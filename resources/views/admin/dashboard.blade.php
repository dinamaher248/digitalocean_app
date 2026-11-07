@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid">
  <h2 class="mb-4">الرئيسية / الإحصائيات</h2>

  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card text-center p-3"><div class="text-muted">إجمالي المستخدمين</div><h3>{{ $stats['users_total'] }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><div class="text-muted">العملاء</div><h3>{{ $stats['customers'] }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><div class="text-muted">السائقون</div><h3>{{ $stats['drivers'] }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><div class="text-muted">أصحاب النشاط</div><h3>{{ $stats['merchants'] }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><div class="text-muted">المتاجر النشطة</div><h3>{{ $stats['active_merchants'] }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><div class="text-muted">المنتجات</div><h3>{{ $stats['products'] }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><div class="text-muted">طلبات الخدمات</div><h3>{{ $stats['service_orders'] }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><div class="text-muted">طلبات معلقة</div><h3>{{ $stats['pending_orders'] }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><div class="text-muted">إيرادات مكتملة</div><h3>{{ number_format($stats['orders_revenue'], 0) }} ج.م</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><div class="text-muted">إجمالي العمولات</div><h3>{{ number_format($stats['commission_total'], 0) }} ج.م</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><div class="text-muted">مستحقات معلقة</div><h3>{{ number_format($stats['dues_outstanding'], 0) }} ج.م</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><div class="text-muted">طلبات سداد بانتظار المراجعة</div><h3>{{ $stats['pending_settlements'] }}</h3></div></div>
  </div>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header fw-bold">أحدث الطلبات</div>
        <div class="card-body p-0">
          <table class="table mb-0">
            <thead><tr><th>#</th><th>المتجر</th><th>الحالة</th><th>المبلغ</th></tr></thead>
            <tbody>
              @forelse($recentOrders as $o)
              <tr>
                <td>{{ $o->id }}</td>
                <td>{{ $o->merchant?->name ?? '—' }}</td>
                <td>{{ $o->status }}</td>
                <td>{{ number_format($o->total_price, 0) }} ج.م</td>
              </tr>
              @empty
              <tr><td colspan="4" class="text-center">لا توجد طلبات</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header fw-bold">أحدث المستخدمين</div>
        <div class="card-body p-0">
          <table class="table mb-0">
            <thead><tr><th>الاسم</th><th>النوع</th><th>الهاتف</th></tr></thead>
            <tbody>
              @forelse($recentUsers as $u)
              <tr>
                <td><a href="{{ route('admin.user.details', $u->id) }}">{{ $u->name }}</a></td>
                <td>{{ $u->title }}</td>
                <td>{{ $u->phone }}</td>
              </tr>
              @empty
              <tr><td colspan="3" class="text-center">لا يوجد مستخدمون</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
