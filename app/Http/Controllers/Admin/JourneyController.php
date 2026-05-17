<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsentLog;
use App\Models\SmsLog;
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

        return view('admin.journey.index', compact('transactions'));
    }

    public function show(Transaction $transaction)
    {
        $steps  = ConsentLog::where('txn_ref', $transaction->txn_ref)->orderBy('created_at')->get();
        $smsLog = SmsLog::where('txn_ref', $transaction->txn_ref)->first();

        return view('admin.journey.show', compact('transaction', 'steps', 'smsLog'));
    }
}
