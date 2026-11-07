@extends('admin.layout.admin_app')
@section('content')
<div class="container-fluid">
  <h2 class="mb-3">تعديل وسيلة النقل والنطاق</h2>
  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif
  <form method="POST" action="{{ route('admin.vehicle_types.update', $type) }}" class="card p-3">
    @csrf
    @method('PUT')
    @include('admin.sections.vehicle_types._form', ['type' => $type])
  </form>
</div>
@endsection
