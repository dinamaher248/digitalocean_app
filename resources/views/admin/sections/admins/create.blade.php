@extends('admin.layout.admin_app')
@section('content')
<div class="container-fluid"><h2>إضافة مشرف</h2>
<form method="POST" action="{{ route('admin.admins.store') }}" class="card p-3 mt-3">@csrf
  <div class="mb-3"><label>الاسم</label><input name="name" class="form-control" required></div>
  <div class="mb-3"><label>البريد</label><input type="email" name="email" class="form-control" required></div>
  <div class="mb-3"><label>كلمة المرور</label><input type="password" name="password" class="form-control" required></div>
  <div class="mb-3"><label>تأكيد كلمة المرور</label><input type="password" name="password_confirmation" class="form-control" required></div>
  <h5>الصلاحيات</h5>
  <div class="row">
    @foreach($permissionKeys as $key => $label)
    <div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}" id="p_{{ $key }}"><label class="form-check-label" for="p_{{ $key }}">{{ $label }}</label></div></div>
    @endforeach
  </div>
  <button class="btn btn-primary mt-3">حفظ</button>
</form></div>
@endsection
