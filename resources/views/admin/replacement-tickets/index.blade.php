@extends('admin.layouts.app')
@section('title', 'রিপ্লেসমেন্ট টিকেট')
@section('page-title', 'রিপ্লেসমেন্ট টিকেট ইস্যু')

@section('content')

{{-- Issue Form --}}
<div class="row mb-4">
  <div class="col-lg-5">
    <div class="card" style="border-radius:1rem;border:none;">
      <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="fw-bold mb-0"><i class="fas fa-exchange-alt me-2 text-warning"></i>নতুন রিপ্লেসমেন্ট টিকেট ইস্যু</h6>
      </div>
      <div class="card-body p-4">
        <p class="text-muted small mb-4">
          <i class="fas fa-info-circle me-1 text-primary"></i>
          স্টেজিং সার্ভার থেকে কেনা টিকেটের বদলে প্রোডাকশনের বৈধ টিকেট ইস্যু করুন।
          অপারেটর স্বয়ংক্রিয়ভাবে MSISDN থেকে শনাক্ত হবে।
        </p>

        @if($errors->any())
          <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
              @foreach($errors->all() as $e)<li class="small">{{ $e }}</li>@endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ route('admin.replacement-tickets.store') }}" id="replaceForm">
          @csrf

          <div class="mb-3">
            <label class="form-label fw-semibold">MSISDN (মোবাইল নম্বর) <span class="text-danger">*</span></label>
            <input type="text" name="msisdn" class="form-control form-control-lg"
                   value="{{ old('msisdn') }}" placeholder="01XXXXXXXXX"
                   inputmode="tel" maxlength="15" required autofocus>
            <div class="form-text">অপারেটর স্বয়ংক্রিয়ভাবে শনাক্ত হবে (017→GP, 018/016→Robi, 019/014→BL, 015→TT)</div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">টিকেট সংখ্যা <span class="text-danger">*</span></label>
            <select name="qty" class="form-select form-select-lg" required>
              @for($i = 1; $i <= 10; $i++)
                <option value="{{ $i }}" {{ old('qty', 1) == $i ? 'selected' : '' }}>{{ $i }}টি</option>
              @endfor
            </select>
          </div>

          <button type="submit" class="btn btn-warning w-100 py-2 fw-bold text-dark" id="submitBtn"
                  onclick="return confirm('এই গ্রাহককে রিপ্লেসমেন্ট টিকেট ইস্যু করবেন?')">
            <i class="fas fa-ticket-alt me-2"></i> রিপ্লেসমেন্ট টিকেট ইস্যু করুন
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- Report Table --}}
<div class="card" style="border-radius:1rem;border:none;">
  <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
    <h6 class="fw-bold mb-0"><i class="fas fa-list me-2 text-primary"></i>ইস্যু করা রিপ্লেসমেন্ট টিকেট</h6>
    <span class="badge bg-secondary">মোট: {{ $transactions->total() }}</span>
  </div>

  @if($transactions->isEmpty())
    <div class="card-body text-center text-muted py-5">
      <i class="fas fa-inbox fa-2x mb-2"></i>
      <p class="mb-0">এখনো কোনো রিপ্লেসমেন্ট টিকেট ইস্যু হয়নি।</p>
    </div>
  @else
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th class="px-3">তারিখ</th>
              <th>ফোন</th>
              <th>অপারেটর</th>
              <th>টিকেট নম্বর</th>
              <th>পরিমাণ</th>
              <th>TXN Ref</th>
              <th>SMS</th>
            </tr>
          </thead>
          <tbody>
            @foreach($transactions as $txn)
            <tr>
              <td class="px-3">
                <div class="small">{{ $txn->confirmed_at?->format('d M Y') ?? '—' }}</div>
                <div class="text-muted" style="font-size:.75rem">{{ $txn->confirmed_at?->format('h:i A') }}</div>
              </td>
              <td><code>{{ $txn->phone }}</code></td>
              <td>
                <span class="badge bg-{{ match($txn->operator) {
                  'Grameenphone' => 'success',
                  'Banglalink'   => 'danger',
                  'Robi'         => 'warning text-dark',
                  'Teletalk'     => 'info text-dark',
                  default        => 'secondary',
                } }}">{{ $txn->operator }}</span>
              </td>
              <td>
                @foreach($txn->resolved_ticket_nos ?? [] as $no)
                  <span class="badge bg-light text-dark border me-1" style="font-size:.75rem">{{ $no }}</span>
                @endforeach
              </td>
              <td>৳{{ number_format($txn->amount, 2) }}</td>
              <td><code style="font-size:.72rem">{{ $txn->txn_ref }}</code></td>
              <td>
                @if($txn->smsLog && strtolower($txn->smsLog->response ?? '') === 'sent')
                  <span class="badge bg-success"><i class="fas fa-check me-1"></i>পাঠানো হয়েছে</span>
                @else
                  <div class="d-flex flex-column gap-1">
                    @if($txn->smsLog)
                      <span class="badge bg-danger"><i class="fas fa-times me-1"></i>ব্যর্থ</span>
                    @else
                      <span class="badge bg-secondary">পাঠানো হয়নি</span>
                    @endif
                    <form method="POST" action="{{ route('admin.replacement-tickets.resend-sms', $txn->id) }}">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-outline-primary" style="font-size:.72rem;">
                        <i class="fas fa-paper-plane me-1"></i>SMS পাঠান
                      </button>
                    </form>
                  </div>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @if($transactions->hasPages())
    <div class="card-footer bg-white border-top py-3 px-4">
      {{ $transactions->links() }}
    </div>
    @endif
  @endif
</div>

@endsection

@push('scripts')
<script>
document.getElementById('replaceForm').addEventListener('submit', function(e) {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> প্রক্রিয়াকরণ হচ্ছে...';
});
</script>
@endpush
