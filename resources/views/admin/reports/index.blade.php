@extends('admin.layouts.app')
@section('title', 'রিপোর্ট')
@section('page-title', 'বিক্রয় রিপোর্ট')

@section('content')
<!-- Export Buttons -->
<div class="d-flex gap-2 mb-3 flex-wrap">
  <a href="{{ route('admin.reports.csv') }}" class="btn btn-success">
    <i class="fas fa-file-csv me-1"></i> CSV ডাউনলোড
  </a>
  <a href="{{ route('admin.reports.pdf') }}" class="btn btn-danger" target="_blank">
    <i class="fas fa-file-pdf me-1"></i> PDF ডাউনলোড
  </a>
</div>

<!-- Operator Summary Excel -->
<div class="card mb-4" style="border-radius:1rem;border:none;">
  <div class="card-header bg-white border-bottom py-3 px-4">
    <h6 class="fw-bold mb-0"><i class="fas fa-file-excel me-2 text-success"></i>অপারেটর ওয়াইজ সামারি (Excel)</h6>
  </div>
  <div class="card-body py-3 px-4">
    <div class="d-flex gap-2 flex-wrap">
      @foreach(['Grameenphone' => ['GP','success'], 'Banglalink' => ['BL','danger'], 'Robi' => ['Robi','warning'], 'Teletalk' => ['TT','info']] as $op => [$label, $color])
      <a href="{{ route('admin.reports.summary-xlsx', $op) }}"
         class="btn btn-outline-{{ $color }} fw-semibold">
        <i class="fas fa-download me-1"></i>{{ $label }} Summary
      </a>
      @endforeach
    </div>
    <div class="text-muted small mt-2">
      <i class="fas fa-info-circle me-1"></i>প্রতিটি ফাইলে: সিরিজ ওয়াইজ মোট টিকেট, অবশিষ্ট এবং ঐ অপারেটরের বিক্রি।
    </div>
  </div>
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

@if(Auth::user()->isAdmin() && $tierProgress->isNotEmpty())
<div class="card mt-4" style="border-radius:1rem;border:none;">
  <div class="card-header bg-white border-0 pt-3">
    <h6 class="fw-bold mb-0"><i class="fas fa-layer-group me-2 text-warning"></i>সিরিজ টায়ার অগ্রগতি</h6>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th class="ps-3">অপারেটর</th>
            <th>সিরিজ</th>
            <th class="text-center">টায়ার</th>
            <th>রেঞ্জ</th>
            <th class="text-center">মোট</th>
            <th class="text-center">বিক্রিত</th>
            <th class="text-center">বাকি</th>
            <th style="min-width:160px;">অগ্রগতি</th>
            <th class="pe-3 text-center">অবস্থা</th>
          </tr>
        </thead>
        <tbody>
          @foreach($tierProgress as $key => $tiers)
            @php
              [$op, $series] = explode('||', $key);
              // Active tier = lowest tier with unsold > 0
              $activeTier = $tiers->first(fn($r) => $r->unsold > 0)?->sale_tier;
            @endphp
            @foreach($tiers as $idx => $row)
            @php
              $pct      = $row->total > 0 ? round(($row->sold / $row->total) * 100) : 0;
              $isActive = $row->sale_tier === $activeTier;
              $isDone   = $row->unsold == 0 && $row->total > 0;
              $isLocked = !$isDone && !$isActive;
              $rowBg    = $isActive ? 'background:#fffbeb;' : ($isDone ? 'background:#f0fdf4;' : '');
            @endphp
            <tr style="{{ $rowBg }}">
              @if($idx === 0)
              <td class="ps-3 fw-semibold small" rowspan="{{ $tiers->count() }}">{{ $op }}</td>
              <td class="fw-bold font-monospace small" rowspan="{{ $tiers->count() }}" style="color:#1e3a8a;">{{ $series }}</td>
              @endif
              <td class="text-center">
                <span class="badge" style="font-size:.7rem;background:{{ $isActive ? '#f59e0b' : ($isDone ? '#10b981' : '#94a3b8') }};color:#fff;">
                  T{{ $row->sale_tier }}
                </span>
              </td>
              <td class="font-monospace" style="font-size:.68rem;white-space:nowrap;">
                {{ $row->min_ticket }}<br><span class="text-muted">↓</span><br>{{ $row->max_ticket }}
              </td>
              <td class="text-center small">{{ number_format($row->total) }}</td>
              <td class="text-center small fw-semibold text-success">{{ number_format($row->sold) }}</td>
              <td class="text-center small {{ $row->unsold > 0 ? 'text-warning fw-semibold' : 'text-muted' }}">
                {{ number_format($row->unsold) }}
              </td>
              <td>
                <div class="d-flex align-items-center gap-1">
                  <div class="progress flex-fill" style="height:8px;border-radius:4px;">
                    <div class="progress-bar {{ $isDone ? 'bg-success' : ($isActive ? 'bg-warning' : 'bg-secondary') }}"
                         style="width:{{ $pct }}%;"></div>
                  </div>
                  <span class="text-muted" style="font-size:.68rem;min-width:28px;">{{ $pct }}%</span>
                </div>
              </td>
              <td class="pe-3 text-center">
                @if($isDone)
                  <span style="font-size:.7rem;color:#065f46;font-weight:700;"><i class="fas fa-check-circle me-1"></i>সম্পন্ন</span>
                @elseif($isActive)
                  <span style="font-size:.7rem;color:#b45309;font-weight:700;"><i class="fas fa-play-circle me-1"></i>চলমান</span>
                @else
                  <span style="font-size:.7rem;color:#94a3b8;"><i class="fas fa-lock me-1"></i>লক</span>
                @endif
              </td>
            </tr>
            @endforeach
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endif
@endsection
