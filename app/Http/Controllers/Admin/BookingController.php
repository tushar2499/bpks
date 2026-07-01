<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsentLog;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Services\DCB\DCBFactory;
use App\Services\SMS\RobiSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function index()
    {
        return view('admin.booking.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'regex:/^(\+?880|0)?1[3-9]\d{8}$/'],
            'qty'   => ['required', 'integer', 'min:1', 'max:10'],
        ], [
            'phone.required' => 'মোবাইল নম্বর দিন।',
            'phone.regex'    => 'বৈধ বাংলাদেশী নম্বর দিন।',
            'qty.required'   => 'টিকেট সংখ্যা দিন।',
            'qty.min'        => 'কমপক্ষে ১টি টিকেট।',
            'qty.max'        => 'সর্বোচ্চ ১০টি টিকেট।',
        ]);

        $phone    = $this->normalizePhone($request->phone);
        $qty      = (int) $request->qty;
        $operator = DCBFactory::detectOperator($phone);

        if (!$operator) {
            return back()->withErrors(['phone' => 'অপারেটর সনাক্ত হয়নি। সঠিক নম্বর দিন।'])->withInput();
        }

        try {
            $transaction = DB::transaction(function () use ($phone, $qty, $operator) {
                $tickets = Ticket::where('status', 0)
                    ->orderBy('id')
                    ->limit($qty)
                    ->lockForUpdate()
                    ->get();

                if ($tickets->count() < $qty) {
                    throw new \RuntimeException("পর্যাপ্ত টিকেট নেই। পাওয়া গেছে: {$tickets->count()}টি।");
                }

                $ticketIds   = $tickets->pluck('id')->toArray();
                $totalAmount = $tickets->sum('sell_price');
                $txnRef      = 'MBKG' . strtoupper(Str::random(13));

                $transaction = Transaction::create([
                    'txn_ref'      => $txnRef,
                    'ticket_id'    => $tickets->first()->id,
                    'ticket_ids'   => $ticketIds,
                    'phone'        => $phone,
                    'operator'     => $operator,
                    'amount'       => $totalAmount,
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

                ConsentLog::record($txnRef, $phone, 'manual_booking', [
                    'ticket_ids'   => $ticketIds,
                    'ticket_nos'   => $tickets->pluck('ticket_no')->toArray(),
                    'admin_id'     => auth()->id(),
                    'admin_name'   => auth()->user()->name ?? null,
                    'operator'     => $operator,
                    'qty'          => $qty,
                    'amount'       => $totalAmount,
                ]);

                return $transaction;
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['qty' => $e->getMessage()])->withInput();
        } catch (\Throwable $e) {
            Log::error('Manual booking failed', ['phone' => $phone, 'qty' => $qty, 'err' => $e->getMessage()]);
            return back()->withErrors(['phone' => 'বুকিং ব্যর্থ হয়েছে। আবার চেষ্টা করুন।'])->withInput();
        }

        // Send SMS
        $this->sendSms($transaction);

        $ticketNos = Transaction::find($transaction->id)
            ->ticket_ids ?? [$transaction->ticket_id];
        $tickets = Ticket::whereIn('id', array_filter((array) $ticketNos))->pluck('ticket_no')->implode(', ');

        return back()->with('success',
            "বুকিং সফল! টিকেট: {$tickets} | রেফারেন্স: {$transaction->txn_ref}"
        );
    }

    private function sendSms(Transaction $transaction): void
    {
        $ids       = $transaction->ticket_ids ?? [$transaction->ticket_id];
        $tickets   = Ticket::whereIn('id', array_filter((array) $ids))->get();
        $ticketNos = $tickets->pluck('ticket_no')->implode(', ');
        $amount    = number_format($transaction->amount, 2);
        $txnRef    = $transaction->txn_ref;

        $message = "প্রিয় গ্রাহক, আপনার BPKS লটারি টিকেট বুকিং সম্পন্ন হয়েছে।"
                 . " টিকেট নম্বর: {$ticketNos}।"
                 . " মূল্য: ৳{$amount}।"
                 . " লেনদেন: {$txnRef} | হেল্পলাইন: +8801725298711";

        try {
            $sms  = new RobiSmsService();
            $sent = $sms->send($transaction->phone, $message, $txnRef);
            $step = $sent ? 'sms_sent' : 'sms_failed';
            $note = $sent ? null : 'SMS service returned false';
        } catch (\Throwable $e) {
            Log::error('Manual booking SMS error', ['txn' => $txnRef, 'err' => $e->getMessage()]);
            $step = 'sms_failed';
            $note = $e->getMessage();
        }

        ConsentLog::record($txnRef, $transaction->phone, $step, ['ticket_nos' => $ticketNos], $note);
    }

    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) === 10 && $clean[0] === '1') return '0' . $clean;
        if (strlen($clean) === 13 && str_starts_with($clean, '880')) return '0' . substr($clean, 3);
        return $clean;
    }
}
