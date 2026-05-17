@extends('admin.layouts.app')
@section('title', 'Generate Tickets')
@section('page-title', 'Generate New Tickets')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card" style="border-radius:1rem;border:none;">
      <div class="card-body p-4">
        <p class="text-muted mb-4">
          <i class="fas fa-info-circle me-1 text-primary"></i>
          Fill the form below. Tickets will be generated serially and saved with <strong>status 0 (Unsold)</strong>.
        </p>

        @if($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
              @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ route('admin.tickets.generate.post') }}" id="generateForm">
          @csrf

          <div class="mb-3">
            <label class="form-label fw-semibold">Operator <span class="text-danger">*</span></label>
            <select name="operator" class="form-select form-select-lg" required>
              <option value="">— Select Operator —</option>
              <option value="Grameenphone" {{ old('operator') == 'Grameenphone' ? 'selected' : '' }}>Grameenphone (GP)</option>
              <option value="Robi"         {{ old('operator') == 'Robi'         ? 'selected' : '' }}>Robi</option>
              <option value="Robi"         {{ old('operator') == 'Airtel'       ? 'selected' : '' }}>Airtel (counted as Robi)</option>
              <option value="Banglalink"   {{ old('operator') == 'Banglalink'   ? 'selected' : '' }}>Banglalink</option>
              <option value="Teletalk"     {{ old('operator') == 'Teletalk'     ? 'selected' : '' }}>Teletalk</option>
            </select>
            <div class="form-text">Airtel is stored as Robi in the database.</div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Prefix</label>
            <input type="text" name="prefix" class="form-control form-control-lg"
                   value="{{ old('prefix', 'BPKS-') }}" placeholder="BPKS-"
                   maxlength="10" oninput="updatePreview()" required>
            <div class="form-text">Example: BPKS-, LOT-, TKT-</div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Start Number</label>
            <input type="text" name="start_number" class="form-control form-control-lg"
                   value="{{ old('start_number', '0000001') }}"
                   inputmode="numeric" pattern="[0-9]+"
                   oninput="this.value=this.value.replace(/[^0-9]/g,''); updatePreview()" required>
            <div class="form-text">Leading zeros are preserved (e.g. 0000001).</div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Number of Tickets</label>
            <input type="number" name="count" class="form-control form-control-lg"
                   value="{{ old('count', 1000) }}" min="1" max="100000"
                   oninput="updatePreview()" required>
            <div class="form-text">Maximum 1,00,000 at once.</div>
          </div>

          <!-- Live Preview -->
          <div class="alert alert-info py-2 mb-4" id="preview">
            <i class="fas fa-eye me-1"></i> Loading preview...
          </div>

          <button type="submit" class="btn btn-success w-100 py-2 fw-bold" id="submitBtn">
            <i class="fas fa-magic me-1"></i> Generate Tickets
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function pad(n, len) {
  return String(n).padStart(len, '0');
}
function updatePreview() {
  const prefix    = document.querySelector('[name=prefix]').value.toUpperCase() || 'BPKS-';
  const startRaw  = document.querySelector('[name=start_number]').value || '1';
  const start     = parseInt(startRaw) || 1;
  const count     = parseInt(document.querySelector('[name=count]').value) || 0;
  const end       = start + count - 1;
  const len       = Math.max(startRaw.length, String(end).length); // preserve leading zeros
  const first     = prefix + pad(start, len);
  const last      = prefix + pad(end, len);
  document.getElementById('preview').innerHTML =
    `<i class="fas fa-eye me-1"></i> First: <strong>${first}</strong> &nbsp;→&nbsp; Last: <strong>${last}</strong> &nbsp;|&nbsp; Total: <strong>${count.toLocaleString()}</strong> tickets`;
}
updatePreview();

document.getElementById('generateForm').addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Generating, please wait...';
});
</script>
@endpush
