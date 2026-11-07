@extends('admin.layout.admin_app')
@section('content')
<div class="container-fluid"><h2>تعديل مستند</h2>
<form method="POST" action="{{ route('admin.documents.update', $document) }}" class="card p-3 mt-3">@csrf @method('PUT')@include('admin.sections.documents._form')</form></div>
@endsection
