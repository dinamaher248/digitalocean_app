@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>المتاجر</h2>
    <a href="{{ route('admin.merchants.create') }}" class="btn btn-primary">إضافة متجر</a>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>#</th>
        <th>الاسم</th>
        <th>القسم</th>
        <th>مفتوح</th>
        <th>إجراءات</th>
      </tr>
    </thead>
    <tbody>
      @foreach($merchants as $merchant)
      <tr>
        <td>{{ $merchant->id }}</td>
        <td>{{ $merchant->name }}</td>
        <td>{{ $merchant->category?->name }}</td>
        <td>{{ $merchant->is_open ? 'نعم' : 'لا' }}</td>
        <td>
          <a href="{{ route('admin.products.index', ['merchant_id' => $merchant->id]) }}" class="btn btn-sm btn-info">عرض منتجاتهم</a>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  {{ $merchants->links() }}
</div>
@endsection
