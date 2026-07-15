@extends('admin.layouts.app')
@section('title', 'রিচার্জ ইম্পোর্ট')
@section('page-title', 'রিচার্জ ইম্পোর্ট')

@section('content')

{{-- Flash Messages --}}
@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show py-2 mb-3">
    <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif
@if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
    <i class="fas fa-times-circle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

{{-- Upload Card --}}
<div class="row mb-4">
  <div class="col-lg-5">
    <div class="card" style="border-radius:1rem;border:none;">
      <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="fw-bold mb-0"><i class="fas fa-upload me-2 text-primary"></i>CSV ফাইল আপলোড করুন</h6>
      </div>
      <div class="card-body p-4">
        @if($errors->any())
          <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
              @foreach($errors->all() as $e)<li class="small">{{ $e }}</li>@endforeach
            </ul>
          </div>
        @endif

        <p class="text-muted small mb-3">
          <i class="fas fa-info-circle me-1 text-primary"></i>
          GP রিচার্জ লগ CSV ফাইল আপলোড করুন। MSISDN + Invoice No দিয়ে ডুপ্লিকেট স্বয়ংক্রিয়ভাবে এড়িয়ে যাবে।
        </p>

        <form method="POST" action="{{ route('admin.recharge-imports.upload') }}" enctype="multipart/form-data">
          @csrf
          <div class="mb-3">
            <label class="form-label fw-semibold">CSV ফাইল <span class="text-danger">*</span></label>
            <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
            <div class="form-text text-muted" style="font-size:.72rem">
              Columns: trx_time, msisdn, invoice_no, dob_msisdn, dob_amount, sof_status, ers_status, dob_status, remarks, ticket count
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
            <i class="fas fa-upload me-2"></i>আপলোড করুন
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- Data Table --}}
<div class="card" style="border-radius:1rem;border:none;">
  <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
    <h6 class="fw-bold mb-0"><i class="fas fa-list me-2 text-primary"></i>ইম্পোর্ট করা রিচার্জ লগ</h6>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-secondary">মোট: {{ $imports->total() }}</span>
      <span class="badge bg-warning text-dark">
        অপেক্ষমাণ: {{ $imports->getCollection()->where('ticket_status', 0)->count() }} (এই পেজে)
      </span>
    </div>
  </div>

  @if($imports->isEmpty())
    <div class="card-body text-center text-muted py-5">
      <i class="fas fa-inbox fa-2x mb-2"></i>
      <p class="mb-0">এখনো কোনো ডেটা আপলোড হয়নি।</p>
    </div>
  @else
    {{-- Bulk action bar --}}
    <div class="px-4 py-2 border-bottom bg-light d-none" id="bulkBar">
      <form method="POST" action="{{ route('admin.recharge-imports.bulk-generate') }}" id="bulkForm">
        @csrf
        <input type="hidden" name="page" value="{{ request()->get('page', 1) }}">
        <div id="bulkIds"></div>
        <div class="d-flex align-items-center gap-2">
          <span class="small fw-semibold"><span id="selectedCount">0</span>টি নির্বাচিত</span>
          <button type="submit" class="btn btn-sm btn-success fw-semibold"
                  onclick="return confirm('নির্বাচিত রোগুলোর জন্য টিকেট তৈরি করবেন?')">
            <i class="fas fa-ticket-alt me-1"></i>বাল্ক টিকেট তৈরি করুন
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSelection">বাতিল</button>
        </div>
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th class="px-3" style="width:36px">
                <input type="checkbox" id="selectAll" class="form-check-input">
              </th>
              <th>তারিখ</th>
              <th>MSISDN</th>
              <th>অপারেটর</th>
              <th>পরিমাণ</th>
              <th>টিকেট</th>
              <th>Invoice No</th>
              <th>ফাইল</th>
              <th>স্ট্যাটাস</th>
            </tr>
          </thead>
          <tbody>
            @foreach($imports as $import)
            @php
              $prefix = substr($import->msisdn, 0, 3);
              $opMap  = ['013'=>'GP','017'=>'GP','014'=>'BL','019'=>'BL','016'=>'Robi','018'=>'Robi','015'=>'TT'];
              $opLabel= $opMap[$prefix] ?? '?';
              $opColor= match($opLabel) {
                'GP'   => 'success',
                'BL'   => 'danger',
                'Robi' => 'warning text-dark',
                'TT'   => 'info text-dark',
                default=> 'secondary',
              };
            @endphp
            <tr>
              <td class="px-3">
                @if($import->ticket_status === 0)
                  <input type="checkbox" class="form-check-input row-check" value="{{ $import->id }}">
                @endif
              </td>
              <td>
                <div class="small">{{ $import->trx_time?->format('d M Y') ?? '—' }}</div>
                <div class="text-muted" style="font-size:.72rem">{{ $import->trx_time?->format('h:i A') }}</div>
              </td>
              <td><code>{{ $import->msisdn }}</code></td>
              <td><span class="badge bg-{{ $opColor }}">{{ $opLabel }}</span></td>
              <td>৳{{ number_format($import->dob_amount, 0) }}</td>
              <td class="text-center"><span class="badge bg-light text-dark border">{{ $import->ticket_count }}</span></td>
              <td><code style="font-size:.7rem">{{ $import->invoice_no }}</code></td>
              <td><span class="text-muted" style="font-size:.72rem">{{ $import->source_file }}</span></td>
              <td>
                @if($import->ticket_status === 1)
                  <div>
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>তৈরি হয়েছে</span>
                    @if($import->txn_ref)
                      <div><code style="font-size:.68rem">{{ $import->txn_ref }}</code></div>
                    @endif
                  </div>
                @else
                  <div class="d-flex flex-column gap-1">
                    <span class="badge bg-warning text-dark">টিকেট হয়নি</span>
                    <form method="POST" action="{{ route('admin.recharge-imports.generate', $import->id) }}"
                          onsubmit="return confirm('{{ $import->msisdn }} নম্বরে {{ $import->ticket_count }}টি টিকেট তৈরি করবেন?')">
                      @csrf
                      <input type="hidden" name="page" value="{{ request()->get('page', 1) }}">
                      <button type="submit" class="btn btn-sm btn-outline-success" style="font-size:.72rem;">
                        <i class="fas fa-ticket-alt me-1"></i>টিকেট তৈরি করুন
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
    @if($imports->hasPages())
    <div class="card-footer bg-white border-top py-3 px-4">
      {{ $imports->links() }}
    </div>
    @endif
  @endif
</div>

@endsection

@push('scripts')
<script>
const bulkBar      = document.getElementById('bulkBar');
const bulkIds      = document.getElementById('bulkIds');
const selectedCount= document.getElementById('selectedCount');
const selectAll    = document.getElementById('selectAll');

function updateBulkBar() {
  const checked = document.querySelectorAll('.row-check:checked');
  if (checked.length > 0) {
    bulkBar.classList.remove('d-none');
    selectedCount.textContent = checked.length;
    bulkIds.innerHTML = '';
    checked.forEach(cb => {
      const inp = document.createElement('input');
      inp.type  = 'hidden';
      inp.name  = 'ids[]';
      inp.value = cb.value;
      bulkIds.appendChild(inp);
    });
  } else {
    bulkBar.classList.add('d-none');
  }
}

document.querySelectorAll('.row-check').forEach(cb => cb.addEventListener('change', updateBulkBar));

selectAll.addEventListener('change', function () {
  document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
  updateBulkBar();
});

document.getElementById('clearSelection').addEventListener('click', function () {
  document.querySelectorAll('.row-check').forEach(cb => cb.checked = false);
  if (selectAll) selectAll.checked = false;
  updateBulkBar();
});
</script>
@endpush
