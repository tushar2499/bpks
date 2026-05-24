<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TicketImageController extends Controller
{
    public function download(Request $request)
    {
        $ref      = $request->query('ref');
        $wantedNo = $request->query('ticket_no');

        $txn = Transaction::where('txn_ref', $ref)
            ->where('status', 'success')
            ->firstOrFail();

        $ids      = $txn->ticket_ids ?? [$txn->ticket_id];
        $tickets  = Ticket::whereIn('id', array_filter($ids))->get();

        if ($wantedNo) {
            $ticket = $tickets->firstWhere('ticket_no', $wantedNo);
            abort_if(!$ticket, 404);
        } else {
            $ticket = $tickets->first();
            abort_if(!$ticket, 404);
        }

        $ticketNo = $ticket->ticket_no;

        $basePath = public_path('bpks-lottery.png');
        $fontPath = public_path('fonts/arialbd.ttf');

        $img = imagecreatefrompng($basePath);
        imageAlphaBlending($img, true);
        imageSaveAlpha($img, true);

        $imgW = imagesx($img);
        $imgH = imagesy($img);

        $red    = imagecolorallocate($img, 185, 28, 28);
        $shadow = imagecolorallocatealpha($img, 0, 0, 0, 60);

        imagettftext($img, 28, 0, 577, 132, $shadow, $fontPath, $ticketNo);
        imagettftext($img, 28, 0, 575, 130, $red,    $fontPath, $ticketNo);

        $this->stampSecurityBand($img, $imgW, $imgH, $txn->txn_ref, $txn->phone, 1.0);

        $filename = 'BPKS-Ticket-' . $ticketNo . '.png';

        setcookie('dl_ready', '1', time() + 60, '/', '', false, false);

        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        imagepng($img);
        imagedestroy($img);
        exit;
    }

    public function downloadPdf(Request $request)
    {
        $txn = Transaction::where('txn_ref', $request->query('ref'))
            ->where('status', 'success')
            ->firstOrFail();

        $ids     = $txn->ticket_ids ?? [$txn->ticket_id];
        $tickets = Ticket::whereIn('id', array_filter($ids))->get();
        abort_if($tickets->isEmpty(), 404);

        $images = $tickets->map(fn($t) => [
            'ticket_no' => $t->ticket_no,
            'b64'       => $this->renderTicketJpeg($t->ticket_no, $txn->txn_ref, $txn->phone),
        ]);

        $pdf = Pdf::loadView('tickets.pdf', [
            'images'  => $images,
            'txn_ref' => $txn->txn_ref,
            'phone'   => $txn->phone,
        ])->setOptions(['isHtml5ParserEnabled' => false, 'isRemoteEnabled' => false])
          ->setPaper([0, 0, 595, 580], 'portrait');

        return $pdf->download('BPKS-Tickets-' . $txn->txn_ref . '.pdf')
            ->cookie('dl_ready', '1', 1, '/', null, false, false);
    }

    public function downloadAllPdf(Request $request)
    {
        set_time_limit(120);

        $phone = $request->query('phone');
        abort_if(!$phone, 400);

        $transactions = Transaction::where('phone', $phone)
            ->where('status', 'success')
            ->get();
        abort_if($transactions->isEmpty(), 404);

        $ticketMeta = [];
        foreach ($transactions as $t) {
            foreach (($t->ticket_ids ?? [$t->ticket_id]) as $id) {
                $ticketMeta[$id] = ['txn_ref' => $t->txn_ref, 'phone' => $t->phone];
            }
        }

        $allIds  = collect(array_keys($ticketMeta))->filter()->unique()->values();
        $tickets = Ticket::whereIn('id', $allIds)->get();
        abort_if($tickets->isEmpty(), 404);

        $images = $tickets->map(fn($t) => [
            'ticket_no' => $t->ticket_no,
            'b64'       => $this->renderTicketJpeg(
                $t->ticket_no,
                $ticketMeta[$t->id]['txn_ref'] ?? '',
                $ticketMeta[$t->id]['phone'] ?? $phone,
            ),
        ]);

        $pdf = Pdf::loadView('tickets.pdf', [
            'images'  => $images,
            'txn_ref' => '',
            'phone'   => $phone,
        ])->setOptions(['isHtml5ParserEnabled' => false, 'isRemoteEnabled' => false])
          ->setPaper([0, 0, 595, 580], 'portrait');

        return $pdf->download('BPKS-Tickets-' . $phone . '.pdf')
            ->cookie('dl_ready', '1', 1, '/', null, false, false);
    }

    private function renderTicketJpeg(string $ticketNo, string $txnRef, string $phone): string
    {
        $basePath = public_path('bpks-lottery.png');
        $fontPath = public_path('fonts/arialbd.ttf');

        $src = imagecreatefrompng($basePath);
        imageAlphaBlending($src, true);

        $srcW = imagesx($src);
        $srcH = imagesy($src);

        $dstW = 800;
        $dstH = (int) round($srcH * $dstW / $srcW);

        $dst = imagecreatetruecolor($dstW, $dstH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
        imagedestroy($src);

        $scale  = $dstW / $srcW;
        $red    = imagecolorallocate($dst, 185, 28, 28);
        $shadow = imagecolorallocatealpha($dst, 0, 0, 0, 60);
        $fs     = (int) round(28 * $scale);
        $tx     = (int) round(575 * $scale);
        $ty     = (int) round(130 * $scale);

        imagettftext($dst, $fs, 0, $tx + 2, $ty + 2, $shadow, $fontPath, $ticketNo);
        imagettftext($dst, $fs, 0, $tx,     $ty,     $red,    $fontPath, $ticketNo);

        $this->stampSecurityBand($dst, $dstW, $dstH, $txnRef, $phone, $scale);

        ob_start();
        imagejpeg($dst, null, 90);
        $data = ob_get_clean();
        imagedestroy($dst);

        return base64_encode($data);
    }

    private function stampSecurityBand($img, int $w, int $h, string $txnRef, string $phone, float $scale): void
    {
        $fontPath = public_path('fonts/arialbd.ttf');

        // Vertical center between top (ticket face) and bottom (rules) sections
        $midY = (int) round(0.485 * $h);

        // Format: TXN ref in groups of 4 separated by ·
        $txnLabel = implode(' · ', str_split($txnRef, 4));

        // Phone: normalize to 13-digit MSISDN then group
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) === 11 && str_starts_with($clean, '01')) {
            $clean = '88' . $clean;
        }
        $phoneLabel = implode('-', str_split($clean, 4));

        $fs1 = (int) round(14 * $scale);
        $fs2 = (int) round(11 * $scale);

        $white  = imagecolorallocate($img, 255, 255, 255);
        $gold   = imagecolorallocate($img, 212, 175, 55);
        $dark   = imagecolorallocatealpha($img, 0, 0, 0, 20);

        // TXN line — centered, thick outline for readability without background
        $bb1 = imagettfbbox($fs1, -3, $fontPath, $txnLabel);
        $tw1 = abs($bb1[2] - $bb1[0]);
        $x1  = max(4, (int) round(($w - $tw1) / 2));
        $y1  = $midY - (int) round(4 * $scale);

        foreach ([[-2,-2],[-2,0],[-2,2],[0,-2],[0,2],[2,-2],[2,0],[2,2]] as [$ox, $oy]) {
            imagettftext($img, $fs1, -3, $x1 + $ox, $y1 + $oy, $dark, $fontPath, $txnLabel);
        }
        imagettftext($img, $fs1, -3, $x1, $y1, $white, $fontPath, $txnLabel);

        // Phone line — centered, gold color
        $bb2 = imagettfbbox($fs2, -3, $fontPath, $phoneLabel);
        $tw2 = abs($bb2[2] - $bb2[0]);
        $x2  = max(4, (int) round(($w - $tw2) / 2));
        $y2  = $midY + (int) round(18 * $scale);

        foreach ([[-2,-2],[-2,0],[-2,2],[0,-2],[0,2],[2,-2],[2,0],[2,2]] as [$ox, $oy]) {
            imagettftext($img, $fs2, -3, $x2 + $ox, $y2 + $oy, $dark, $fontPath, $phoneLabel);
        }
        imagettftext($img, $fs2, -3, $x2, $y2, $gold, $fontPath, $phoneLabel);
    }
}
