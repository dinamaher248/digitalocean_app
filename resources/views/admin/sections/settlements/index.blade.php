@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid">
  <h2 class="mb-3">طلبات سداد المستحقات</h2>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

  @foreach($requests as $req)
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <strong>{{ $req->user?->name }}</strong>
        <span class="badge bg-secondary">{{ $req->status }}</span>
      </div>
      <p class="mb-1">نوع الحساب: {{ $req->account_type === 'driver' ? 'سائق' : 'صاحب نشاط' }}</p>
      <p class="mb-1">المبلغ المستحق: {{ $req->amount }} ج.م</p>
      <p class="mb-1">تاريخ الإرسال: {{ $req->created_at }}</p>
      <p><a href="{{ asset('storage/'.$req->payment_proof) }}" target="_blank">عرض صورة التحويل</a></p>
      @if($req->status === 'pending')
      <form class="d-inline" method="POST" action="{{ route('admin.settlements.approve', $req->id) }}">@csrf
        <button class="btn btn-success btn-sm">تأكيد السداد</button>
      </form>
      <form class="d-inline" method="POST" action="{{ route('admin.settlements.reject', $req->id) }}">@csrf
        <button class="btn btn-danger btn-sm">رفض السداد</button>
      </form>
      @endif
    </div>
  </div>
  @endforeach

  {{ $requests->links() }}
</div>
@endsection
