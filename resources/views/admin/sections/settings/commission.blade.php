@extends('admin.layout.admin_app')
@section('content')
<div class="container-fluid">
  <h2 class="mb-3">إعدادات العمولة والمستحقات</h2>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <form method="POST" action="{{ route('admin.commission.update') }}" class="card p-3 mb-4">@csrf
    <div class="row g-3 mb-3">
      <div class="col-md-4"><label class="form-label">نسبة عمولة صاحب النشاط %</label><input type="number" step="0.01" name="merchant_commission_rate" class="form-control" value="{{ $settings['merchant_commission_rate'] }}" required></div>
      <div class="col-md-4"><label class="form-label">حد المديونية (ج.م)</label><input type="number" step="0.01" name="dues_debt_limit" class="form-control" value="{{ $settings['dues_debt_limit'] }}" required></div>
    </div>

    <hr class="my-4">
    <h5 class="mb-1">السائقون</h5>
    <p class="text-muted small mb-3">لو وقّفت خياراً يختفي من حساب السائق ولا يُحسب. لو فعّلت الاتنين يختار بينهم.</p>
    <div class="form-check form-switch mb-2">
      <input class="form-check-input" type="checkbox" name="driver_commission_enabled" value="1" id="driver_commission_on" {{ !empty($settings['driver_commission_enabled']) ? 'checked' : '' }}>
      <label class="form-check-label" for="driver_commission_on">تفعيل خيار العمولة</label>
    </div>
    <div class="mb-3 col-md-4">
      <label class="form-label">نسبة عمولة السائق %</label>
      <input type="number" step="0.01" name="driver_commission_rate" class="form-control" value="{{ $settings['driver_commission_rate'] }}" required>
    </div>
    <div class="form-check form-switch mb-2">
      <input class="form-check-input" type="checkbox" name="driver_fixed_fee_enabled" value="1" id="driver_fixed_on" {{ !empty($settings['driver_fixed_fee_enabled']) ? 'checked' : '' }}>
      <label class="form-check-label" for="driver_fixed_on">تفعيل خيار الاشتراك الثابت لكل طلب</label>
    </div>
    <div class="mb-3 col-md-4">
      <label class="form-label">السعر الثابت لكل طلب (ج.م)</label>
      <input type="number" step="0.01" min="0" name="driver_fixed_fee_amount" class="form-control" value="{{ $settings['driver_fixed_fee_amount'] ?? 10 }}">
    </div>

    <hr class="my-4">
    <h5 class="mb-1">أصحاب النشاط — قسم الطلبات</h5>
    <p class="text-muted small mb-3">لو فعّلت الخيارين يظهر لصاحب نشاط الطلبات الاختياران. لو وقّفت خياراً لا يظهر ولا يُحسب. أي تعديل في القيمة يظهر فوراً في حسابه.</p>

    <div class="form-check form-switch mb-2">
      <input class="form-check-input" type="checkbox" name="vendor_orders_commission_enabled" value="1" id="orders_commission_on" {{ !empty($settings['vendor_orders_commission_enabled']) ? 'checked' : '' }}>
      <label class="form-check-label" for="orders_commission_on">تفعيل خيار العمولة</label>
    </div>
    <div class="mb-3 col-md-4">
      <label class="form-label">نسبة العمولة %</label>
      <input type="number" step="0.01" min="0" name="vendor_orders_commission_percent" class="form-control" value="{{ $settings['vendor_orders_commission_percent'] ?? 5 }}">
    </div>

    <div class="form-check form-switch mb-2">
      <input class="form-check-input" type="checkbox" name="vendor_orders_fixed_fee_enabled" value="1" id="orders_fixed_on" {{ !empty($settings['vendor_orders_fixed_fee_enabled']) ? 'checked' : '' }}>
      <label class="form-check-label" for="orders_fixed_on">تفعيل خيار السعر الثابت لكل أوردر</label>
    </div>
    <div class="mb-3 col-md-4">
      <label class="form-label">السعر الثابت لكل أوردر (ج.م)</label>
      <input type="number" step="0.01" min="0" name="vendor_orders_fixed_fee_amount" class="form-control" value="{{ $settings['vendor_orders_fixed_fee_amount'] ?? 10 }}">
    </div>

    <hr class="my-4">
    <h5 class="mb-1">أصحاب النشاط — قسم الحجوزات</h5>
    @php $bookingsFree = empty($settings['vendor_bookings_commission_enabled']) && empty($settings['vendor_bookings_fixed_fee_enabled']); @endphp
    <p class="text-muted small mb-2">نفس نظام الطلبات. اتركه معطّلاً ليظل قسم الحجوزات مجانياً، وفعّله وقت ما تحب.</p>
    @if($bookingsFree)
      <div class="alert alert-success py-2 small mb-3">الحجوزات مجانية حالياً — لا تُحتسب أي مستحقات على أصحاب نشاط الحجوزات.</div>
    @endif

    <div class="form-check form-switch mb-2">
      <input class="form-check-input" type="checkbox" name="vendor_bookings_commission_enabled" value="1" id="bookings_commission_on" {{ !empty($settings['vendor_bookings_commission_enabled']) ? 'checked' : '' }}>
      <label class="form-check-label" for="bookings_commission_on">تفعيل خيار العمولة</label>
    </div>
    <div class="mb-3 col-md-4">
      <label class="form-label">نسبة العمولة %</label>
      <input type="number" step="0.01" min="0" name="vendor_bookings_commission_percent" class="form-control" value="{{ $settings['vendor_bookings_commission_percent'] ?? 5 }}">
    </div>

    <div class="form-check form-switch mb-2">
      <input class="form-check-input" type="checkbox" name="vendor_bookings_fixed_fee_enabled" value="1" id="bookings_fixed_on" {{ !empty($settings['vendor_bookings_fixed_fee_enabled']) ? 'checked' : '' }}>
      <label class="form-check-label" for="bookings_fixed_on">تفعيل خيار السعر الثابت لكل حجز</label>
    </div>
    <div class="mb-3 col-md-4">
      <label class="form-label">السعر الثابت لكل حجز (ج.م)</label>
      <input type="number" step="0.01" min="0" name="vendor_bookings_fixed_fee_amount" class="form-control" value="{{ $settings['vendor_bookings_fixed_fee_amount'] ?? 10 }}">
    </div>

    <hr class="my-4">
    <h5 class="mb-1">أصحاب النشاط — قسم خدمات أخرى</h5>
    <div class="alert alert-secondary py-2 small mb-0">
      قسم «خدمات أخرى» مجاني بالكامل ولا يخضع لأي عمولة أو سعر ثابت — لا توجد إعدادات فوترة له.
    </div>

    <button class="btn btn-primary mt-3">حفظ الإعدادات</button>
  </form>

  <h4>طلبات سداد المستحقات</h4>
  <div class="card"><div class="card-body">
    @forelse($settlements as $req)
    <div class="border rounded p-3 mb-2">
      <strong>{{ $req->user?->name }}</strong> — {{ $req->account_type === 'driver' ? 'سائق' : 'صاحب نشاط' }} — {{ $req->amount }} ج.م — <span class="badge bg-secondary">{{ $req->status }}</span>
      @if($req->status === 'pending')
      <div class="mt-2">
        <a href="{{ asset('storage/'.$req->payment_proof) }}" target="_blank">صورة التحويل</a>
        <form class="d-inline" method="POST" action="{{ route('admin.settlements.approve', $req->id) }}">@csrf<button class="btn btn-sm btn-success">تأكيد</button></form>
        <form class="d-inline" method="POST" action="{{ route('admin.settlements.reject', $req->id) }}">@csrf<button class="btn btn-sm btn-danger">رفض</button></form>
      </div>
      @endif
    </div>
    @empty
      <div class="text-muted">لا توجد طلبات سداد حالياً.</div>
    @endforelse
  </div></div>
</div>
@endsection
