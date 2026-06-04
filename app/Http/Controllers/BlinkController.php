<?php

namespace App\Http\Controllers;

use App\Models\BlinkNotifyLog;
use App\Models\ConsentLog;
use App\Models\Transaction;
use App\Services\Blink\BlinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BlinkController extends Controller
{
    public function showOtpPage(string $txnRef)
    {
        $transaction = Transaction::where('txn_ref', $txnRef)
            ->where('operator', 'Banglalink')
            ->whereIn('status', ['pending'])
            ->firstOrFail();

        $maskedPhone      = $this->maskPhone($transaction->phone);
        $resendAvailableAt = $transaction->blink_otp_requested_at
            ? $transaction->blink_otp_requested_at->addMinutes(5)
            : now();

        return view('blink.otp', compact('transaction', 'maskedPhone', 'resendAvailableAt'));
    }

    public function submitOtp(Request $request, string $txnRef)
    {
        $request->validate([
            'otp' => ['required', 'digits:5'],
        ], [
            'otp.required' => 'OTP কোড দিন।',
            'otp.digits'   => 'OTP ৫ সংখ্যার হতে হবে।',
        ]);

        $transaction = Transaction::where('txn_ref', $txnRef)
            ->where('operator', 'Banglalink')
            ->where('status', 'pending')
            ->firstOrFail();

        $result = (new BlinkService())->submitOtp($transaction->phone, $request->otp);

        ConsentLog::record($txnRef, $transaction->phone, 'otp_submitted', [
            'otp'    => $request->otp,
            'result' => $result,
        ]);

        if (!$result['success']) {
            return back()->withErrors(['otp' => 'OTP ভুল বা মেয়াদ শেষ। আবার চেষ্টা করুন।']);
        }

        return redirect()->route('blink.waiting', $txnRef);
    }

    public function showWaitingPage(string $txnRef)
    {
        $transaction = Transaction::where('txn_ref', $txnRef)
            ->where('operator', 'Banglalink')
            ->firstOrFail();

        if ($transaction->status === 'success') {
            return redirect()->route('buy.success', ['ref' => $txnRef]);
        }

        if ($transaction->status === 'failed') {
            return redirect()->route('buy.index')
                ->withErrors(['phone' => 'পেমেন্ট ব্যর্থ হয়েছে: ' . ($transaction->failure_reason ?? 'অজানা কারণ')]);
        }

        $maskedPhone = $this->maskPhone($transaction->phone);
        return view('blink.waiting', compact('transaction', 'maskedPhone'));
    }

    public function pollStatus(string $txnRef)
    {
        $transaction = Transaction::where('txn_ref', $txnRef)
            ->where('operator', 'Banglalink')
            ->first();

        if (!$transaction) {
            return response()->json(['status' => 'expired']);
        }

        if ($transaction->status === 'success') {
            return response()->json([
                'status'   => 'success',
                'redirect' => route('buy.success', ['ref' => $txnRef]),
            ]);
        }

        if ($transaction->status === 'failed') {
            return response()->json([
                'status'  => 'failed',
                'message' => $transaction->failure_reason ?? 'পেমেন্ট ব্যর্থ হয়েছে।',
            ]);
        }

        // Expire after 15 minutes of OTP request
        if ($transaction->blink_otp_requested_at && $transaction->blink_otp_requested_at->lt(now()->subMinutes(15))) {
            return response()->json(['status' => 'expired']);
        }

        // Check for a non-success notify — low balance or other charge failure
        $failedNotify = BlinkNotifyLog::where('txn_ref', $txnRef)
            ->whereRaw('LOWER(status) NOT IN (?, ?)', ['succss', 'success'])
            ->first();

        if ($failedNotify) {
            return response()->json([
                'status'       => 'low_balance',
                'blink_status' => $failedNotify->status,
            ]);
        }

        return response()->json(['status' => 'pending']);
    }

    public function resendOtp(string $txnRef)
    {
        $transaction = Transaction::where('txn_ref', $txnRef)
            ->where('operator', 'Banglalink')
            ->where('status', 'pending')
            ->firstOrFail();

        if ($transaction->blink_otp_requested_at && $transaction->blink_otp_requested_at->gt(now()->subMinutes(5))) {
            $waitSeconds = now()->diffInSeconds($transaction->blink_otp_requested_at->addMinutes(5));
            return back()->withErrors(['otp' => "আরও {$waitSeconds} সেকেন্ড পরে OTP পুনরায় পাঠানো যাবে।"]);
        }

        $result = (new BlinkService())->requestOtp($transaction->phone, max(1, (int) ($transaction->qty ?? 1)));

        if (!$result['success']) {
            Log::error('Blink resend OTP failed', ['txn' => $txnRef, 'result' => $result]);
            return back()->withErrors(['otp' => 'OTP পাঠানো যায়নি। পরে আবার চেষ্টা করুন।']);
        }

        $transaction->update([
            'blink_otp_requested_at' => now(),
            'blink_txn_id'           => $result['transectionId'] ?? $transaction->blink_txn_id,
        ]);

        ConsentLog::record($txnRef, $transaction->phone, 'otp_resent', $result);

        return back()->with('success', 'নতুন OTP পাঠানো হয়েছে।');
    }

    private function maskPhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) >= 11) {
            return substr($clean, 0, 3) . '****' . substr($clean, -4);
        }
        return substr($clean, 0, 3) . '****';
    }
}
