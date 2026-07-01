@extends('admin.layouts.app')
@section('title', 'ড্যাশবোর্ড')
@section('page-title', 'ড্যাশবোর্ড')

@section('content')
<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card h-100" style="background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="small opacity-75 mb-1">মোট টিকেট</div>
            <div class="stat-number">{{ number_format($stats->total) }}</div>
          </div>
          <i class="fas fa-ticket-alt fa-2x opacity-50"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card h-100" style="background:linear-gradient(135deg,#065f46,#059669);color:#fff;">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="small opacity-75 mb-1">বিক্রিত</div>
            <div class="stat-number">{{ number_format($stats->sold) }}</div>
          </div>
          <i class="fas fa-check-circle fa-2x opacity-50"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card h-100" style="background:linear-gradient(135deg,#92400e,#d97706);color:#fff;">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="small opacity-75 mb-1">অবিক্রীত</div>
            <div class="stat-number">{{ number_format($stats->unsold) }}</div>
          </div>
          <i class="fas fa-hourglass-half fa-2x opacity-50"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card h-100" style="background:linear-gradient(135deg,#7c1d6f,#a21caf);color:#fff;">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="small opacity-75 mb-1">মোট আয়</div>
            <div class="stat-number">৳{{ number_format($stats->revenue, 0) }}</div>
          </div>
          <i class="fas fa-taka-sign fa-2x opacity-50"></i>
        </div>
      </div>
    </div>
  </div>
</div>

@if(Auth::user()->isAdmin() && $stuckCount > 0)
<div class="alert alert-warning d-flex align-items-center justify-content-between mb-4" style="border-radius:1rem;">
  <div>
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>{{ number_format($stuckCount) }} টি</strong> টিকেট ১ ঘণ্টারও বেশি সময় ধরে রিজার্ভড অবস্থায় আছে।
  </div>
  <form method="POST" action="{{ route('admin.tickets.release-stuck') }}" class="ms-3">
    @csrf
    <button type="submit" class="btn btn-warning btn-sm fw-bold"
            onclick="return confirm('{{ $stuckCount }} টি রিজার্ভড টিকেট রিলিজ করবেন?')">
      <i class="fas fa-unlock me-1"></i>রিলিজ করুন
    </button>
  </form>
</div>
@endif

<!-- Progress Bar -->
@if($stats->total > 0)
<div class="card mb-4" style="border-radius:1rem;border:none;">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <span class="fw-semibold">বিক্রয় অগ্রগতি</span>
      <span class="text-muted small">{{ $stats->total > 0 ? number_format(($stats->sold / $stats->total) * 100, 1) : 0 }}%</span>
    </div>
    <div class="progress" style="height:12px;border-radius:6px;">
      <div class="progress-bar bg-success" style="width:{{ $stats->total > 0 ? ($stats->sold / $stats->total) * 100 : 0 }}%;border-radius:6px;"></div>
    </div>
    <div class="d-flex justify-content-between mt-1">
      <small class="text-success">{{ number_format($stats->sold) }} বিক্রিত</small>
      <small class="text-muted">{{ number_format($stats->unsold) }} বাকি</small>
    </div>
  </div>
</div>
@endif

<!-- Operator-wise Sales -->
@if($operatorStats->isNotEmpty())
<div class="card mb-4" style="border-radius:1rem;border:none;">
  <div class="card-header bg-white border-0 pt-3 pb-0">
    <h6 class="fw-bold mb-0"><i class="fas fa-sim-card me-2 text-primary"></i>অপারেটর ভিত্তিক বিক্রয়</h6>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>অপারেটর</th>
            <th class="text-end">বিক্রিত টিকেট</th>
            <th class="text-end">মোট আয়</th>
            <th style="width:35%">অংশীদারিত্ব</th>
          </tr>
        </thead>
        <tbody>
          @foreach($operatorStats as $op)
          @php
            $pct = $stats->sold > 0 ? ($op->sold / $stats->sold) * 100 : 0;
            $color = match($op->operator) {
              'Grameenphone' => '#16a34a',
              'Robi'        => '#dc2626',
              'Banglalink'  => '#ea580c',
              'Teletalk'    => '#2563eb',
              default       => '#6366f1',
            };
          @endphp
          <tr>
            <td class="fw-semibold">{{ $op->operator ?? 'অজানা' }}</td>
            <td class="text-end">{{ number_format($op->sold) }}</td>
            <td class="text-end">৳{{ number_format($op->revenue, 0) }}</td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="progress flex-grow-1" style="height:8px;border-radius:4px;">
                  <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $color }};border-radius:4px;"></div>
                </div>
                <small class="text-muted" style="min-width:38px;">{{ number_format($pct, 1) }}%</small>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endif

<!-- Sales Chart (last 60 days) -->
@if(count($chartDates) > 0)
<div class="card mb-4" style="border-radius:1rem;border:none;">
  <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
    <h6 class="fw-bold mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>বিক্রয় বিশ্লেষণ (শেষ ৬০ দিন)</h6>
    <div class="btn-group btn-group-sm" role="group">
      <button id="btnQty" type="button" class="btn btn-primary" onclick="switchMode('qty')">সংখ্যা</button>
      <button id="btnRev" type="button" class="btn btn-outline-primary" onclick="switchMode('revenue')">আয় (৳)</button>
    </div>
  </div>
  <div class="card-body">
    <canvas id="salesChart" style="max-height:320px;"></canvas>
  </div>
</div>
@endif

<!-- Recent Sales -->
<div class="card" style="border-radius:1rem;border:none;">
  <div class="card-header bg-white border-0 pt-3 pb-0">
    <h6 class="fw-bold mb-0"><i class="fas fa-clock me-2 text-primary"></i>সর্বশেষ বিক্রয়</h6>
  </div>
  <div class="card-body p-0">
    @if($recentSold->isEmpty())
      <div class="text-center text-muted py-4">
        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-50"></i>
        এখনও কোনো টিকেট বিক্রি হয়নি।
      </div>
    @else
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>টিকেট নম্বর</th>
            <th>ফোন</th>
            <th>অপারেটর</th>
            <th>মূল্য</th>
            <th>বিক্রয় সময়</th>
          </tr>
        </thead>
        <tbody>
          @foreach($recentSold as $t)
          <tr>
            <td><span class="badge bg-primary fw-normal">{{ $t->ticket_no }}</span></td>
            <td>{{ $t->phone ?? '-' }}</td>
            <td>{{ $t->operator ?? '-' }}</td>
            <td>৳{{ $t->sell_price }}</td>
            <td class="text-muted small">{{ $t->sold_at ? \Carbon\Carbon::parse($t->sold_at)->format('d M Y, h:i A') : '-' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>
</div>
@if(Auth::user()->isAdmin())
<div class="card mt-4" style="border-radius:1rem;border:none;">
  <div class="card-header bg-white border-0 pt-3 pb-0">
    <h6 class="fw-bold mb-0"><i class="fas fa-chart-line me-2 text-primary"></i>ট্রাফিক বিশ্লেষণ (Google Analytics)</h6>
  </div>
  <div class="card-body p-2">
    <div style="position:relative;width:100%;overflow:hidden;border-radius:.5rem;">
      <iframe
        width="100%" height="450"
        src="https://datastudio.google.com/embed/reporting/33c58867-3126-4b08-8859-23454953139d/page/rJwzF"
        frameborder="0" style="border:0;display:block;" allowfullscreen
        sandbox="allow-storage-access-by-user-activation allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox">
      </iframe>
    </div>
  </div>
</div>
@endif

@endsection

@push('scripts')
@if(count($chartDates) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const DATES     = @json($chartDates);
const OPERATORS = @json($chartOperators);
const DATASETS  = @json($chartDatasets);

const OP_COLORS = {
  'Grameenphone': '#16a34a',
  'Robi':         '#dc2626',
  'Airtel':       '#be123c',
  'Banglalink':   '#ea580c',
};
const DEFAULT_COLOR = '#6366f1';

function buildDatasets(mode) {
  return OPERATORS.map(op => ({
    label:           op,
    data:            DATASETS[op][mode],
    backgroundColor: (OP_COLORS[op] ?? DEFAULT_COLOR) + 'cc',
    borderColor:     OP_COLORS[op] ?? DEFAULT_COLOR,
    borderWidth:     1,
    borderRadius:    3,
  }));
}

const ctx   = document.getElementById('salesChart').getContext('2d');
let mode    = 'qty';
const chart = new Chart(ctx, {
  type: 'bar',
  data: { labels: DATES, datasets: buildDatasets('qty') },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
      tooltip: {
        callbacks: {
          label: ctx => {
            const v = ctx.parsed.y;
            return ` ${ctx.dataset.label}: ${mode === 'revenue' ? '৳' + v.toLocaleString() : v}`;
          }
        }
      }
    },
    scales: {
      x: {
        stacked: true,
        ticks: { font: { size: 10 }, maxRotation: 45 },
        grid: { display: false },
      },
      y: {
        stacked: true,
        beginAtZero: true,
        ticks: {
          font: { size: 10 },
          callback: v => mode === 'revenue' ? '৳' + v.toLocaleString() : v,
        },
      },
    },
  },
});

function switchMode(m) {
  mode = m;
  chart.data.datasets = buildDatasets(m);
  chart.options.scales.y.ticks.callback = v => m === 'revenue' ? '৳' + v.toLocaleString() : v;
  chart.update();
  document.getElementById('btnQty').className = m === 'qty'     ? 'btn btn-sm btn-primary'         : 'btn btn-sm btn-outline-primary';
  document.getElementById('btnRev').className = m === 'revenue' ? 'btn btn-sm btn-primary'         : 'btn btn-sm btn-outline-primary';
}
</script>
@endif
@endpush
