@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>إدارة الخطط</h2>
        <a href="{{ route('admin.plans.create') }}" class="btn btn-primary">
            + إضافة خطة جديدة
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            حدثت أخطاء أثناء العملية، برجاء مراجعة البيانات.
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <table id="plansTable" class="display table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>اسم الخطة</th>
                        <th>السعر الأساسي</th>
                        <th>قيمة الخصم</th>
                        <th>السعر بعد الخصم</th>
                        <th>مدة الخطة</th>
                        <th>السائقين</th>
                        <th>أصحاب النشاط</th>
                        <th>الحالة</th>
                        <th>تاريخ الإضافة</th>
                        <th style="width: 150px;">العمليات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        @php
                            $finalPrice = max(0, $plan->price - $plan->offer);
                        @endphp
                        <tr>
                            <td>{{ $plan->id }}</td>
                            <td>{{ $plan->name }}</td>
                            <td>{{ number_format($plan->price) }} جنيه</td>
                            <td>{{ number_format($plan->offer) }} جنيه</td>
                            <td><strong>{{ number_format($finalPrice) }} جنيه</strong></td>
                            <td>{{ $plan->days }} يوم</td>
                            <td>{{ ($plan->for_drivers ?? true) ? '✓' : '—' }}</td>
                            <td>{{ ($plan->for_merchants ?? false) ? '✓' : '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.plans.toggle', $plan) }}" class="d-inline">@csrf
                                    <button class="btn btn-sm {{ ($plan->is_active ?? true) ? 'btn-success' : 'btn-secondary' }}">{{ ($plan->is_active ?? true) ? 'مفعّلة' : 'موقوفة' }}</button>
                                </form>
                            </td>
                            <td>{{ $plan->created_at?->format('Y-m-d') }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('admin.plans.edit', $plan->id) }}" class="btn btn-sm btn-info">
                                        تعديل
                                    </a>

                                    <form action="{{ route('admin.plans.destroy', $plan->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('هل أنت متأكد من حذف هذه الخطة؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center">
                                لا توجد خطط مسجلة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('#plansTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ar.json'
            }
        });
    });
</script>
@endsection
