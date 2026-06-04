@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid">
    <h3 class="mb-3">
        تعديل المكان: {{ $location->name_ar }}
    </h3>

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
            <form method="POST" action="{{ route('admin.locations.update', $location) }}">
                @csrf
                @method('PUT')

                @include('admin.sections.locations._form', [
                    'location'   => $location,
                    'type'       => $type ?? $location->type,
                    'parents'    => $parents ?? collect(),
                    'buttonText' => 'حفظ التعديلات'
                ])
            </form>
        </div>
    </div>
</div>
@endsection
