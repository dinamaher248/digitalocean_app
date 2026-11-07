@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid">
  @php $maxImages = $merchant->category?->maxImages() ?? 10; @endphp
  <h2 class="mb-2">{{ $merchant->name }} — المنتجات ({{ $merchant->products->count() }}/{{ $maxImages }})</h2>
  <p class="text-muted small mb-1">الحد الأقصى محدد من إعداد القسم «{{ $merchant->category?->name ?? '—' }}».</p>
  <a href="{{ route('admin.merchants.index') }}" class="btn btn-link">رجوع للمتاجر</a>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

  <div class="row">
    <div class="col-md-5">
      <div class="card mb-3">
        <div class="card-header">إضافة منتج</div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.merchants.products.store', $merchant->id) }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-2">
              <label class="form-label">اسم المنتج</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-2">
              <label class="form-label">السعر</label>
              <input type="number" step="0.01" name="price" class="form-control" required>
            </div>
            <div class="mb-2">
              <label class="form-label">الوصف (اختياري)</label>
              <textarea name="description" class="form-control" rows="2"></textarea>
            </div>
            <div class="mb-2">
              <label class="form-label">صورة (اختياري)</label>
              <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <button class="btn btn-primary w-100" @if($merchant->products->count() >= $maxImages) disabled @endif>إضافة</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-md-7">
      <table class="table table-bordered">
        <thead><tr><th>المنتج</th><th>السعر</th><th>الوصف</th><th></th></tr></thead>
        <tbody>
          @forelse($merchant->products as $product)
          <tr>
            <td>{{ $product->name }}</td>
            <td>{{ $product->price }} ج.م</td>
            <td>{{ $product->description ?: '—' }}</td>
            <td>
              <form method="POST" action="{{ route('admin.products.toggle', $product->id) }}" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-warning">{{ $product->is_active ? 'تعطيل' : 'تفعيل' }}</button>
              </form>
              <form method="POST" action="{{ route('admin.merchants.products.destroy', [$merchant->id, $product->id]) }}" class="d-inline">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger" onclick="return confirm('حذف المنتج؟')">حذف</button>
              </form>
            </td>
          </tr>
          @empty
          <tr><td colspan="4" class="text-center">لا توجد منتجات</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
