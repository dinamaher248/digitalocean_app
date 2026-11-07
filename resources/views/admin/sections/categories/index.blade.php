@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid">
  <h2 class="mb-3">إدارة الأقسام</h2>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

  <a href="{{ route('admin.categories.create') }}" class="btn btn-primary mb-3">+ إضافة قسم</a>

  <div class="card"><div class="card-body">
    <table class="table table-bordered">
      <thead><tr><th>#</th><th>الصورة</th><th>الاسم</th><th>النوع</th><th>Slug</th><th>عدد الصور المضافة</th><th>الحد المسموح</th><th>الترتيب</th><th>الحالة</th><th>إجراءات</th></tr></thead>
      <tbody>
        @foreach($categories as $c)
        @php
          $section = $c->hub_section ?? 'orders';
          $sectionLabel = match ($section) {
            'bookings' => 'الحجوزات',
            'services' => 'خدمات أخرى',
            default => 'الطلبات',
          };
          $img = $c->icon ?? '';
          $hasImg = is_string($img) && $img !== '' && (str_contains($img, '/') || preg_match('/\.(jpe?g|png|webp|gif)$/i', $img));
        @endphp
        <tr>
          <td>{{ $c->id }}</td>
          <td>
            @if($hasImg)
              <img src="{{ asset('storage/' . ltrim($img, '/')) }}" alt="" style="width:56px;height:40px;object-fit:cover;border-radius:6px;">
            @else
              <span class="text-muted">—</span>
            @endif
          </td>
          <td>{{ $c->name }}</td>
          <td><span class="badge bg-primary">{{ $sectionLabel }}</span></td>
          <td>{{ $c->slug }}</td>
          <td>
            @php $imagesCount = (int) (($c->images_count ?? 0) ?: ($c->items_count ?? 0)); @endphp
            <span class="badge bg-dark">{{ $imagesCount }}</span>
          </td>
          <td><span class="badge bg-info">{{ $c->max_images ?: 10 }}</span></td>
          <td>{{ $c->sort_order }}</td>
          <td><span class="badge {{ $c->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $c->is_active ? 'مفعّل' : 'معطّل' }}</span></td>
          <td class="d-flex gap-1">
            <a href="{{ route('admin.categories.edit', $c) }}" class="btn btn-sm btn-info">تعديل</a>
            <form method="POST" action="{{ route('admin.categories.toggle', $c) }}">@csrf<button class="btn btn-sm btn-warning">تفعيل/تعطيل</button></form>
            <form method="POST" action="{{ route('admin.categories.destroy', $c) }}" onsubmit="return confirm('حذف القسم؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">حذف</button></form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>
</div>
@endsection
