@extends('admin.layout.admin_app')

@section('content')
<div class="container" style="max-width:700px">
  <h2 class="mb-3">إضافة متجر</h2>
  <form method="POST" action="{{ route('admin.merchants.store') }}" enctype="multipart/form-data" class="row g-3">
    @csrf
    <div class="col-12">
      <label class="form-label">القسم</label>
      <select name="category_id" class="form-select" required>
        @foreach($categories as $cat)
          <option value="{{ $cat->id }}">{{ $cat->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-12">
      <label class="form-label">اسم المتجر</label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="col-12">
      <label class="form-label">صاحب النشاط (اختياري)</label>
      <select name="owner_user_id" class="form-select">
        <option value="">— بدون —</option>
        @foreach($owners as $owner)
          <option value="{{ $owner->id }}">{{ $owner->name }} ({{ $owner->phone }})</option>
        @endforeach
      </select>
    </div>
    <div class="col-12">
      <label class="form-label">رقم التواصل</label>
      <input type="text" name="phone" class="form-control">
    </div>
    <div class="col-12">
      <label class="form-label">العنوان</label>
      <input type="text" name="address" class="form-control">
    </div>
    <div class="col-6">
      <label class="form-label">خط العرض</label>
      <input type="text" name="lat" class="form-control">
    </div>
    <div class="col-6">
      <label class="form-label">خط الطول</label>
      <input type="text" name="lng" class="form-control">
    </div>
    <div class="col-12">
      <label class="form-label">رقم المحفظة</label>
      <input type="text" name="wallet_number" class="form-control">
    </div>
    <div class="col-12">
      <label class="form-label">صورة المتجر</label>
      <input type="file" name="image" class="form-control" accept="image/*">
    </div>
    <div class="col-12">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pay_cash" value="1" checked> كاش</div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pay_insta" value="1"> انستا باي</div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pay_on_delivery" value="1" checked> دفع عند الاستلام</div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pay_wallet" value="1"> محفظة إلكترونية</div>
    </div>
    <div class="col-12"><button class="btn btn-primary w-100">حفظ</button></div>
  </form>
</div>
@endsection
