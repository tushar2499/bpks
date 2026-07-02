<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlinkNotifyLog;
use App\Models\ConsentLog;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Services\Blink\BlinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerCareController extends Controller
{
    public function index(Request $request)
    {
        $phone        = null;
        $transactions = collect();
        $summary      = null;
        $blinkStatus  = null;
        $blinkMatchedMap = collect();

        if ($request->filled('phone')) {
            $phone = preg_replace('/\D/', '', trim($request->phone));
            if (str_starts_with($phone, '880') && strlen($phone) === 13) {
                $phone = '0' . substr($phone, 3);
            } elseif (str_starts_with($phone, '88') && strlen($phone) === 13) {
                $phone = '0' . substr($phone, 2);
            }

            $transactions = Transaction::with(['smsLog', 'consentLogs'])
                ->where('phone', $phone)
                ->orderByDesc('created_at')
                ->get();

            // Batch-load all tickets
            $allIds = $transactions->flatMap(fn($t) => $t->ticket_ids ?? array_filter([$t->ticket_id]))
                ->unique()->filter();
            $ticketsById = Ticket::whereIn('id', $allIds)->pluck('ticket_no', 'id');

            foreach ($transactions as $txn) {
                $ids = $txn->ticket_ids ?? array_filter([$txn->ticket_id]);
                $txn->resolved_ticket_nos = collect($ids)
                    ->map(fn($id) => $ticketsById[$id] ?? null)
                    ->filter()->values()->all();
            }

            $successful = $transactions->where('status', 'success');

            $summary = [
                'total_transactions' => $transactions->count(),
                'successful'         => $successful->count(),
                'total_tickets'      => $successful->sum('qty'),
                'total_spent'        => $successful->sum('amount'),
                'operators'          => $transactions->pluck('operator')->unique()->filter()->values(),
                'last_purchase'      => $successful->sortByDesc('confirmed_at')->first()?->confirmed_at,
            ];

            $blinkStatus = (new BlinkService())->getTransactionStatus($phone);

            if ($blinkStatus && $blinkStatus['success']) {
                $successTxnIds = collect($blinkStatus['data']['records'] ?? [])
                    ->filter(fn($r) => ($r['chargeAmount'] ?? 0) > 0 && stripos($r['reason'] ?? '', 'success') !== false)
                    ->pluck('transactionId')->filter()->values()->all();

                if ($successTxnIds) {
                    $blinkMatchedMap = BlinkNotifyLog::whereIn('blink_txn_id', $successTxnIds)
                        ->where('matched', 'yes')
                        ->pluck('txn_ref', 'blink_txn_id');
                }
            }
        }

        return view('admin.customer-care.index', compact('phone', 'transactions', 'summary', 'blinkStatus', 'blinkMatchedMap'));
    }

    public function assignBlinkTicket(Request $request)
    {
        $request->validate([
            'msisdn'        => ['required'],
            'blink_txn_id'  => ['required'],
            'charge_amount' => ['required', 'numeric', 'min:1'],
        ]);

        $phone      = preg_replace('/\D/', '', trim($request->msisdn));
        if (str_starts_with($phone, '880') && strlen($phone) === 13) {
            $phone = '0' . substr($phone, 3);
        }

        $blinkTxnId = $request->blink_txn_id;
        $amount     = (float) $request->charge_amount;
        $prices     = config('dcb.banglalink.prices', []);
        $qty        = array_search($amount, array_map('floatval', $prices));
        $qty        = $qty !== false ? (int) $qty : max(1, (int) round($amount / 20));

        // Guard: already assigned
        if (Transaction::where('blink_txn_id', $blinkTxnId)->where('status', 'success')->exists()) {
            return back()->withErrors(['assign' => 'এই ট্রানজেকশনের জন্য টিকেট ইতোমধ্যে তৈরি হয়েছে।'])->withInput();
        }

        try {
            $transaction = DB::transaction(function () use ($phone, $qty, $blinkTxnId, $amount) {
                $tier = DB::table('tickets')
                    ->where('operator', 'Banglalink')
                    ->where('status', 0)
                    ->whereNotNull('series')
                    ->min('sale_tier');

                if ($tier === null) return null;

                $candidateIds = Ticket::where('operator', 'Banglalink')
                    ->where('status', 0)
                    ->where('sale_tier', $tier)
                    ->inRandomOrder()
                    ->limit($qty * 3)
                    ->pluck('id');

                if ($candidateIds->isEmpty()) return null;

                $tickets = Ticket::whereIn('id', $candidateIds)
                    ->where('status', 0)
                    ->orderBy('id')
                    ->limit($qty)
                    ->lockForUpdate()
                    ->get();

                if ($tickets->count() < $qty) return null;

                $ticketIds   = $tickets->pluck('id')->toArray();
                $totalAmount = config('dcb.banglalink.prices', [])[$qty] ?? ($qty * 20);
                $txnRef      = 'BLNKM' . strtoupper(Str::random(12));

                $txn = Transaction::create([
                    'txn_ref'      => $txnRef,
                    'ticket_id'    => $tickets->first()->id,
                    'ticket_ids'   => $ticketIds,
                    'phone'        => $phone,
                    'operator'     => 'Banglalink',
                    'amount'       => $totalAmount,
                    'qty'          => $qty,
                    'status'       => 'success',
                    'blink_txn_id' => $blinkTxnId,
                    'confirmed_at' => now(),
                ]);

                Ticket::whereIn('id', $ticketIds)->update([
                    'status'   => 1,
                    'phone'    => $phone,
                    'operator' => 'Banglalink',
                    'sold_at'  => now(),
                ]);

                return $txn;
            });
        } catch (\Throwable $e) {
            Log::error('Blink manual assign failed', ['phone' => $phone, 'blink_txn_id' => $blinkTxnId, 'err' => $e->getMessage()]);
            return back()->withErrors(['assign' => 'সিস্টেম ত্রুটি: ' . $e->getMessage()])->withInput();
        }

        if (!$transaction) {
            return back()->withErrors(['assign' => 'টিকেট বরাদ্দ করা সম্ভব হয়নি। পর্যাপ্ত Banglalink টিকেট নেই।'])->withInput();
        }

        // Update or create notify log
        BlinkNotifyLog::updateOrCreate(
            ['blink_txn_id' => $blinkTxnId],
            ['matched' => 'yes', 'txn_ref' => $transaction->txn_ref, 'msisdn' => $phone, 'payload' => '{}']
        );

        /** @var \App\Models\User $admin */
        $admin = auth()->user();
        ConsentLog::record($transaction->txn_ref, $phone, 'ticket_assigned_manual', [
            'ticket_ids' => $transaction->ticket_ids,
            'by'         => $admin?->name,
        ]);

        // Send SMS
        $this->sendManualBlinkSms($transaction);

        return redirect()->route('admin.customer-care.index', ['phone' => $phone])
            ->with('success', 'টিকেট সফলভাবে বরাদ্দ হয়েছে: ' . $transaction->txn_ref);
    }

    private function sendManualBlinkSms(Transaction $transaction): void
    {
        $ids     = $transaction->ticket_ids ?? [$transaction->ticket_id];
        $tickets = Ticket::whereIn('id', array_filter($ids))->get();

        if ($tickets->isEmpty()) return;

        $ticketNos   = $tickets->pluck('ticket_no')->implode(', ');
        $amount      = number_format($transaction->amount, 2);
        $downloadUrl = route('ticket.download-all-pdf', ['phone' => $transaction->phone]);
        $message     = "আপনি সফল ভাবে BPKS ({$ticketNos}) টিকেট ক্রয় করেছেন। মূল্য: ৳{$amount} (ট্যাক্সসহ) | ট্রানজেকশন: {$transaction->txn_ref} | ডাউনলোড: {$downloadUrl} । হেল্পলাইন: 01920934747 (9:30 AM-5:30 PM)";

        try {
            $sent = (new BlinkService())->sendSms($transaction->phone, $message, $transaction->txn_ref);
            ConsentLog::record($transaction->txn_ref, $transaction->phone, $sent ? 'sms_sent' : 'sms_failed', ['ticket_nos' => $ticketNos]);
        } catch (\Throwable $e) {
            Log::error('Blink manual assign SMS error', ['txn' => $transaction->txn_ref, 'err' => $e->getMessage()]);
            ConsentLog::record($transaction->txn_ref, $transaction->phone, 'sms_failed', null, $e->getMessage());
        }
    }
}
