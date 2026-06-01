@extends('admin.layouts.app')
@section('title', 'দৈনিক রিপোর্ট')
@section('page-title', 'দৈনিক বিক্রয় রিপোর্ট')

@push('styles')
<style>
.filter-card { border-radius: 1rem; border: none; box-shadow: 0 1px 6px rgba(0,0,0,.07); }
.summary-pill { background:#fff; border-radius:.75rem; padding:.6rem 1rem; box-shadow:0 1px 4px rgba(0,0,0,.08); text-align:center; }
.summary-pill .num { font-size:1.5rem; font-weight:800; line-height:1; }
.summary-pill .lbl { font-size:.72rem; color:#64748b; margin-top:.2rem; }
.tfoot-row td { font-weight:800; background:#f8fafc; border-top:2px solid #e2e8f0; }
</style>
@endpush

@section('content')

{{-- Filters --}}
<div class="card filter-card mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      @if(!$opFilter)
      <div class="col-md-2">
        <label class="form-label small mb-1">অপারেটর</label>
        <select name="operator" class="form-select form-select-sm">
          <option value="">সব</option>
          @foreach($operators as $op)
            <option value="{{ $op }}" @selected(request('operator') === $op)>{{ $op }}</option>
          @endforeach
        </select>
      </div>
      @endif
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
        <a href="{{ route('admin.reports.daily') }}" class="btn btn-outline-secondary btn-sm">রিসেট</a>
      </div>
    </form>
  </div>
</div>

{{-- Summary pills --}}
@if($rows->isNotEmpty())
<div class="row g-3 mb-3">
  <div class="col-4">
    <div class="summary-pill">
      <div class="num text-primary">{{ number_format($totals['txn_count']) }}</div>
      <div class="lbl">মোট লেনদেন</div>
    </div>
  </div>
  <div class="col-4">
    <div class="summary-pill">
      <div class="num text-success">{{ number_format($totals['ticket_count']) }}</div>
      <div class="lbl">মোট টিকেট</div>
    </div>
  </div>
  <div class="col-4">
    <div class="summary-pill">
      <div class="num text-purple" style="color:#7c3aed;">৳{{ number_format($totals['total_amount'], 0) }}</div>
      <div class="lbl">মোট আয়</div>
    </div>
  </div>
</div>
@endif

{{-- Table --}}
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
    <span class="fw-semibold">{{ $rows->count() }} টি রেকর্ড</span>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th class="ps-3">তারিখ</th>
          <th>অপারেটর</th>
          <th class="text-center">লেনদেন</th>
          <th class="text-center">টিকেট</th>
          <th class="text-end">আয় (৳)</th>
          <th class="pe-3 text-center">বিবরণ</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $row)
        <tr>
          <td class="ps-3 small text-nowrap">{{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}</td>
          <td class="small">{{ $row->operator }}</td>
          <td class="text-center fw-semibold">{{ number_format($row->txn_count) }}</td>
          <td class="text-center fw-semibold text-success">{{ number_format($row->ticket_count) }}</td>
          <td class="text-end fw-semibold">{{ number_format($row->total_amount, 2) }}</td>
          <td class="pe-3 text-center">
            <button class="btn btn-sm btn-outline-primary py-0 px-2 btn-detail"
                    data-date="{{ $row->date }}"
                    data-operator="{{ $row->operator }}">
              <i class="fas fa-list me-1"></i>বিবরণ
            </button>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="text-center text-muted py-5">
            <i class="fas fa-inbox fa-2x d-block mb-2 opacity-25"></i>কোনো ডেটা নেই
          </td>
        </tr>
        @endforelse
      </tbody>
      @if($rows->isNotEmpty())
      <tfoot>
        <tr class="tfoot-row">
          <td class="ps-3" colspan="2">মোট</td>
          <td class="text-center">{{ number_format($totals['txn_count']) }}</td>
          <td class="text-center text-success">{{ number_format($totals['ticket_count']) }}</td>
          <td class="text-end">{{ number_format($totals['total_amount'], 2) }}</td>
          <td></td>
        </tr>
      </tfoot>
      @endif
    </table>
  </div>
</div>

{{-- Detail Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;">
        <h6 class="modal-title fw-bold mb-0" id="detailModalTitle">বিবরণ</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div id="detailLoading" class="text-center py-5 text-muted">
          <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block"></i>লোড হচ্ছে...
        </div>
        <div id="detailContent" style="display:none;">
          <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th class="ps-3">সময়</th>
                <th>ফোন</th>
                <th>TXN REF</th>
                <th class="text-center">টিকেট</th>
                <th>টিকেট নম্বর</th>
                <th class="text-end">আয় (৳)</th>
                <th class="pe-3 text-center">SMS</th>
              </tr>
            </thead>
            <tbody id="detailBody"></tbody>
            <tfoot>
              <tr class="tfoot-row">
                <td class="ps-3" colspan="3">মোট</td>
                <td class="text-center" id="detailTotalQty"></td>
                <td></td>
                <td class="text-end" id="detailTotalAmount"></td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
const detailUrl   = '{{ route('admin.reports.daily-detail') }}';
const modal       = new bootstrap.Modal(document.getElementById('detailModal'));

document.querySelectorAll('.btn-detail').forEach(btn => {
  btn.addEventListener('click', function() {
    const date     = this.dataset.date;
    const operator = this.dataset.operator;

    document.getElementById('detailModalTitle').textContent = date + ' — ' + operator;
    document.getElementById('detailLoading').style.display  = '';
    document.getElementById('detailContent').style.display  = 'none';
    modal.show();

    fetch(detailUrl + '?date=' + encodeURIComponent(date) + '&operator=' + encodeURIComponent(operator))
      .then(r => r.json())
      .then(rows => {
        let html = '', totalQty = 0, totalAmt = 0;
        rows.forEach(r => {
          totalQty += r.qty || 0;
          totalAmt += parseFloat(r.amount.replace(/,/g,'')) || 0;
          const tickets = (r.ticket_nos || []).map(n =>
            `<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1" style="font-size:.68rem;">${n}</span>`
          ).join('');
          const smsOk = r.sms && r.sms !== '—' && r.sms !== 'Failed';
          html += `<tr>
            <td class="ps-3 small text-nowrap">${r.confirmed}</td>
            <td class="fw-semibold small">${r.phone}</td>
            <td class="font-monospace" style="font-size:.68rem;">${r.txn_ref}</td>
            <td class="text-center">${r.qty}</td>
            <td>${tickets || '<span class="text-muted">—</span>'}</td>
            <td class="text-end">৳${r.amount}</td>
            <td class="pe-3 text-center">
              <span class="badge ${smsOk ? 'bg-success' : 'bg-secondary'}" style="font-size:.65rem;">${r.sms}</span>
            </td>
          </tr>`;
        });
        document.getElementById('detailBody').innerHTML = html || '<tr><td colspan="7" class="text-center text-muted py-3">কোনো ডেটা নেই</td></tr>';
        document.getElementById('detailTotalQty').textContent    = totalQty;
        document.getElementById('detailTotalAmount').textContent = '৳' + totalAmt.toLocaleString('en', {minimumFractionDigits:2});
        document.getElementById('detailLoading').style.display   = 'none';
        document.getElementById('detailContent').style.display   = '';
      });
  });
});
</script>
@endpush
