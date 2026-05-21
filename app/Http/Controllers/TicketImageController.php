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

        $w = imagesx($img); // 1052
        $h = imagesy($img); // 1024

        // Colors
        $red    = imagecolorallocate($img, 185, 28, 28);
        $shadow = imagecolorallocatealpha($img, 0, 0, 0, 60);

        // Ticket number — right of BPKS logo (logo ~x:430-560, y:30-160)
        $textX    = 575;
        $textY    = 130;
        $fontSize = 52;

        imagettftext($img, $fontSize, 0, $textX + 2, $textY + 2, $shadow, $fontPath, $ticketNo);
        imagettftext($img, $fontSize, 0, $textX,     $textY,     $red,    $fontPath, $ticketNo);

        // Output
        $filename = 'BPKS-Ticket-' . $ticketNo . '.png';

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
            'b64'       => $this->renderTicketJpeg($t->ticket_no),
        ]);

        $pdf = Pdf::loadView('tickets.pdf', [
            'images'  => $images,
            'txn_ref' => $txn->txn_ref,
            'phone'   => $txn->phone,
        ])->setOptions(['isHtml5ParserEnabled' => false, 'isRemoteEnabled' => false])
          ->setPaper([0, 0, 595, 580], 'portrait');

        return $pdf->download('BPKS-Tickets-' . $txn->txn_ref . '.pdf')
            ->cookie('dl_ready', '1', 1, '/');
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

        $allIds  = $transactions->flatMap(fn($t) => $t->ticket_ids ?? [$t->ticket_id])->filter()->unique()->values();
        $tickets = Ticket::whereIn('id', $allIds)->get();
        abort_if($tickets->isEmpty(), 404);

        $images = $tickets->map(fn($t) => [
            'ticket_no' => $t->ticket_no,
            'b64'       => $this->renderTicketJpeg($t->ticket_no),
        ]);

        $pdf = Pdf::loadView('tickets.pdf', [
            'images'  => $images,
            'txn_ref' => '',
            'phone'   => $phone,
        ])->setOptions(['isHtml5ParserEnabled' => false, 'isRemoteEnabled' => false])
          ->setPaper([0, 0, 595, 580], 'portrait');

        return $pdf->download('BPKS-Tickets-' . $phone . '.pdf')
            ->cookie('dl_ready', '1', 1, '/');
    }

    private function renderTicketJpeg(string $ticketNo): string
    {
        $basePath = public_path('bpks-lottery.png');
        $fontPath = public_path('fonts/arialbd.ttf');

        $src = imagecreatefrompng($basePath);
        imageAlphaBlending($src, true);

        $srcW = imagesx($src);
        $srcH = imagesy($src);

        // Scale to 400px wide — enough for PDF quality, ~4× less data than original
        $dstW = 400;
        $dstH = (int) round($srcH * $dstW / $srcW);

        $dst = imagecreatetruecolor($dstW, $dstH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
        imagedestroy($src);

        $scale  = $dstW / $srcW;
        $red    = imagecolorallocate($dst, 185, 28, 28);
        $shadow = imagecolorallocatealpha($dst, 0, 0, 0, 60);
        $fs     = (int) round(52 * $scale);
        $tx     = (int) round(575 * $scale);
        $ty     = (int) round(130 * $scale);

        imagettftext($dst, $fs, 0, $tx + 1, $ty + 1, $shadow, $fontPath, $ticketNo);
        imagettftext($dst, $fs, 0, $tx,     $ty,     $red,    $fontPath, $ticketNo);

        ob_start();
        imagejpeg($dst, null, 70);
        $data = ob_get_clean();
        imagedestroy($dst);

        return base64_encode($data);
    }
}
