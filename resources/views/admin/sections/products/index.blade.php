@extends('admin.layout.admin_app')
@section('content')
<div class="container-fluid">
  @php
    $ownersMode = isset($owners);
    $owner = $owner ?? null;
    $merchant = $merchant ?? null;
    $ownerName = $merchant->name ?? $owner->name ?? '';
  @endphp

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h2 class="mb-0">{{ $ownersMode ? 'أصحاب النشاط ومنتجاتهم' : ('منتجات: '.$ownerName) }}</h2>
      @unless($ownersMode)
        <div class="small text-muted">
          @if($owner)
            صاحب النشاط: {{ $owner->name }} — {{ $owner->phone }}
          @endif
        </div>
      @endunless
    </div>
    @unless($ownersMode)
      <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">رجوع لكل أصحاب النشاط</a>
    @endunless
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

  @if($ownersMode)
    <form class="row g-2 mb-3">
      <div class="col-md-6">
        <input name="q" class="form-control" placeholder="بحث باسم صاحب النشاط أو المتجر أو الهاتف" value="{{ request('q') }}">
      </div>
      <div class="col-md-2"><button class="btn btn-primary w-100">بحث</button></div>
    </form>

    <div class="card"><div class="card-body table-responsive">
      <table class="table table-bordered align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>صاحب النشاط</th>
            <th>الهاتف</th>
            <th>المتجر</th>
            <th>القسم</th>
            <th>عدد الصور المضافة</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          @forelse($owners as $user)
          @php $store = $user->ownedMerchant; @endphp
          <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->phone ?: '—' }}</td>
            <td>{{ $store->name ?? 'لا يوجد متجر مرتبط' }}</td>
            <td>{{ $store->category?->name ?? '—' }}</td>
            <td>{{ (int) (($store->images_count ?? 0) ?: ($store->products_count ?? 0)) }}</td>
            <td>
              @if($store)
                <a href="{{ route('admin.products.index', ['merchant_id' => $store->id]) }}" class="btn btn-sm btn-info">عرض منتجاتهم</a>
              @else
                <a href="{{ route('admin.products.index', ['owner_id' => $user->id]) }}" class="btn btn-sm btn-outline-info">عرض منتجاتهم</a>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="text-center text-muted py-4">لا يوجد أصحاب نشاط حالياً.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
      {{ $owners->links() }}
    </div></div>
  @else
    <form class="row g-2 mb-3">
      @if($merchant)
        <input type="hidden" name="merchant_id" value="{{ $merchant->id }}">
      @elseif($owner)
        <input type="hidden" name="owner_id" value="{{ $owner->id }}">
      @endif
      <div class="col-md-4"><input name="q" class="form-control" placeholder="بحث في المنتجات..." value="{{ request('q') }}"></div>
      <div class="col-md-3">
        <select name="status" class="form-select">
          <option value="">كل الحالات</option>
          <option value="active" @selected(request('status')==='active')>مفعّل</option>
          <option value="inactive" @selected(request('status')==='inactive')>معطّل</option>
        </select>
      </div>
      <div class="col-md-2"><button class="btn btn-primary w-100">بحث</button></div>
    </form>

    @if(!$merchant)
      <div class="alert alert-warning">لا يوجد متجر مرتبط بهذا الحساب بعد، لذلك لا توجد منتجات للعرض.</div>
    @endif

    <div class="card"><div class="card-body table-responsive">
      <table class="table table-bordered">
        <thead><tr><th>#</th><th>المنتج</th><th>المتجر</th><th>السعر</th><th>الحالة</th><th>إجراءات</th></tr></thead>
        <tbody>
          @forelse($products as $p)
          <tr>
            <td>{{ $p->id }}</td>
            <td>{{ $p->name }}</td>
            <td>{{ $p->merchant?->name }}</td>
            <td>{{ number_format($p->price, 0) }} ج.م</td>
            <td><span class="badge {{ $p->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $p->is_active ? 'مفعّل' : 'معطّل' }}</span></td>
            <td class="d-flex gap-1 flex-wrap">
              <form method="POST" action="{{ route('admin.products.toggle', $p->id) }}">@csrf<button class="btn btn-sm btn-warning">{{ $p->is_active ? 'تعطيل' : 'تفعيل' }}</button></form>
              <form method="POST" action="{{ route('admin.products.destroy', $p->id) }}" onsubmit="return confirm('حذف المنتج؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">حذف</button></form>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center text-muted py-4">لم ينشر هذا صاحب النشاط أي منتجات بعد.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
      {{ $products->links() }}
    </div></div>
  @endif
</div>
@endsection
