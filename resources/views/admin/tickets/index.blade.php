@extends('admin.layouts.app')
@section('title', 'টিকেট তালিকা')
@section('page-title', 'টিকেট তালিকা')

@section('content')
<!-- Filter Bar -->
<div class="card mb-3" style="border-radius:1rem;border:none;">
  <div class="card-body py-3">
    <form method="GET" action="{{ route('admin.tickets.index') }}" class="row g-2 align-items-end">
      <div class="col-sm-3">
        <label class="form-label fw-semibold small mb-1">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Ticket no..."
               value="{{ request('search') }}">
      </div>
      <div class="col-sm-2">
        <label class="form-label fw-semibold small mb-1">Phone</label>
        <input type="text" name="phone" class="form-control" placeholder="01XXXXXXXXX"
               value="{{ request('phone') }}">
      </div>
      <div class="col-sm-2">
        <label class="form-label fw-semibold small mb-1">Operator</label>
        <select name="operator" class="form-select">
          <option value="">All</option>
          <option value="Grameenphone" {{ request('operator') === 'Grameenphone' ? 'selected' : '' }}>Grameenphone</option>
          <option value="Robi"         {{ request('operator') === 'Robi'         ? 'selected' : '' }}>Robi</option>
          <option value="Banglalink"   {{ request('operator') === 'Banglalink'   ? 'selected' : '' }}>Banglalink</option>
          <option value="Teletalk"     {{ request('operator') === 'Teletalk'     ? 'selected' : '' }}>Teletalk</option>
        </select>
      </div>
      <div class="col-sm-2">
        <label class="form-label fw-semibold small mb-1">Status</label>
        <select name="status" class="form-select">
          <option value="">All</option>
          <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Unsold</option>
          <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Sold</option>
        </select>
      </div>
      <div class="col-sm-auto">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Filter</button>
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary ms-1">Reset</a>
      </div>
      <div class="col-sm-auto ms-sm-auto">
        <a href="{{ route('admin.tickets.generate') }}" class="btn btn-success">
          <i class="fas fa-plus me-1"></i> নতুন টিকেট তৈরি
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Table -->
<div class="card" style="border-radius:1rem;border:none;">
  <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
    <span class="fw-bold">মোট: {{ number_format($tickets->total()) }} টিকেট</span>
    <small class="text-muted">পৃষ্ঠা {{ $tickets->currentPage() }} / {{ $tickets->lastPage() }}</small>
  </div>
  <div class="card-body p-0">
    @if($tickets->isEmpty())
      <div class="text-center text-muted py-5">
        <i class="fas fa-inbox fa-3x mb-3 d-block opacity-30"></i>
        কোনো টিকেট পাওয়া যায়নি।
        <br><a href="{{ route('admin.tickets.generate') }}" class="btn btn-sm btn-success mt-3">টিকেট তৈরি করুন</a>
      </div>
    @else
    <form method="POST" action="{{ route('admin.tickets.bulk-delete') }}" id="bulkForm">
      @csrf
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
              <th>টিকেট নম্বর</th>
              <th>স্ট্যাটাস</th>
              <th>ফোন</th>
              <th>অপারেটর</th>
              <th>বিক্রয় সময়</th>
              <th style="width:130px;">অ্যাকশন</th>
            </tr>
          </thead>
          <tbody>
            @foreach($tickets as $ticket)
            <tr>
              <td>
                @if($ticket->status === 0)
                  <input type="checkbox" name="ids[]" value="{{ $ticket->id }}" class="form-check-input row-check">
                @endif
              </td>
              <td><span class="badge bg-dark fw-normal fs-6">{{ $ticket->ticket_no }}</span></td>
              <td>
                @if($ticket->status === 0)
                  <span class="badge badge-unsold px-3 py-2 rounded-pill">অবিক্রীত</span>
                @elseif($ticket->status === 1)
                  <span class="badge badge-sold px-3 py-2 rounded-pill">বিক্রিত</span>
                @else
                  <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">সংরক্ষিত</span>
                @endif
              </td>
              <td>{{ $ticket->phone ?? '-' }}</td>
              <td>{{ $ticket->operator ?? '-' }}</td>
              <td class="text-muted small">
                {{ $ticket->sold_at ? \Carbon\Carbon::parse($ticket->sold_at)->format('d/m/Y h:i A') : '-' }}
              </td>
              <td>
                @if($ticket->status === 0)
                  <form action="{{ route('admin.tickets.destroy', $ticket) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('এই টিকেট মুছবেন?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                  </form>
                @else
                  <span class="text-muted small">—</span>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Bulk delete bar -->
      <div class="px-3 py-2 border-top d-flex align-items-center gap-2" id="bulkBar" style="display:none!important;">
        <span class="text-muted small" id="selectedCount">0 টি নির্বাচিত</span>
        <button type="submit" class="btn btn-sm btn-danger ms-2"
                onclick="return confirm('নির্বাচিত অবিক্রীত টিকেটগুলো মুছবেন?')">
          <i class="fas fa-trash me-1"></i> নির্বাচিত মুছুন
        </button>
      </div>
    </form>
    @endif
  </div>
  @if($tickets->hasPages())
  <div class="card-footer bg-white border-top-0 d-flex justify-content-center py-3">
    {{ $tickets->links('pagination::bootstrap-5') }}
  </div>
  @endif
</div>

@endsection

@push('scripts')
<script>
const selectAll  = document.getElementById('selectAll');
const bulkBar    = document.getElementById('bulkBar');
const countLabel = document.getElementById('selectedCount');

function updateBulkBar() {
  const checked = document.querySelectorAll('.row-check:checked').length;
  if (checked > 0) {
    bulkBar.style.display = 'flex';
    countLabel.textContent = checked + ' টি নির্বাচিত';
  } else {
    bulkBar.style.display = 'none';
  }
}

selectAll && selectAll.addEventListener('change', function() {
  document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
  updateBulkBar();
});

document.querySelectorAll('.row-check').forEach(cb => cb.addEventListener('change', updateBulkBar));
</script>
@endpush
