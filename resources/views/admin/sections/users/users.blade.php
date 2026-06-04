@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">إدارة المستخدمين</h2>
    </div>

    {{-- إشعارات فلاش للنجاح أو الخطأ --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    {{-- كروت الإحصائيات --}}
    <div class="row mb-3">
        <div class="col-md-4 mb-2">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">إجمالي المستخدمين</h6>
                    <h3 class="mb-0">{{ $stats['all'] ?? $users->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">عدد السائقين</h6>
                    <h3 class="mb-0">{{ $stats['drivers'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">عدد العملاء</h6>
                    <h3 class="mb-0">{{ $stats['users'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- تبويبات الفلتر: الكل / السائقون / العملاء --}}
    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $filter === 'all' ? 'active' : '' }}"
               href="{{ route('admin.user.index', ['type' => 'all']) }}">
                الكل
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $filter === 'drivers' ? 'active' : '' }}"
               href="{{ route('admin.user.index', ['type' => 'drivers']) }}">
                السائقون
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $filter === 'users' ? 'active' : '' }}"
               href="{{ route('admin.user.index', ['type' => 'users']) }}">
                العملاء
            </a>
        </li>
    </ul>

    <div class="card">
        <div class="card-body">
            <table id="usersTable" class="display table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>البريد الإلكتروني</th>
                        <th>رقم الهاتف</th>
                        <th>النوع</th>
                        <th>تاريخ الإنشاء</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone }}</td>
                            <td>
                                @if($user->title === 'driver')
                                    <span class="badge bg-info">سائق</span>
                                @else
                                    <span class="badge bg-secondary">عميل</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at?->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('admin.user.delete', [$user->id]) }}"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟');">
                                    حذف
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('#usersTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            language: {
                sEmptyTable:     "لا توجد بيانات متاحة في الجدول",
                sLoadingRecords: "جارٍ التحميل...",
                sProcessing:     "جارٍ المعالجة...",
                sLengthMenu:     "أظهر _MENU_ مدخلات",
                sZeroRecords:    "لم يتم العثور على نتائج",
                sInfo:           "إظهار _START_ إلى _END_ من أصل _TOTAL_ مدخل",
                sInfoEmpty:      "إظهار 0 إلى 0 من أصل 0 مدخل",
                sInfoFiltered:   "(منتقاة من مجموع _MAX_ مدخل)",
                sSearch:         "بحث:",
                oPaginate: {
                    sFirst:    "الأولى",
                    sPrevious: "السابق",
                    sNext:     "التالي",
                    sLast:     "الأخيرة"
                }
            }
        });
    });
</script>
@endsection

@section('styles')
<style>
    .nav-pills .nav-link {
        border-radius: 999px;
    }
    .nav-pills .nav-link.active {
        background-color: #e6910a;
        border-color: #e6910a;
    }
</style>
@endsection
