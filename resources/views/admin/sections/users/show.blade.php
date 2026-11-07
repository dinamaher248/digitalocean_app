@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>مراجعة المستخدم #{{ $user->id }}</h2>
    <a href="{{ route('admin.user.index', ['type' => $user->title === 'driver' ? 'drivers' : ($user->title === 'merchant' ? 'merchants' : 'users')]) }}" class="btn btn-secondary">رجوع</a>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card p-3">
        <h5>البيانات الأساسية</h5>
        <p><strong>الاسم:</strong> {{ $user->name }}</p>
        <p><strong>البريد:</strong> {{ $user->email }}</p>
        <p><strong>الهاتف:</strong> {{ $user->phone }}</p>
        <p><strong>النوع:</strong>
          @if($user->title === 'driver') سائق
          @elseif(in_array($user->title, ['merchant', 'vendor'], true)) صاحب نشاط
          @else عميل @endif
        </p>
        <p><strong>الحالة:</strong>
          @if($user->is_suspended)<span class="badge bg-danger">موقوف</span>
          @elseif($user->is_approved)<span class="badge bg-success">معتمد</span>
          @else<span class="badge bg-warning">بانتظار المراجعة</span>@endif
        </p>
        <p><strong>المحافظة:</strong> {{ $user->governorate ?? '—' }}</p>
        <p><strong>المنطقة:</strong> {{ $user->area ?? '—' }}</p>
        @if($user->title === 'driver')
          <p><strong>نوع المركبة:</strong> {{ $user->vehicle_type ?? '—' }}</p>
          <p><strong>الرصيد المستحق:</strong> {{ number_format($user->dues_balance ?? 0, 2) }} ج.م</p>
        @endif
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card p-3 mb-3">
        <h5>إجراءات</h5>
        <div class="d-flex flex-wrap gap-2">
          <form method="POST" action="{{ route('admin.user.suspend', $user->id) }}">@csrf
            <button class="btn btn-warning">{{ $user->is_suspended ? 'تفعيل الحساب' : 'إيقاف مؤقت' }}</button>
          </form>
          <form method="POST" action="{{ route('admin.user.approve', $user->id) }}">@csrf
            <button class="btn btn-success">اعتماد</button>
          </form>
          <form method="POST" action="{{ route('admin.user.reject', $user->id) }}">@csrf
            <button class="btn btn-outline-danger">رفض الاعتماد</button>
          </form>
          <a href="{{ route('admin.user.delete', $user->id) }}" class="btn btn-danger" onclick="return confirm('حذف المستخدم؟')">حذف</a>
        </div>
      </div>

      @if($user->title === 'driver')
      <div class="card p-3">
        <h5>مستندات السائق</h5>
        @if($user->nid_image)
          <p>البطاقة:<br><a href="{{ asset('storage/'.$user->nid_image) }}" target="_blank"><img src="{{ asset('storage/'.$user->nid_image) }}" height="120" class="rounded"></a></p>
        @endif
        @if($user->license_image)
          <p>الرخصة:<br><a href="{{ asset('storage/'.$user->license_image) }}" target="_blank"><img src="{{ asset('storage/'.$user->license_image) }}" height="120" class="rounded"></a></p>
        @endif
        @if(!$user->nid_image && !$user->license_image)<p class="text-muted">لا توجد مستندات مرفوعة</p>@endif
      </div>
      @endif

      @if(in_array($user->title, ['merchant', 'vendor'], true) && $user->ownedMerchant)
      <div class="card p-3 mt-3">
        <h5>بيانات النشاط</h5>
        <p><strong>اسم النشاط:</strong> {{ $user->ownedMerchant->name }}</p>
        <p><strong>القسم:</strong> {{ $user->ownedMerchant->category?->name }}</p>
        <p><strong>العنوان:</strong> {{ $user->ownedMerchant->address }}</p>
        <p><strong>الهاتف:</strong> {{ $user->ownedMerchant->phone }}</p>
        <a href="{{ route('admin.products.index', ['merchant_id' => $user->ownedMerchant->id]) }}" class="btn btn-sm btn-primary">عرض المنتجات</a>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection
