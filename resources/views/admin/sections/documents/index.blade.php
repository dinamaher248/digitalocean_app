@extends('admin.layout.admin_app')
@section('content')
<div class="container-fluid">
  <h2 class="mb-3">مستندات التسجيل</h2>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  <a href="{{ route('admin.documents.create') }}" class="btn btn-primary mb-3">+ إضافة مستند</a>
  <div class="card"><div class="card-body">
    <table class="table table-bordered">
      <thead><tr><th>المفتاح</th><th>الاسم</th><th>النوع</th><th>مطلوب لـ</th><th>وسائل النقل</th><th>أنشطة أصحاب النشاط</th><th>إجباري</th><th>الترتيب</th><th>الحالة</th><th>إجراءات</th></tr></thead>
      <tbody>
        @foreach($documents as $d)
        @php
            $vehicleLabels = method_exists($d, 'vehicleTypeLabels') ? $d->vehicleTypeLabels() : [];
            $audienceLabel = method_exists($d, 'audienceLabel') ? $d->audienceLabel() : 'الكل';
            $hubLabels = method_exists($d, 'vendorHubSections')
                ? collect($d->vendorHubSections())->map(fn ($hub) => match ($hub) {
                    'orders' => 'طلبات',
                    'bookings' => 'حجوزات',
                    'services' => 'خدمات أخرى',
                    default => $hub,
                })->all()
                : [];
        @endphp
        <tr>
          <td>{{ $d->key }}</td>
          <td>{{ $d->label }}</td>
          <td>{{ $d->field_type }}</td>
          <td>{{ $audienceLabel }}</td>
          <td>
            @if(in_array(($d->user_type ?? 'both'), ['driver', 'both'], true))
                @if($vehicleLabels === [])
                    <span class="text-muted">كل الوسائل</span>
                @else
                    {{ implode('، ', $vehicleLabels) }}
                @endif
            @else
                <span class="text-muted">—</span>
            @endif
          </td>
          <td>
            @if(in_array(($d->user_type ?? 'both'), ['vendor', 'both'], true))
                @if($hubLabels === [])
                    <span class="text-muted">كل الأنشطة</span>
                @else
                    {{ implode('، ', $hubLabels) }}
                @endif
            @else
                <span class="text-muted">—</span>
            @endif
          </td>
          <td>{{ $d->is_required ? 'نعم' : 'لا' }}</td>
          <td>{{ $d->sort_order }}</td>
          <td>{{ $d->is_active ? 'مفعّل' : 'معطّل' }}</td>
          <td class="d-flex gap-1">
            <a href="{{ route('admin.documents.edit', $d) }}" class="btn btn-sm btn-info">تعديل</a>
            <form method="POST" action="{{ route('admin.documents.toggle', $d) }}">@csrf<button class="btn btn-sm btn-warning">تفعيل/تعطيل</button></form>
            <form method="POST" action="{{ route('admin.documents.destroy', $d) }}" onsubmit="return confirm('حذف؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">حذف</button></form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>
</div>
@endsection
