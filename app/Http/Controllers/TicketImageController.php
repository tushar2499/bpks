<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TicketImageController extends Controller
{
    public function download(Request $request)
    {
        $ref = $request->query('ref');

        $txn = Transaction::with('ticket')
            ->where('txn_ref', $ref)
            ->where('status', 'success')
            ->firstOrFail();

        $ticketNo = $txn->ticket->ticket_no;

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
}
