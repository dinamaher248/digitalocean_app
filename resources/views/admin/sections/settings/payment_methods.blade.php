@extends('admin.layout.admin_app')
@section('content')
<div class="container-fluid">
  <h2 class="mb-3">طرق الدفع الخاصة بالتطبيق</h2>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  <form method="POST" action="{{ route('admin.payment_settings.update') }}" class="card p-3">@csrf
    <div class="form-check form-switch mb-3">
      <input class="form-check-input" type="checkbox" name="wallet_enabled" value="1" id="wallet_enabled" {{ $settings['wallet_enabled'] ? 'checked' : '' }}>
      <label class="form-check-label" for="wallet_enabled">تفعيل فودافون كاش / المحفظة</label>
    </div>
    <div class="mb-3"><label class="form-label">رقم فودافون كاش</label><input name="wallet_number" class="form-control" value="{{ $settings['wallet_number'] }}"></div>
    <hr>
    <div class="form-check form-switch mb-3">
      <input class="form-check-input" type="checkbox" name="instapay_enabled" value="1" id="instapay_enabled" {{ $settings['instapay_enabled'] ? 'checked' : '' }}>
      <label class="form-check-label" for="instapay_enabled">تفعيل انستا باي</label>
    </div>
    <div class="mb-3"><label class="form-label">بيانات / رقم انستا باي</label><input name="instapay_number" class="form-control" value="{{ $settings['instapay_number'] }}"></div>
    <button class="btn btn-primary">حفظ</button>
  </form>
</div>
@endsection
