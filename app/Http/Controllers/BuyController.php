<?php

namespace App\Http\Controllers;

use App\Models\ConsentLog;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Services\DCB\DCBFactory;
use App\Services\DCB\RobiConsentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BuyController extends Controller
{
    public function index()
    {
        return view('buy.index');
    }

    public function initiate(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'regex:/^(\+?880|0)?1[3-9]\d{8}$/'],
            'qty'   => ['nullable', 'integer', 'min:1', 'max:10'],
        ], [
            'phone.required' => 'মোবাইল নম্বর দিন।',
            'phone.regex'    => 'বৈধ বাংলাদেশী নম্বর দিন।',
            'qty.min'        => 'কমপক্ষে ১টি টিকেট কিনতে হবে।',
            'qty.max'        => 'সর্বোচ্চ ১০টি টিকেট কেনা যাবে।',
        ]);

        $phone    = $this->normalizePhone($request->phone);
        $qty      = max(1, min(10, (int) ($request->qty ?? 1)));
        $operator = DCBFactory::detectOperator($phone);

        if (!$operator) {
            return back()->withErrors(['phone' => 'অপারেটর সনাক্ত হয়নি। সঠিক নম্বর দিন।'])->withInput();
        }

        if ($operator === 'Teletalk') {
            return back()->withErrors(['phone' => 'Teletalk এখনো সাপোর্ট করা হয়নি।'])->withInput();
        }

        // Rate limit: 1 pending purchase per phone per 2 minutes
        $recentTxn = Transaction::where('phone', $phone)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();

        if ($recentTxn) {
            return back()->withErrors(['phone' => 'আপনার একটি লেনদেন চলছে। ২ মিনিট পর চেষ্টা করুন।'])->withInput();
        }

        // Atomic: lock qty unsold tickets for this operator
        $result = DB::transaction(function () use ($phone, $operator, $qty) {

            $tickets = Ticket::where('status', 0)
                ->where('operator', $operator)
                ->inRandomOrder()
                ->lockForUpdate()
                ->limit($qty)
                ->get();

            if ($tickets->count() < $qty) {
                $found = $tickets->count();
                return ['error' => $found === 0
                    ? "দুঃখিত! {$operator} এর জন্য কোনো টিকেট পাওয়া যায়নি।"
                    : "দুঃখিত! মাত্র {$found}টি টিকেট পাওয়া যাচ্ছে।"];
            }

            $txnRef      = 'BPKS' . strtoupper(Str::random(13)); // no hyphen — Robi accepts alphanumeric only
            $totalAmount = $tickets->sum('sell_price');

            $ticketIds = $tickets->pluck('id')->all();

            $transaction = Transaction::create([
                'txn_ref'    => $txnRef,
                'ticket_id'  => $tickets->first()->id,
                'ticket_ids' => $ticketIds,
                'phone'      => $phone,
                'operator'   => $operator,
                'amount'     => $totalAmount,
                'qty'        => $qty,
                'status'     => 'pending',
            ]);

            // Reserve all tickets
            Ticket::whereIn('id', $ticketIds)->update(['status' => 2]);

            return ['transaction' => $transaction, 'tickets' => $tickets];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['phone' => $result['error']])->withInput();
        }

        $transaction = $result['transaction'];

        // ── Robi: WAP consent redirect flow ─────────────────────────────────
        if ($operator === 'Robi') {
            return $this->initiateRobiConsent($transaction);
        }

        // ── Other operators: direct charge ───────────────────────────────────
        try {
            $dcb      = DCBFactory::make($operator);
            $response = $dcb->charge($phone, $transaction->amount, $transaction->txn_ref);
        } catch (\Throwable $e) {
            Log::error('DCB init error', ['txn' => $transaction->txn_ref, 'err' => $e->getMessage()]);
            $this->rollbackTransaction($transaction);
            return back()->withErrors(['phone' => 'পেমেন্ট সিস্টেমে সমস্যা। পরে চেষ্টা করুন।'])->withInput();
        }

        $transaction->update([
            'dcb_txn_id'   => $response['dcb_txn_id'],
            'dcb_response' => $response['response'],
        ]);

        if ($response['success']) {
            return $this->confirmSuccess($transaction);
        }

        $this->rollbackTransaction($transaction, $response['failure_reason']);

        return back()->withErrors(['phone' => 'পেমেন্ট ব্যর্থ হয়েছে: ' . ($response['failure_reason'] ?? 'অজানা কারণ')])->withInput();
    }

    private function initiateRobiConsent(Transaction $transaction): \Illuminate\Http\RedirectResponse
    {
        $callbackUrl = route('callback.robi-consent', ['txnRef' => $transaction->txn_ref]);

        $basePoisha  = (int) config('dcb.robi.dcb_amount');
        $totalPoisha = $basePoisha * max(1, (int) ($transaction->qty ?? 1));

        try {
            $consent = (new RobiConsentService())->buildConsentUrl(
                $transaction->phone,
                $transaction->txn_ref,
                $callbackUrl,
                $totalPoisha
            );
        } catch (\Throwable $e) {
            Log::error('Robi consent build error', ['txn' => $transaction->txn_ref, 'err' => $e->getMessage()]);
            $this->rollbackTransaction($transaction);
            return back()->withErrors(['phone' => 'পেমেন্ট সিস্টেমে সমস্যা। পরে চেষ্টা করুন।'])->withInput();
        }

        $transaction->update([
            'nonce'                => $consent['nonce'],
            'consent_url'          => $consent['consent_url'],
            'consent_payload'      => $consent['payload'],
            'consent_initiated_at' => now(),
        ]);

        ConsentLog::record($transaction->txn_ref, $transaction->phone, 'consent_generated', $consent['payload']);
        ConsentLog::record($transaction->txn_ref, $transaction->phone, 'redirected', ['consent_url' => $consent['consent_url']]);

        return redirect()->away($consent['consent_url']);
    }

    private function confirmSuccess(Transaction $transaction): \Illuminate\Http\RedirectResponse
    {
        DB::transaction(function () use ($transaction) {
            $ids = $transaction->ticket_ids ?? [$transaction->ticket_id];
            Ticket::whereIn('id', array_filter($ids))->update([
                'status'  => 1,
                'phone'   => $transaction->phone,
                'sold_at' => now(),
            ]);
            $transaction->update([
                'status'       => 'success',
                'confirmed_at' => now(),
            ]);
        });

        return redirect()->route('buy.success', ['ref' => $transaction->txn_ref]);
    }

    public function success(Request $request)
    {
        $transaction = Transaction::with('ticket')
            ->where('txn_ref', $request->ref)
            ->where('status', 'success')
            ->firstOrFail();

        return view('buy.success', compact('transaction'));
    }

    private function rollbackTransaction(Transaction $transaction, ?string $reason = null): void
    {
        DB::transaction(function () use ($transaction, $reason) {
            $ids = $transaction->ticket_ids ?? [$transaction->ticket_id];
            Ticket::whereIn('id', array_filter($ids))->where('status', 2)->update(['status' => 0]);
            $transaction->update([
                'status'         => 'failed',
                'failure_reason' => $reason,
            ]);
        });
    }

    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) === 10 && $clean[0] === '1') {
            return '0' . $clean;
        }
        if (strlen($clean) === 13 && str_starts_with($clean, '880')) {
            return '0' . substr($clean, 3);
        }
        return $clean;
    }
}
