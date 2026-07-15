<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Admin') | BPKS লটারি</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Hind Siliguri', sans-serif; background: #f1f5f9; }
    .sidebar {
      width: 240px; min-height: 100vh;
      background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
      position: fixed; top: 0; left: 0; z-index: 100;
      display: flex; flex-direction: column;
    }
    .sidebar-brand {
      padding: 1.2rem 1.5rem;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      color: #fff; font-weight: 700; font-size: 1.1rem; text-decoration: none;
    }
    .sidebar-brand span { font-size: 0.75rem; opacity: 0.7; display: block; font-weight: 400; }
    .sidebar-nav { padding: 1rem 0; flex: 1; }
    .sidebar-nav .nav-link {
      color: rgba(255,255,255,0.8); padding: 0.65rem 1.5rem;
      display: flex; align-items: center; gap: 0.6rem; font-weight: 500;
      border-left: 3px solid transparent; transition: all 0.2s;
    }
    .sidebar-nav .nav-link:hover,
    .sidebar-nav .nav-link.active {
      color: #fff; background: rgba(255,255,255,0.1);
      border-left-color: #fbbf24;
    }
    .sidebar-nav .nav-link .fa { width: 18px; text-align: center; }
    .sidebar-footer { padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); }
    .main-wrapper { margin-left: 240px; min-height: 100vh; }
    .topbar {
      background: #fff; border-bottom: 1px solid #e2e8f0;
      padding: 0.8rem 1.5rem; display: flex; align-items: center;
      justify-content: space-between; position: sticky; top: 0; z-index: 50;
    }
    .page-content { padding: 1.5rem; }
    .stat-card { border-radius: 1rem; border: none; overflow: hidden; }
    .stat-card .card-body { padding: 1.2rem 1.5rem; }
    .stat-number { font-size: 2rem; font-weight: 700; line-height: 1; }
    .badge-unsold { background: #fef3c7; color: #92400e; }
    .badge-sold   { background: #d1fae5; color: #065f46; }
    @media (max-width: 768px) {
      .sidebar { width: 100%; min-height: auto; position: relative; }
      .main-wrapper { margin-left: 0; }
    }
  </style>
  @stack('styles')
</head>
<body>
<div class="d-flex">
  <!-- Sidebar -->
  <nav class="sidebar">
    <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
      <i class="fas fa-wheelchair me-2"></i>BPKS Admin
      <span>প্রতিবন্ধী কল্যাণ লটারি</span>
    </a>
    <div class="sidebar-nav">
      <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt fa"></i> ড্যাশবোর্ড
      </a>
      @if(Auth::user()->canManageTickets())
      <a href="{{ route('admin.tickets.index') }}" class="nav-link {{ request()->routeIs('admin.tickets.index') ? 'active' : '' }}">
        <i class="fas fa-ticket-alt fa"></i> টিকেট তালিকা
      </a>
      <a href="{{ route('admin.tickets.generate') }}" class="nav-link {{ request()->routeIs('admin.tickets.generate') ? 'active' : '' }}">
        <i class="fas fa-plus-circle fa"></i> টিকেট তৈরি
      </a>
      <a href="{{ route('admin.booking.index') }}" class="nav-link {{ request()->routeIs('admin.booking.*') ? 'active' : '' }}">
        <i class="fas fa-cash-register fa"></i> ম্যানুয়াল বুকিং
      </a>
      @endif
      @if(Auth::user()->canViewReports())
      <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}">
        <i class="fas fa-chart-bar fa"></i> রিপোর্ট
      </a>
      <a href="{{ route('admin.reports.sms') }}" class="nav-link {{ request()->routeIs('admin.reports.sms') ? 'active' : '' }}">
        <i class="fas fa-sms fa"></i> SMS ব্যর্থতা
      </a>
      @endif
      <a href="{{ route('admin.reports.daily') }}" class="nav-link {{ request()->routeIs('admin.reports.daily') ? 'active' : '' }}">
        <i class="fas fa-calendar-alt fa"></i> দৈনিক রিপোর্ট
      </a>
      <a href="{{ route('admin.journey.index') }}" class="nav-link {{ request()->routeIs('admin.journey.*') ? 'active' : '' }}">
        <i class="fas fa-route fa"></i> কাস্টমার জার্নি
      </a>
      <a href="{{ route('admin.customer-care.index') }}" class="nav-link {{ request()->routeIs('admin.customer-care.*') ? 'active' : '' }}">
        <i class="fas fa-headset fa"></i> কাস্টমার কেয়ার
      </a>
      @if(Auth::user()->canManageTickets())
      <a href="{{ route('admin.replacement-tickets.index') }}" class="nav-link {{ request()->routeIs('admin.replacement-tickets*') ? 'active' : '' }}">
        <i class="fas fa-exchange-alt fa"></i> রিপ্লেসমেন্ট টিকেট
      </a>
      <a href="{{ route('admin.recharge-imports.index') }}" class="nav-link {{ request()->routeIs('admin.recharge-imports*') ? 'active' : '' }}">
        <i class="fas fa-upload fa"></i> রিচার্জ ইম্পোর্ট
      </a>
      @endif
      @if(Auth::user()->isAdmin())
      <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <i class="fas fa-users fa"></i> ব্যবহারকারী
      </a>
      @endif
    </div>
    <div class="sidebar-footer">
      <form action="{{ route('admin.logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-light w-100">
          <i class="fas fa-sign-out-alt me-1"></i> লগআউট
        </button>
      </form>
    </div>
  </nav>

  <!-- Main -->
  <div class="main-wrapper flex-fill">
    <div class="topbar">
      <div class="fw-bold text-dark">@yield('page-title', 'Admin Panel')</div>
      <div class="text-muted small">
        <i class="fas fa-user-circle me-1"></i>{{ Auth::user()->name }}
        @php $roleLabel = ['admin'=>'Admin','operator'=>'Operator','customer_care'=>'Customer Care'][Auth::user()->role] ?? Auth::user()->role; @endphp
        <span class="badge ms-1" style="font-size:.6rem;background:#e2e8f0;color:#475569;">{{ $roleLabel }}</span>
        @if(Auth::user()->isOperator() && Auth::user()->operator)
          <span class="badge ms-1" style="font-size:.6rem;background:#dbeafe;color:#1d4ed8;">{{ Auth::user()->operator }}</span>
        @endif
      </div>
    </div>
    <div class="page-content">
      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif
      @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif
      @yield('content')
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
