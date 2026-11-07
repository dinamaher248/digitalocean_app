@extends('admin.layout.admin_app')
@section('content')
<div class="container-fluid"><h2>تعديل مشرف</h2>
<form method="POST" action="{{ route('admin.admins.update', $admin->id) }}" class="card p-3 mt-3">@csrf @method('PUT')
  <div class="mb-3"><label>الاسم</label><input name="name" class="form-control" value="{{ $admin->name }}" required></div>
  <div class="mb-3"><label>البريد</label><input type="email" name="email" class="form-control" value="{{ $admin->email }}" required></div>
  <div class="mb-3"><label>كلمة مرور جديدة (اختياري)</label><input type="password" name="password" class="form-control"></div>
  <div class="mb-3"><label>تأكيد كلمة المرور</label><input type="password" name="password_confirmation" class="form-control"></div>
  <div class="form-check mb-3"><input type="checkbox" name="is_suspended" value="1" class="form-check-input" id="is_suspended" {{ $admin->is_suspended ? 'checked' : '' }}><label for="is_suspended">إيقاف المشرف</label></div>
  <h5>الصلاحيات</h5>
  @php $perms = $admin->admin_permissions ?? []; @endphp
  <div class="row">
    @foreach($permissionKeys as $key => $label)
    <div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}" id="p_{{ $key }}" {{ in_array($key, $perms) ? 'checked' : '' }}><label class="form-check-label" for="p_{{ $key }}">{{ $label }}</label></div></div>
    @endforeach
  </div>
  <button class="btn btn-primary mt-3">حفظ</button>
</form></div>
@endsection
