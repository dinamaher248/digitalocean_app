@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid">
    <h3 class="mb-3">
        @if(($type ?? 'governorate') === 'area')
            إضافة مدينة / حي جديد
        @else
            إضافة محافظة جديدة
        @endif
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
            <form method="POST" action="{{ route('admin.locations.store') }}">
                @include('admin.sections.locations._form', [
                    'location'   => $location,
                    'type'       => $type ?? 'governorate',
                    'parents'    => $parents ?? collect(),
                    'buttonText' => 'إضافة المكان'
                ])
            </form>
        </div>
    </div>
</div>
@endsection
