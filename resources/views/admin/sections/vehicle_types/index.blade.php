@extends('admin.layout.admin_app')
@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">وسائل النقل والنطاق</h2>
    <a href="{{ route('admin.vehicle_types.create') }}" class="btn btn-primary">+ إضافة وسيلة</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if(!empty($missingTable))
    <div class="alert alert-warning">
      جدول وسائل النقل غير موجود. نفّذ على السيرفر:
      <code>php artisan migrate</code>
    </div>
  @endif

  <div class="alert alert-info">
    أضف أي وسيلة (مثل <strong>سكوتر</strong>) واختر تظهر تحت <strong>مشوار سريع</strong> أو <strong>نقل بضائع</strong>.
    حدد أيضاً نطاق الظهور بالكيلومتر ليصل الإشعار للسائقين القريبين فقط.
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-bordered align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>المفتاح</th>
            <th>الاسم</th>
            <th>التصنيف</th>
            <th>نطاق الظهور (كم)</th>
            <th>الترتيب</th>
            <th>الحالة</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          @forelse($types as $type)
            <tr>
              <td>{{ $type->id }}</td>
              <td><code>{{ $type->key }}</code></td>
              <td>{{ $type->displayName() }}</td>
              <td>
                @if(($type->category ?? 'quick_trip') === 'goods')
                  <span class="badge bg-success">نقل بضائع</span>
                @else
                  <span class="badge bg-warning text-dark">مشوار سريع</span>
                @endif
              </td>
              <td>
                <span class="badge bg-secondary fs-6">
                  {{ number_format((float)($type->service_radius_km ?? 7), 1) }} كم
                </span>
              </td>
              <td>{{ $type->sort_order ?? 0 }}</td>
              <td>{{ ($type->is_active ?? true) ? 'مفعّل' : 'معطّل' }}</td>
              <td class="d-flex gap-1 flex-wrap">
                <a href="{{ route('admin.vehicle_types.edit', $type) }}" class="btn btn-sm btn-info">تعديل</a>
                <form method="POST" action="{{ route('admin.vehicle_types.toggle', $type) }}">@csrf
                  <button class="btn btn-sm btn-warning">تفعيل/تعطيل</button>
                </form>
                <form method="POST" action="{{ route('admin.vehicle_types.destroy', $type) }}" onsubmit="return confirm('حذف وسيلة النقل؟')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-danger">حذف</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted">لا توجد وسائل نقل بعد</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
