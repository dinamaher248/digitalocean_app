@extends('admin.layout.admin_app')
@section('content')
<div class="container-fluid"><h2>تعديل قسم</h2>
<form method="POST" action="{{ route('admin.categories.update', $category) }}" class="card p-3 mt-3" enctype="multipart/form-data">
  @csrf
  @method('PUT')
  @include('admin.sections.categories._form')
</form></div>
@endsection
