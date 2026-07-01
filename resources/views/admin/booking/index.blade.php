@extends('admin.layouts.app')
@section('title', 'Manual Ticket Booking')
@section('page-title', 'ম্যানুয়াল টিকেট বুকিং')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-6">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
          @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
      </div>
    @endif

    <div class="card" style="border-radius:1rem;border:none;">
      <div class="card-body p-4">
        <p class="text-muted mb-4">
          <i class="fas fa-info-circle me-1 text-primary"></i>
          গ্রাহকের মোবাইল নম্বর ও টিকেট সংখ্যা দিন। টিকেট স্বয়ংক্রিয়ভাবে বরাদ্দ হবে এবং SMS পাঠানো হবে।
        </p>

        <form method="POST" action="{{ route('admin.booking.store') }}" id="bookingForm">
          @csrf

          <div class="mb-3">
            <label class="form-label fw-semibold">মোবাইল নম্বর <span class="text-danger">*</span></label>
            <input type="text" name="phone" class="form-control form-control-lg"
                   value="{{ old('phone') }}" placeholder="01XXXXXXXXX"
                   inputmode="tel" maxlength="15" required autofocus>
            <div class="form-text">বাংলাদেশী নম্বর (01X-XXXXXXXX)</div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">টিকেট সংখ্যা <span class="text-danger">*</span></label>
            <input type="number" name="qty" class="form-control form-control-lg"
                   value="{{ old('qty', 1) }}" min="1" max="10" required>
            <div class="form-text">সর্বোচ্চ ১০টি একবারে বুক করা যাবে।</div>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" id="submitBtn">
            <i class="fas fa-ticket-alt me-2"></i> বুকিং সম্পন্ন করুন
          </button>
        </form>
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('bookingForm').addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> প্রক্রিয়াকরণ হচ্ছে...';
});
</script>
@endpush
