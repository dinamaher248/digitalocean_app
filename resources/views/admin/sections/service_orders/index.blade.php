@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid">
  <h2 class="mb-3">طلبات الخدمات</h2>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

  @foreach($orders as $order)
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <strong>#{{ $order->id }} — {{ $order->merchant?->name }}</strong>
        <span class="badge bg-secondary">{{ $order->status }}</span>
      </div>
      <p class="mb-1">العميل: {{ $order->user?->name }} | {{ $order->user?->phone }}</p>
      <p class="mb-1">المنتج: {{ $order->product_name }} × {{ $order->quantity }} = {{ $order->total_price }} ج.م</p>
      <p class="mb-1">العنوان: {{ $order->delivery_address }}</p>
      <p class="mb-1">الدفع: {{ $order->payment_method }} @if($order->notes) | ملاحظات: {{ $order->notes }}@endif</p>
      @if($order->payment_proof)
        <p><a href="{{ asset('storage/'.$order->payment_proof) }}" target="_blank">عرض صورة التحويل</a></p>
      @endif

      @if(in_array($order->status, ['new', 'awaiting_payment_review']))
      <form class="d-inline" method="POST" action="{{ route('admin.service_orders.accept', $order->id) }}">@csrf
        <button class="btn btn-success btn-sm">قبول الطلب</button>
      </form>
      <form class="d-inline" method="POST" action="{{ route('admin.service_orders.reject', $order->id) }}">@csrf
        <button class="btn btn-danger btn-sm">رفض الطلب</button>
      </form>
      @endif

      @if($order->status === 'awaiting_driver' && $order->deliveryOffers->isNotEmpty())
      <hr>
      <strong>عروض السائقين:</strong>
      @foreach($order->deliveryOffers as $offer)
        <div class="d-flex align-items-center gap-2 mt-1">
          <span>{{ $offer->driver?->name }} — {{ $offer->amount }} ج.م</span>
          @if($offer->status === 'pending')
          <form method="POST" action="{{ route('admin.service_orders.accept_driver_offer', [$order->id, $offer->id]) }}">@csrf
            <button class="btn btn-sm btn-primary">قبول السائق</button>
          </form>
          @endif
        </div>
      @endforeach
      @endif
    </div>
  </div>
  @endforeach

  {{ $orders->links() }}
</div>
@endsection
