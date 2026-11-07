@extends('admin.layout.admin_app')
@section('content')
<div class="container-fluid">
    <h2>تغيير كلمة المرور</h2>

    @if (session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mt-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.password.update') }}" class="card p-3 mt-3" style="max-width: 560px;">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>كلمة المرور الحالية</label>
            <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
        </div>

        <div class="mb-3">
            <label>كلمة المرور الجديدة</label>
            <input type="password" name="password" class="form-control" required autocomplete="new-password">
            <div class="form-text">8 أحرف على الأقل.</div>
        </div>

        <div class="mb-3">
            <label>تأكيد كلمة المرور الجديدة</label>
            <input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
        </div>

        <button class="btn btn-primary">حفظ</button>
    </form>
</div>
@endsection
