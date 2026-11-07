@extends('admin.layout.admin_app')
@section('content')
<div class="container-fluid"><h2>إضافة مستند</h2>
<form method="POST" action="{{ route('admin.documents.store') }}" class="card p-3 mt-3">@include('admin.sections.documents._form')</form></div>
@endsection
