<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsentLog;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Services\Blink\BlinkService;
use App\Services\DCB\DCBFactory;
use App\Services\DCB\GpConsentService;
use App\Services\SMS\RobiSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReplacementTicketController extends Controller
{
    public function index()
    {
        $transactions = Transaction::where('txn_ref', 'LIKE', 'RPLC%')
            ->with(['smsLog'])
            ->orderByDesc('confirmed_at')
            ->paginate(50);

        $allTicketIds = $transactions->flatMap(fn($t) => $t->ticket_ids ?? array_filter([$t->ticket_id]))->unique()->filter();
        $ticketsById  = Ticket::whereIn('id', $allTicketIds)->pluck('ticket_no', 'id');

        foreach ($transactions as $txn) {
            $ids = $txn->ticket_ids ?? array_filter([$txn->ticket_id]);
            $txn->resolved_ticket_nos = collect($ids)->map(fn($id) => $ticketsById[$id] ?? null)->filter()->values()->all();
        }

        return view('admin.replacement-tickets.index', compact('transactions'));
    }

    public function lookupAcr(Request $request)
    {
        $phone = preg_replace('/\D/', '', trim($request->query('phone', '')));
        if (strlen($phone) === 13 && str_starts_with($phone, '880')) {
            $phone = '0' . substr($phone, 3);
        }

        $acr = Transaction::where('phone', $phone)
            ->where('operator', 'Grameenphone')
            ->whereNotNull('gp_customer_ref')
            ->orderByDesc('id')
            ->value('gp_customer_ref');

        return response()->json(['acr' => $acr]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'msisdn' => ['required'],
            'qty'    => ['required', 'integer', 'min:1', 'max:10'],
            'acr'    => ['nullable', 'string'],
        ]);

        $phone = preg_replace('/\D/', '', trim($request->msisdn));
        if (strlen($phone) === 13 && str_starts_with($phone, '880')) {
            $phone = '0' . substr($phone, 3);
        }

        $operator = DCBFactory::detectOperator($phone);
        if (!$operator) {
            return back()->withErrors(['msisdn' => 'অপারেটর শনাক্ত করা যায়নি। বৈধ বাংলাদেশী নম্বর দিন।'])->withInput();
        }

        $qty = (int) $request->qty;

        $acr = $operator === 'Grameenphone' ? trim($request->acr ?? '') : null;

        try {
            $result = DB::transaction(function () use ($phone, $qty, $operator, $acr) {
                $tier = DB::table('tickets')
                    ->where('operator', $operator)
                    ->where('status', 0)
                    ->whereNotNull('series')
                    ->min('sale_tier');

                if ($tier === null) return null;

                $candidateIds = Ticket::where('operator', $operator)
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

                $ticketIds = $tickets->pluck('id')->toArray();
                $txnRef    = 'RPLC' . strtoupper(Str::random(12));

                $txn = Transaction::create(array_filter([
                    'txn_ref'          => $txnRef,
                    'ticket_id'        => $tickets->first()->id,
                    'ticket_ids'       => $ticketIds,
                    'phone'            => $phone,
                    'operator'         => $operator,
                    'amount'           => $qty * 20,
                    'qty'              => $qty,
                    'status'           => 'success',
                    'confirmed_at'     => now(),
                    'gp_customer_ref'  => $acr ?: null,
                ]));

                Ticket::whereIn('id', $ticketIds)->update([
                    'status'   => 1,
                    'phone'    => $phone,
                    'operator' => $operator,
                    'sold_at'  => now(),
                ]);

                return ['txn' => $txn, 'tickets' => $tickets];
            });
        } catch (\Throwable $e) {
            Log::error('Replacement ticket failed', ['phone' => $phone, 'err' => $e->getMessage()]);
            return back()->withErrors(['msisdn' => 'সিস্টেম ত্রুটি: ' . $e->getMessage()])->withInput();
        }

        if (!$result) {
            return back()->withErrors(['msisdn' => 'পর্যাপ্ত ' . $operator . ' টিকেট নেই।'])->withInput();
        }

        $txn     = $result['txn'];
        $tickets = $result['tickets'];

        /** @var \App\Models\User $admin */
        $admin = auth()->user();
        ConsentLog::record($txn->txn_ref, $phone, 'replacement_ticket_issued', [
            'ticket_ids' => $txn->ticket_ids,
            'operator'   => $operator,
            'by'         => $admin?->name,
        ]);

        $ticketNos = $tickets->pluck('ticket_no')->implode(', ');
        $this->sendReplacementSms($txn, $ticketNos);

        return redirect()->route('admin.replacement-tickets.index')
            ->with('success', "রিপ্লেসমেন্ট টিকেট সফলভাবে ইস্যু হয়েছে: {$txn->txn_ref} | টিকেট: {$ticketNos}");
    }

    public function resendSms(Request $request, Transaction $transaction)
    {
        $ids     = $transaction->ticket_ids ?? array_filter([$transaction->ticket_id]);
        $tickets = Ticket::whereIn('id', $ids)->get();

        if ($tickets->isEmpty()) {
            return back()->with('error', 'টিকেট তথ্য পাওয়া যায়নি।');
        }

        // If ACR supplied via form for GP, store it so future lookups succeed
        if ($transaction->operator === 'Grameenphone' && $request->filled('acr')) {
            $transaction->update(['gp_customer_ref' => trim($request->acr)]);
        }

        $ticketNos = $tickets->pluck('ticket_no')->implode(', ');
        $sent = $this->sendReplacementSms($transaction->fresh(), $ticketNos, true);

        return $sent
            ? back()->with('success', 'SMS পুনরায় পাঠানো হয়েছে: ' . $transaction->txn_ref)
            : back()->with('error', 'SMS পাঠানো ব্যর্থ হয়েছে।');
    }

    private function sendReplacementSms(Transaction $transaction, string $ticketNos, bool $retry = false): bool
    {
        $phone       = $transaction->phone;
        $downloadUrl = route('ticket.download-all-pdf', ['phone' => $phone]);
        $message     = "প্রিয় গ্রাহক, আপনার নতুন বৈধ টিকিট নম্বর: {$ticketNos}। অনুগ্রহ করে এই নম্বরটিই আপনার অফিসিয়াল টিকিট হিসেবে ব্যবহার করুন। আপনার সহযোগিতার জন্য ধন্যবাদ। – BPKS\n\nDownload: {$downloadUrl}";

        $sent = false;

        try {
            if ($transaction->operator === 'Grameenphone') {
                $acr = Transaction::where('phone', $phone)
                    ->where('operator', 'Grameenphone')
                    ->whereNotNull('gp_customer_ref')
                    ->orderByDesc('id')
                    ->value('gp_customer_ref');

                $sent = $acr
                    ? (new GpConsentService())->sendSms($acr, $phone, $message, $transaction->txn_ref)
                    : false;
                $note = $sent ? null : ($acr ? 'GP SMS failed' : 'GP SMS skipped — no ACR found');
            } else {
                $sent = match ($transaction->operator) {
                    'Banglalink' => (new BlinkService())->sendSms($phone, $message, $transaction->txn_ref),
                    default      => (new RobiSmsService())->send($phone, $message, $transaction->txn_ref),
                };
                $note = $sent ? null : 'SMS service returned false';
            }

            $step = $sent ? 'sms_sent' : 'sms_failed';
        } catch (\Throwable $e) {
            Log::error('Replacement ticket SMS error', ['txn' => $transaction->txn_ref, 'err' => $e->getMessage()]);
            $step = 'sms_failed';
            $note = $e->getMessage();
        }

        // Normalize smsLog response so the view can check === 'sent' regardless of operator
        \App\Models\SmsLog::where('txn_ref', $transaction->txn_ref)
            ->update(['response' => $sent ? 'sent' : 'failed']);

        ConsentLog::record($transaction->txn_ref, $phone, $step, ['ticket_nos' => $ticketNos, 'retry' => $retry], $note ?? null);

        return $sent;
    }
}
