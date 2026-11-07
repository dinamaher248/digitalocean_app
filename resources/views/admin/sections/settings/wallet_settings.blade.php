@extends('admin.layout.admin_app')

@section('content')
  <div class="container" style="max-width: 600px;">
    <h2 class="my-4">إعدادات المحفظة</h2>

    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <p class="text-muted">
      هذا الرقم يظهر للسائقين في شاشة اختيار الباقة لتحويل قيمة الاشتراك.
    </p>

    <form method="POST" action="{{ route('admin.wallet_settings.update') }}" class="row g-3">
      @csrf

      <div class="col-12">
        <label for="phone" class="form-label">رقم المحفظة / التحويل</label>
        <input
          type="text"
          name="phone"
          id="phone"
          class="form-control @error('phone') is-invalid @enderror"
          value="{{ old('phone', $walletNumber) }}"
          placeholder="مثال: 01012345678"
          required
        >
        @error('phone')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="col-12">
        <button type="submit" class="btn btn-primary w-100">حفظ</button>
      </div>
    </form>
  </div>
@endsection
