@extends('admin.layouts.app')
@section('title', $user->exists ? 'ব্যবহারকারী সম্পাদনা' : 'নতুন ব্যবহারকারী')
@section('page-title', $user->exists ? 'ব্যবহারকারী সম্পাদনা' : 'নতুন ব্যবহারকারী')

@section('content')
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <form method="POST"
              action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
          @csrf
          @if($user->exists) @method('PUT') @endif

          <div class="mb-3">
            <label class="form-label fw-semibold">নাম <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $user->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">ইমেইল <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $user->email) }}" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">
              পাসওয়ার্ড {{ $user->exists ? '(ফাঁকা রাখলে পরিবর্তন হবে না)' : '*' }}
            </label>
            <input type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   {{ $user->exists ? '' : 'required' }}>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">পাসওয়ার্ড নিশ্চিত করুন</label>
            <input type="password" name="password_confirmation" class="form-control">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">রোল <span class="text-danger">*</span></label>
            <select name="role" id="roleSelect" class="form-select @error('role') is-invalid @enderror" required>
              <option value="admin"         @selected(old('role', $user->role) === 'admin')>Admin</option>
              <option value="operator"      @selected(old('role', $user->role) === 'operator')>Operator</option>
              <option value="customer_care" @selected(old('role', $user->role) === 'customer_care')>Customer Care</option>
            </select>
            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mb-4" id="operatorField" style="display:none;">
            <label class="form-label fw-semibold">অপারেটর <span class="text-danger">*</span></label>
            <select name="operator" class="form-select @error('operator') is-invalid @enderror">
              <option value="">— নির্বাচন করুন —</option>
              @foreach(['Grameenphone','Robi','Airtel','Banglalink'] as $op)
              <option value="{{ $op }}" @selected(old('operator', $user->operator) === $op)>{{ $op }}</option>
              @endforeach
            </select>
            @error('operator')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-1"></i>{{ $user->exists ? 'আপডেট' : 'তৈরি করুন' }}
            </button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">বাতিল</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const roleSelect    = document.getElementById('roleSelect');
const operatorField = document.getElementById('operatorField');
function toggleOperator() {
  operatorField.style.display = roleSelect.value === 'operator' ? '' : 'none';
}
roleSelect.addEventListener('change', toggleOperator);
toggleOperator();
</script>
@endpush
