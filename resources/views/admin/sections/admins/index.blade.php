@extends('admin.layout.admin_app')
@section('content')
<div class="container-fluid">
  <h2 class="mb-3">إدارة المشرفين</h2>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
  <a href="{{ route('admin.admins.create') }}" class="btn btn-primary mb-3">+ إضافة مشرف</a>
  <div class="card"><div class="card-body">
    <table class="table table-bordered">
      <thead><tr><th>الاسم</th><th>البريد</th><th>الحالة</th><th>إجراءات</th></tr></thead>
      <tbody>
        @foreach($admins as $a)
        <tr>
          <td>{{ $a->name }}</td>
          <td>{{ $a->email }}</td>
          <td>{{ $a->is_suspended ? 'موقوف' : 'نشط' }}</td>
          <td>
            <a href="{{ route('admin.admins.edit', $a->id) }}" class="btn btn-sm btn-info">تعديل</a>
            <form class="d-inline" method="POST" action="{{ route('admin.admins.destroy', $a->id) }}" onsubmit="return confirm('حذف المشرف؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">حذف</button></form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>
</div>
@endsection
