<?php

namespace App\Http\Controllers;

use App\Models\ConsentLog;
use App\Models\SmsLog;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Services\SMS\RobiSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    // ── Robi consent WAP callback (browser GET redirect) ─────────────────────

    public function robiConsent(Request $request, string $txnRef)
    {
        Log::info('Robi consent callback', ['txn_ref' => $txnRef, 'params' => $request->all()]);

        $transaction = Transaction::where('txn_ref', $txnRef)
            ->where('operator', 'Robi')
            ->where('status', 'pending')
            ->first();

        if (!$transaction) {
            Log::warning('Robi consent callback: txn not found or not pending', ['txn_ref' => $txnRef]);
            return redirect()->route('buy.index')->withErrors(['phone' => 'লেনদেন পাওয়া যায়নি।']);
        }

        $resultCode    = (string) $request->query('resultCode', '');
        $dcbTxnId      = $request->query('transactionId');
        $isDoubleConf  = $request->query('isDoubleConfrim');
        $cnfmResult    = $request->query('cnfmResult');
        $callbackData  = $request->all();

        ConsentLog::record(
            $txnRef,
            $transaction->phone,
            'callback_received',
            $callbackData,
            'resultCode=' . $resultCode
        );

        $transaction->update([
            'dcb_txn_id'   => $dcbTxnId,
            'dcb_response' => json_encode($callbackData),
        ]);

        if ($resultCode === '0') {
            return $this->handleConsentSuccess($transaction, $dcbTxnId);
        }

        return $this->handleConsentFailure($transaction, $resultCode);
    }

    private function handleConsentSuccess(Transaction $transaction, ?string $dcbTxnId): \Illuminate\Http\RedirectResponse
    {
        DB::transaction(function () use ($transaction, $dcbTxnId) {
            Ticket::where('id', $transaction->ticket_id)
                ->where('status', 2)
                ->update(['status' => 1, 'phone' => $transaction->phone, 'sold_at' => now()]);

            $transaction->update([
                'status'       => 'success',
                'dcb_txn_id'   => $dcbTxnId,
                'confirmed_at' => now(),
            ]);
        });

        ConsentLog::record($transaction->txn_ref, $transaction->phone, 'ticket_assigned', [
            'ticket_id' => $transaction->ticket_id,
            'dcb_txn_id' => $dcbTxnId,
        ]);

        $this->sendTicketSms($transaction);

        return redirect()->route('buy.success', ['ref' => $transaction->txn_ref]);
    }

    private function handleConsentFailure(Transaction $transaction, string $resultCode): \Illuminate\Http\RedirectResponse
    {
        $reason = $this->resultMessage($resultCode);

        DB::transaction(function () use ($transaction, $reason) {
            Ticket::where('id', $transaction->ticket_id)
                ->where('status', 2)
                ->update(['status' => 0]);

            $transaction->update([
                'status'         => 'failed',
                'failure_reason' => $reason,
            ]);
        });

        ConsentLog::record($transaction->txn_ref, $transaction->phone, 'failed', null, $reason);

        return redirect()->route('buy.index')
            ->withErrors(['phone' => 'পেমেন্ট ব্যর্থ হয়েছে: ' . $reason]);
    }

    private function sendTicketSms(Transaction $transaction): void
    {
        $transaction->load('ticket');
        $ticket = $transaction->ticket;

        if (!$ticket) {
            ConsentLog::record($transaction->txn_ref, $transaction->phone, 'sms_failed', null, 'ticket not found');
            return;
        }

        $amount  = number_format($transaction->amount, 2);
        $message = "প্রিয় গ্রাহক, আপনার BPKS লটারি টিকেট কেনা সফল হয়েছে।\n"
                 . "টিকেট নম্বর: {$ticket->ticket_no}\n"
                 . "মূল্য: ৳{$amount}\n"
                 . "লেনদেন: {$transaction->txn_ref}";

        try {
            $sms    = new RobiSmsService();
            $sent   = $sms->send($transaction->phone, $message, $transaction->txn_ref);
            $step   = $sent ? 'sms_sent' : 'sms_failed';
            $note   = $sent ? null : 'SMS service returned false';
        } catch (\Throwable $e) {
            Log::error('Ticket SMS error', ['txn' => $transaction->txn_ref, 'err' => $e->getMessage()]);
            $step = 'sms_failed';
            $note = $e->getMessage();
        }

        ConsentLog::record($transaction->txn_ref, $transaction->phone, $step, ['ticket_no' => $ticket->ticket_no], $note);
    }

    private function resultMessage(string $code): string
    {
        return match ($code) {
            '1'  => 'Parameter is invalid.',
            '2'  => 'SP authentication error.',
            '3'  => 'SP cannot operate this service.',
            '4'  => 'Insufficient balance.',
            '5'  => 'User is invalid.',
            '6'  => 'Product already purchased.',
            '7'  => 'Product/service does not exist.',
            '8'  => 'Product does not need confirmation.',
            '-1' => 'User cancelled or timeout.',
            '99' => 'Other error.',
            default => 'Unknown error (code: ' . $code . ')',
        };
    }

    // ── Robi SMS delivery notify ──────────────────────────────────────────────

    public function smsNotify(Request $request, int $smsLogId): \Illuminate\Http\JsonResponse
    {
        Log::info('SMS notify', ['id' => $smsLogId, 'body' => $request->all()]);

        $smsLog = SmsLog::find($smsLogId);
        if ($smsLog) {
            $status = $request->input('outboundSMSMessageRequest.deliveryInfoList.deliveryInfo.0.deliveryStatus')
                   ?? $request->input('deliveryStatus')
                   ?? 'notified';

            $smsLog->update([
                'status_message' => $status,
                'response'       => json_encode($request->all()),
            ]);
        }

        return response()->json(['result' => 'ok']);
    }

    // ── Robi async direct-charge callback (POST) ──────────────────────────────

    public function robi(Request $request)
    {
        Log::info('Robi callback', $request->all());

        $externalId = $request->input('externalId') ?? $request->input('externalid');
        $resultCode = $request->input('resultCode');
        $dcbTxnId   = $request->input('transactionId');

        return $this->process($externalId, $resultCode === '0', $dcbTxnId, $request->all());
    }

    // ── Grameenphone async callback ───────────────────────────────────────────

    public function grameenphone(Request $request)
    {
        Log::info('GP callback', $request->all());

        $externalId = $request->input('externalId');
        $success    = $request->input('status') === 'SUCCESS';
        $dcbTxnId   = $request->input('transactionId');

        return $this->process($externalId, $success, $dcbTxnId, $request->all());
    }

    // ── Banglalink async callback ─────────────────────────────────────────────

    public function banglalink(Request $request)
    {
        Log::info('BL callback', $request->all());

        $externalId = $request->input('externalId');
        $success    = ($request->input('statusCode') ?? '') === '200';
        $dcbTxnId   = $request->input('transactionId');

        return $this->process($externalId, $success, $dcbTxnId, $request->all());
    }

    private function process(string $txnRef, bool $success, ?string $dcbTxnId, array $raw): \Illuminate\Http\JsonResponse
    {
        $transaction = Transaction::where('txn_ref', $txnRef)->where('status', 'pending')->first();

        if (!$transaction) {
            Log::warning('Callback: txn not found or not pending', ['ref' => $txnRef]);
            return response()->json(['result' => 'not_found'], 200);
        }

        DB::transaction(function () use ($transaction, $success, $dcbTxnId, $raw) {
            $transaction->update([
                'dcb_txn_id'   => $dcbTxnId,
                'dcb_response' => json_encode($raw),
            ]);

            if ($success) {
                Ticket::where('id', $transaction->ticket_id)
                    ->where('status', 2)
                    ->update(['status' => 1, 'phone' => $transaction->phone, 'sold_at' => now()]);

                $transaction->update(['status' => 'success', 'confirmed_at' => now()]);
            } else {
                Ticket::where('id', $transaction->ticket_id)
                    ->where('status', 2)
                    ->update(['status' => 0]);

                $transaction->update(['status' => 'failed', 'failure_reason' => 'Operator declined']);
            }
        });

        return response()->json(['result' => 'ok'], 200);
    }
}
