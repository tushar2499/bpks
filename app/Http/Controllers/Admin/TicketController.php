<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::query();

        if ($request->filled('status') && in_array($request->status, ['0', '1'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('operator')) {
            $query->where('operator', $request->operator);
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_no', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('ticket_no')->paginate(50)->withQueryString();

        return view('admin.tickets.index', compact('tickets'));
    }

    public function generateForm()
    {
        return view('admin.tickets.generate');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'operator'     => 'required|in:Grameenphone,Robi,Banglalink,Teletalk',
            'prefix'       => 'required|string|max:10',
            'start_number' => 'required|regex:/^[0-9]+$/|min:1',
            'count'        => 'required|integer|min:1|max:100000',
        ]);

        $prefix    = strtoupper(trim($request->prefix));
        $startRaw  = $request->start_number;          // preserve leading zeros string
        $start     = (int) $startRaw;
        $count     = (int) $request->count;
        $operator  = $request->operator;
        $padLen    = max(strlen($startRaw), strlen((string) ($start + $count - 1)));
        $now       = now()->toDateTimeString();

        $tickets = [];
        for ($i = 0; $i < $count; $i++) {
            $tickets[] = [
                'ticket_no'  => $prefix . str_pad($start + $i, $padLen, '0', STR_PAD_LEFT),
                'operator'   => $operator,
                'status'     => 0,
                'sell_price' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $inserted = 0;
        $skipped  = 0;
        foreach (array_chunk($tickets, 1000) as $chunk) {
            $result    = DB::table('tickets')->insertOrIgnore($chunk);
            $inserted += $result;
            $skipped  += count($chunk) - $result;
        }

        TicketBatch::create([
            'prefix'       => $prefix,
            'start_number' => $start,
            'count'        => $count,
            'created_by'   => Auth::id(),
        ]);

        $msg = number_format($inserted) . ' টিকেট সফলভাবে তৈরি হয়েছে।';
        if ($skipped > 0) {
            $msg .= ' ' . number_format($skipped) . ' টি ডুপ্লিকেট এড়ানো হয়েছে।';
        }

        return redirect()->route('admin.tickets.index')->with('success', $msg);
    }

    public function sell(Request $request, Ticket $ticket)
    {
        if ($ticket->status === 1) {
            return back()->with('error', 'এই টিকেটটি আগেই বিক্রি হয়েছে।');
        }

        $request->validate([
            'phone'    => 'required|string|max:15',
            'operator' => 'nullable|string|max:20',
        ]);

        $ticket->update([
            'status'   => 1,
            'phone'    => $request->phone,
            'operator' => $request->operator,
            'sold_at'  => now(),
        ]);

        return back()->with('success', "টিকেট {$ticket->ticket_no} সফলভাবে বিক্রি হয়েছে।");
    }

    public function destroy(Ticket $ticket)
    {
        if ($ticket->status === 1) {
            return back()->with('error', 'বিক্রিত টিকেট মুছতে পারবেন না।');
        }

        $ticket->delete();
        return back()->with('success', 'টিকেট মুছে দেওয়া হয়েছে।');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $deleted = Ticket::whereIn('id', $request->ids)
            ->where('status', 0)
            ->delete();

        return back()->with('success', number_format($deleted) . ' টি অবিক্রীত টিকেট মুছে দেওয়া হয়েছে।');
    }
}
