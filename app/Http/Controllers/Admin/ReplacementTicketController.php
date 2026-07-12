<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsentLog;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Services\Blink\BlinkService;
use App\Services\DCB\DCBFactory;
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

    public function store(Request $request)
    {
        $request->validate([
            'msisdn' => ['required'],
            'qty'    => ['required', 'integer', 'min:1', 'max:10'],
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

        try {
            $result = DB::transaction(function () use ($phone, $qty, $operator) {
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

                $txn = Transaction::create([
                    'txn_ref'      => $txnRef,
                    'ticket_id'    => $tickets->first()->id,
                    'ticket_ids'   => $ticketIds,
                    'phone'        => $phone,
                    'operator'     => $operator,
                    'amount'       => $qty * 20,
                    'qty'          => $qty,
                    'status'       => 'success',
                    'confirmed_at' => now(),
                ]);

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

        $ticketNos   = $tickets->pluck('ticket_no')->implode(', ');
        $downloadUrl = route('ticket.download-all-pdf', ['phone' => $phone]);
        $message     = "প্রিয় গ্রাহক, আপনার নতুন বৈধ টিকিট নম্বর: {$ticketNos}। অনুগ্রহ করে এই নম্বরটিই আপনার অফিসিয়াল টিকিট হিসেবে ব্যবহার করুন। আপনার সহযোগিতার জন্য ধন্যবাদ। – BPKS\n\nDownload: {$downloadUrl}";

        try {
            $sent = (new BlinkService())->sendSms($phone, $message, $txn->txn_ref);
            ConsentLog::record($txn->txn_ref, $phone, $sent ? 'sms_sent' : 'sms_failed', ['ticket_nos' => $ticketNos]);
        } catch (\Throwable $e) {
            Log::error('Replacement ticket SMS error', ['txn' => $txn->txn_ref, 'err' => $e->getMessage()]);
            ConsentLog::record($txn->txn_ref, $phone, 'sms_failed', null, $e->getMessage());
        }

        return redirect()->route('admin.replacement-tickets.index')
            ->with('success', "রিপ্লেসমেন্ট টিকেট সফলভাবে ইস্যু হয়েছে: {$txn->txn_ref} | টিকেট: {$ticketNos}");
    }
}
