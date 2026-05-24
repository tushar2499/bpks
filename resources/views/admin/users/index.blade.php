@extends('admin.layouts.app')
@section('title', 'ব্যবহারকারী')
@section('page-title', 'ব্যবহারকারী ব্যবস্থাপনা')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <span class="text-muted small">মোট {{ $users->count() }} জন</span>
  <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i>নতুন ব্যবহারকারী
  </a>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <table class="table table-sm table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th class="ps-3">নাম</th>
          <th>ইমেইল</th>
          <th>রোল</th>
          <th>অপারেটর</th>
          <th class="pe-3 text-end">অ্যাকশন</th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $u)
        @php
          $roleBadge = match($u->role) {
            'admin'         => ['bg-danger text-white', 'Admin'],
            'operator'      => ['bg-primary text-white', 'Operator'],
            'customer_care' => ['bg-success text-white', 'Customer Care'],
            default         => ['bg-secondary text-white', $u->role],
          };
        @endphp
        <tr>
          <td class="ps-3 fw-semibold">
            {{ $u->name }}
            @if($u->id === auth()->id())
              <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem;">You</span>
            @endif
          </td>
          <td class="small text-muted">{{ $u->email }}</td>
          <td><span class="badge {{ $roleBadge[0] }}" style="font-size:.7rem;">{{ $roleBadge[1] }}</span></td>
          <td class="small">{{ $u->operator ?? '—' }}</td>
          <td class="pe-3 text-end">
            <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-primary py-0 px-2">
              <i class="fas fa-edit"></i>
            </a>
            @if($u->id !== auth()->id())
            <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="d-inline"
                  onsubmit="return confirm('{{ $u->name }} কে মুছবেন?')">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-outline-danger py-0 px-2"><i class="fas fa-trash"></i></button>
            </form>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
