@extends('admin.layouts.app')
@section('title', 'রিপোর্ট')
@section('page-title', 'বিক্রয় রিপোর্ট')

@section('content')
<!-- Export Buttons -->
<div class="d-flex gap-2 mb-4">
  <a href="{{ route('admin.reports.csv') }}" class="btn btn-success">
    <i class="fas fa-file-csv me-1"></i> CSV ডাউনলোড
  </a>
  <a href="{{ route('admin.reports.pdf') }}" class="btn btn-danger" target="_blank">
    <i class="fas fa-file-pdf me-1"></i> PDF ডাউনলোড
  </a>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card text-center h-100" style="border-radius:1rem;border:2px solid #e2e8f0;">
      <div class="card-body">
        <div class="text-muted small mb-1">মোট টিকেট</div>
        <div class="display-6 fw-bold text-dark">{{ number_format($stats->total) }}</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card text-center h-100" style="border-radius:1rem;border:2px solid #d1fae5;">
      <div class="card-body">
        <div class="text-muted small mb-1">বিক্রিত</div>
        <div class="display-6 fw-bold text-success">{{ number_format($stats->sold) }}</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card text-center h-100" style="border-radius:1rem;border:2px solid #fef3c7;">
      <div class="card-body">
        <div class="text-muted small mb-1">অবিক্রীত</div>
        <div class="display-6 fw-bold text-warning">{{ number_format($stats->unsold) }}</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card text-center h-100" style="border-radius:1rem;border:2px solid #ede9fe;">
      <div class="card-body">
        <div class="text-muted small mb-1">মোট আয়</div>
        <div class="display-6 fw-bold text-purple" style="color:#7c3aed;">৳{{ number_format($stats->revenue, 0) }}</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- By Operator -->
  <div class="col-lg-5">
    <div class="card h-100" style="border-radius:1rem;border:none;">
      <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="fas fa-sim-card me-2 text-primary"></i>অপারেটর অনুযায়ী বিক্রয়</h6>
      </div>
      <div class="card-body p-0">
        @if($byOperator->isEmpty())
          <div class="text-center text-muted py-4">কোনো ডেটা নেই।</div>
        @else
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr><th>অপারেটর</th><th class="text-end">টিকেট</th><th class="text-end">আয় (৳)</th></tr>
          </thead>
          <tbody>
            @foreach($byOperator as $op)
            <tr>
              <td>{{ $op->operator ?: 'অজানা' }}</td>
              <td class="text-end fw-semibold">{{ number_format($op->count) }}</td>
              <td class="text-end text-success fw-semibold">{{ number_format($op->revenue, 0) }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
        @endif
      </div>
    </div>
  </div>

  <!-- Daily Sales -->
  <div class="col-lg-7">
    <div class="card h-100" style="border-radius:1rem;border:none;">
      <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="fas fa-calendar me-2 text-success"></i>দৈনিক বিক্রয় (শেষ ৩০ দিন)</h6>
      </div>
      <div class="card-body p-0">
        @if($daily->isEmpty())
          <div class="text-center text-muted py-4">কোনো ডেটা নেই।</div>
        @else
        <div style="max-height:350px;overflow-y:auto;">
          <table class="table table-hover table-sm mb-0">
            <thead class="table-light sticky-top">
              <tr><th>তারিখ</th><th class="text-end">টিকেট</th><th class="text-end">আয় (৳)</th></tr>
            </thead>
            <tbody>
              @foreach($daily as $d)
              <tr>
                <td class="text-muted small">{{ \Carbon\Carbon::parse($d->date)->format('d M Y') }}</td>
                <td class="text-end fw-semibold">{{ number_format($d->count) }}</td>
                <td class="text-end text-success">{{ number_format($d->revenue, 0) }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
