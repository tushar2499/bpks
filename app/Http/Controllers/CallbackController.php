<?php

namespace App\Http\Controllers;

use App\Models\ConsentLog;
use App\Models\SmsLog;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Services\DCB\GpConsentService;
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
            ->first();

        if (!$transaction) {
            Log::warning('Robi consent callback: txn not found', ['txn_ref' => $txnRef]);
            return redirect()->route('buy.index')->withErrors(['phone' => 'লেনদেন পাওয়া যায়নি।']);
        }

        // Async callback already processed this transaction before browser redirect arrived
        if ($transaction->status === 'success') {
            Log::info('Robi consent callback: already success (async arrived first)', ['txn_ref' => $txnRef]);
            return redirect()->route('buy.success', ['ref' => $transaction->txn_ref]);
        }

        if ($transaction->status === 'failed') {
            Log::info('Robi consent callback: already failed (async arrived first)', ['txn_ref' => $txnRef]);
            return redirect()->route('buy.index')
                ->withErrors(['phone' => 'পেমেন্ট ব্যর্থ হয়েছে: ' . ($transaction->failure_reason ?? 'অজানা ত্রুটি')]);
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
        $ids = $transaction->ticket_ids ?? [$transaction->ticket_id];

        DB::transaction(function () use ($transaction, $dcbTxnId, $ids) {
            Ticket::whereIn('id', array_filter($ids))
                ->where('status', 2)
                ->update(['status' => 1, 'phone' => $transaction->phone, 'sold_at' => now()]);

            $transaction->update([
                'status'       => 'success',
                'dcb_txn_id'   => $dcbTxnId,
                'confirmed_at' => now(),
            ]);
        });

        ConsentLog::record($transaction->txn_ref, $transaction->phone, 'ticket_assigned', [
            'ticket_ids' => $ids,
            'dcb_txn_id' => $dcbTxnId,
        ]);

        $this->sendTicketSms($transaction);

        return redirect()->route('buy.success', ['ref' => $transaction->txn_ref]);
    }

    private function handleConsentFailure(Transaction $transaction, string $resultCode): \Illuminate\Http\RedirectResponse
    {
        $reason = $this->resultMessage($resultCode);

        $ids = $transaction->ticket_ids ?? [$transaction->ticket_id];

        DB::transaction(function () use ($transaction, $reason, $ids) {
            Ticket::whereIn('id', array_filter($ids))
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
        $ids     = $transaction->ticket_ids ?? [$transaction->ticket_id];
        $tickets = Ticket::whereIn('id', array_filter($ids))->get();

        if ($tickets->isEmpty()) {
            ConsentLog::record($transaction->txn_ref, $transaction->phone, 'sms_failed', null, 'tickets not found');
            return;
        }

        $ticketNos   = $tickets->pluck('ticket_no')->implode(', ');
        $amount      = number_format($transaction->amount, 2);
        $downloadUrl = route('ticket.download-all-pdf', ['phone' => $transaction->phone]);

        $defaultMessage = "প্রিয় গ্রাহক, আপনার BPKS লটারি টিকেট কেনা সফল হয়েছে।\n"
                        . "টিকেট নম্বর: {$ticketNos}\n"
                        . "মূল্য: ৳{$amount}\n"
                        . "লেনদেন: {$transaction->txn_ref}";

        $gpMessage = "আপনি সফল ভাবে BPKS লটারির টিকিট ক্রয় করেছেন। চার্জ ২০ টাকা।"
                   . " টিকেট নাম্বার: '{$ticketNos}' ,"
                   . " ডাউনলোড টিকিট: '{$downloadUrl}'"
                   . " | হেল্পলাইন: +8801725298711 (চার্জ প্রযোজ্য)";

        try {
            if ($transaction->operator === 'Grameenphone') {
                $acr  = $transaction->gp_customer_ref;
                $sent = $acr
                    ? (new GpConsentService())->sendSms($acr, $transaction->phone, $gpMessage)
                    : false;
                $note = $sent ? null : 'Missing ACR or GP SMS failed';
            } else {
                $sms  = new RobiSmsService();
                $sent = $sms->send($transaction->phone, $defaultMessage, $transaction->txn_ref);
                $note = $sent ? null : 'SMS service returned false';
            }

            $step = $sent ? 'sms_sent' : 'sms_failed';
        } catch (\Throwable $e) {
            Log::error('Ticket SMS error', ['txn' => $transaction->txn_ref, 'err' => $e->getMessage()]);
            $step = 'sms_failed';
            $note = $e->getMessage();
        }

        ConsentLog::record($transaction->txn_ref, $transaction->phone, $step, ['ticket_nos' => $ticketNos], $note);
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

    // ── Grameenphone DOB consent callback (browser GET redirect) ─────────────

    public function gpCallback(Request $request, string $txnRef, string $status)
    {
        Log::info('GP consent callback', ['txn_ref' => $txnRef, 'status' => $status, 'params' => $request->all()]);

        $transaction = Transaction::where('txn_ref', $txnRef)
            ->where('operator', 'Grameenphone')
            ->first();

        if (!$transaction) {
            Log::warning('GP callback: txn not found', ['txn_ref' => $txnRef]);
            return redirect()->route('buy.index')->withErrors(['phone' => 'লেনদেন পাওয়া যায়নি।']);
        }

        if ($transaction->status === 'success') {
            return redirect()->route('buy.success', ['ref' => $transaction->txn_ref]);
        }

        if ($transaction->status === 'failed') {
            return redirect()->route('buy.index')
                ->withErrors(['phone' => 'পেমেন্ট ব্যর্থ হয়েছে: ' . ($transaction->failure_reason ?? 'অজানা ত্রুটি')]);
        }

        ConsentLog::record($txnRef, $transaction->phone, 'callback_received', $request->all(), 'status=' . $status);

        if ($status !== 'ok') {
            return $this->handleConsentFailure($transaction, 'GP consent ' . $status);
        }

        $consentId         = $request->query('consentId', '');
        $customerReference = $request->query('customerReference', '');

        if (!$consentId || !$customerReference) {
            Log::error('GP ok callback missing consentId or customerReference', ['txn' => $txnRef, 'params' => $request->all()]);
            return $this->handleConsentFailure($transaction, 'Missing consent parameters from GP');
        }

        $transaction->update([
            'gp_consent_id'  => $consentId,
            'gp_customer_ref' => $customerReference,
            'dcb_response'   => json_encode($request->all()),
        ]);

        $gpAmount = (float) config('dcb.grameenphone.amount') * max(1, (int) ($transaction->qty ?? 1));

        $charge = (new GpConsentService())->chargePayment(
            $customerReference,
            $consentId,
            $txnRef,
            $gpAmount
        );

        if ($charge['success']) {
            $transaction->update([
                'gp_charge_request' => $charge['request'],
                'dcb_response'      => $charge['response'],
            ]);
            return $this->handleConsentSuccess($transaction, $charge['server_ref']);
        }

        $transaction->update([
            'gp_charge_request' => $charge['request'],
            'dcb_response'      => $charge['response'],
        ]);
        ConsentLog::record($txnRef, $transaction->phone, 'charge_failed',
            json_decode($charge['response'], true),
            $charge['reason']
        );
        return $this->handleConsentFailure($transaction, $charge['reason']);
    }

    // ── Grameenphone async server callback (POST) ─────────────────────────────

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

        $ids = $transaction->ticket_ids ?? [$transaction->ticket_id];

        DB::transaction(function () use ($transaction, $success, $dcbTxnId, $raw, $ids) {
            $transaction->update([
                'dcb_txn_id'   => $dcbTxnId,
                'dcb_response' => json_encode($raw),
            ]);

            if ($success) {
                Ticket::whereIn('id', array_filter($ids))
                    ->where('status', 2)
                    ->update(['status' => 1, 'phone' => $transaction->phone, 'sold_at' => now()]);

                $transaction->update(['status' => 'success', 'confirmed_at' => now()]);
            } else {
                Ticket::whereIn('id', array_filter($ids))
                    ->where('status', 2)
                    ->update(['status' => 0]);

                $transaction->update(['status' => 'failed', 'failure_reason' => 'Operator declined']);
            }
        });

        return response()->json(['result' => 'ok'], 200);
    }
}
