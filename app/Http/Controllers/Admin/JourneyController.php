<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsentLog;
use App\Models\SmsLog;
use App\Models\Ticket;
use App\Models\Transaction;
use Illuminate\Http\Request;

class JourneyController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['ticket', 'consentLogs', 'smsLog'])
            ->where('operator', 'Robi')
            ->orderByDesc('created_at');

        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(25)->withQueryString();

        // Batch-load all tickets for multi-ticket transactions
        $allIds = $transactions->flatMap(fn($t) => $t->ticket_ids ?? array_filter([$t->ticket_id]))->unique()->filter();
        $ticketsById = Ticket::whereIn('id', $allIds)->pluck('ticket_no', 'id');

        // Attach resolved ticket numbers to each transaction as a transient property
        foreach ($transactions as $txn) {
            $ids = $txn->ticket_ids ?? array_filter([$txn->ticket_id]);
            $txn->resolved_ticket_nos = collect($ids)->map(fn($id) => $ticketsById[$id] ?? null)->filter()->values()->all();
        }

        return view('admin.journey.index', compact('transactions'));
    }

    public function show(Transaction $transaction)
    {
        $steps  = ConsentLog::where('txn_ref', $transaction->txn_ref)->orderBy('created_at')->get();
        $smsLog = SmsLog::where('txn_ref', $transaction->txn_ref)->first();

        return view('admin.journey.show', compact('transaction', 'steps', 'smsLog'));
    }
}
