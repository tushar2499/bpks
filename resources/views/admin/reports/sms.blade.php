@extends('admin.layouts.app')
@section('title', 'SMS ব্যর্থতা রিপোর্ট')
@section('page-title', 'SMS ব্যর্থতা রিপোর্ট')

@push('styles')
<style>
.stat-pill {
  border-radius: 1rem; padding: .9rem 1.2rem; text-align: center;
  background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.08);
}
.stat-pill .num  { font-size: 1.7rem; font-weight: 800; line-height: 1; }
.stat-pill .lbl  { font-size: .72rem; color: #64748b; margin-top: .2rem; }
.sbadge { font-size: .72rem; padding: .28em .6em; border-radius: .4rem; font-weight: 700; }
.s-not-sent { background: #fef3c7; color: #92400e; }
.s-failed   { background: #fee2e2; color: #991b1b; }
.s-sent     { background: #d1fae5; color: #065f46; }
</style>
@endpush

@section('content')

{{-- Summary stats --}}
<div class="row g-3 mb-3">
  <div class="col-4">
    <div class="stat-pill">
      <div class="num text-success">{{ number_format($totalSuccess) }}</div>
      <div class="lbl">সফল পেমেন্ট</div>
    </div>
  </div>
  <div class="col-4">
    <div class="stat-pill">
      <div class="num text-warning">{{ number_format($totalNoSms) }}</div>
      <div class="lbl">SMS পাঠানো হয়নি</div>
    </div>
  </div>
  <div class="col-4">
    <div class="stat-pill">
      <div class="num text-danger">{{ number_format($totalFailed) }}</div>
      <div class="lbl">SMS ব্যর্থ</div>
    </div>
  </div>
</div>

{{-- Filters --}}
<div class="card mb-3 border-0 shadow-sm">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small mb-1">ফোন</label>
        <input type="text" name="phone" class="form-control form-control-sm"
               value="{{ request('phone') }}" placeholder="01XXXXXXXXX">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">SMS স্ট্যাটাস</label>
        <select name="sms_status" class="form-select form-select-sm">
          <option value="">ব্যর্থ সব (ডিফল্ট)</option>
          <option value="not_sent" @selected(request('sms_status')=='not_sent')>পাঠানো হয়নি</option>
          <option value="failed"   @selected(request('sms_status')=='failed')>ব্যর্থ</option>
          <option value="sent"     @selected(request('sms_status')=='sent')>সফল সব দেখুন</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">তারিখ থেকে</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">তারিখ পর্যন্ত</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>ফিল্টার</button>
        <a href="{{ route('admin.reports.sms') }}" class="btn btn-outline-secondary btn-sm">রিসেট</a>
      </div>
    </form>
  </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
    <span class="fw-semibold">{{ $transactions->total() }} টি রেকর্ড</span>
    <small class="text-muted">পৃষ্ঠা {{ $transactions->currentPage() }} / {{ $transactions->lastPage() }}</small>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th class="ps-3">তারিখ</th>
          <th>ফোন</th>
          <th>অপারেটর</th>
          <th>টিকেট নং</th>
          <th>TXN REF</th>
          <th>SMS স্ট্যাটাস</th>
          <th>SMS রেসপন্স</th>
          <th class="pe-3">অ্যাকশন</th>
        </tr>
      </thead>
      <tbody>
        @forelse($transactions as $txn)
        @php
          $hasSms   = $txn->smsLog !== null;
          $smsStatus = $txn->smsLog->status_message ?? '';
          $smsOk     = $hasSms && !in_array($smsStatus, ['Failed', 'Unknown', '']) && $smsStatus !== '';
          $badgeClass = !$hasSms ? 's-not-sent' : ($smsOk ? 's-sent' : 's-failed');
          $badgeText  = !$hasSms ? 'পাঠানো হয়নি' : ($smsOk ? 'সফল' : 'ব্যর্থ');
        @endphp
        <tr>
          <td class="ps-3 text-nowrap small">{{ $txn->confirmed_at?->format('d M Y H:i') ?? $txn->created_at->format('d M Y H:i') }}</td>
          <td class="fw-semibold small">{{ $txn->phone }}</td>
          <td class="small">{{ $txn->operator }}</td>
          <td class="font-monospace small fw-bold">
            @if(!empty($txn->resolved_ticket_nos))
              {{ implode(', ', $txn->resolved_ticket_nos) }}
            @else
              —
            @endif
          </td>
          <td class="font-monospace" style="font-size:.68rem;">{{ $txn->txn_ref }}</td>
          <td><span class="sbadge {{ $badgeClass }}">{{ $badgeText }}</span></td>
          <td class="small text-muted" style="max-width:180px;word-break:break-word;">
            {{ $txn->smsLog->status_message ?? '—' }}
          </td>
          <td class="pe-3">
            @if(!$smsOk)
              <form method="POST" action="{{ route('admin.reports.sms.retry', $txn) }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning py-0 px-2"
                        onclick="return confirm('{{ $txn->phone }} নম্বরে SMS পাঠাবেন?')"
                        title="SMS পুনরায় পাঠান">
                  <i class="fas fa-paper-plane me-1"></i>পাঠান
                </button>
              </form>
            @else
              <span class="text-success small"><i class="fas fa-check"></i></span>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted py-5">
          <i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>সব গ্রাহক SMS পেয়েছেন
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($transactions->hasPages())
  <div class="card-footer bg-white py-2">
    {{ $transactions->links('pagination::bootstrap-5') }}
  </div>
  @endif
</div>
@endsection
