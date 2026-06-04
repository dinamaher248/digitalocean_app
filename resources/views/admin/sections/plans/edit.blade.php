@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid">
    <h3 class="mb-3">تعديل الخطة: {{ $plan->name }}</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            برجاء مراجعة الحقول أدناه.
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.plans.update', $plan->id) }}">
                @method('PUT')
                @include('admin.sections.plans._form', ['buttonText' => 'حفظ التعديلات'])
            </form>
        </div>
    </div>
</div>
@endsection
