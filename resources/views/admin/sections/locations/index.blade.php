@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">إدارة الأماكن</h2>
            <small class="text-muted">
                المستوى الحالي:
                <strong>{{ $typeLabel ?? '' }}</strong>
                @if(!empty($parent))
                    / {{ $parent->name_ar }}
                @endif
            </small>
        </div>

        {{-- زر إضافة حسب المستوى الحالي --}}
        <div>
            @php
                // نوع المكان اللي هيتضاف في هذا الـ scope
                // لو واقفين على المحافظات → إضافة محافظة جديدة
                // لو واقفين على مناطق محافظة معيّنة → إضافة منطقة جديدة تحت نفس المحافظة
                // لو واقفين على أحياء منطقة معيّنة → إضافة حي جديد تحت نفس المنطقة
                $createType = $type ?? 'governorate';
            @endphp

            <a href="{{ route('admin.locations.create', ['type' => $createType, 'parent_id' => $parent->id ?? null]) }}"
               class="btn btn-primary">
                <i class="fa fa-plus"></i>
                @if($createType === 'governorate')
                    إضافة محافظة
                @elseif($createType === 'zone')
                    إضافة منطقة
                @else
                    إضافة حي / قسم
                @endif
            </a>
        </div>
    </div>

    {{-- رسائل فلاش --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- فلاتر سريعة للمستوى --}}
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-2">
            <a href="{{ route('admin.locations.index', ['type' => 'governorate']) }}"
               class="btn btn-sm {{ ($type ?? '') === 'governorate' ? 'btn-primary' : 'btn-outline-secondary' }}">
                المحافظات
            </a>
            <a href="{{ route('admin.locations.index', ['type' => 'zone']) }}"
               class="btn btn-sm {{ ($type ?? '') === 'zone' ? 'btn-primary' : 'btn-outline-secondary' }}">
                المناطق
            </a>
            <a href="{{ route('admin.locations.index', ['type' => 'district']) }}"
               class="btn btn-sm {{ ($type ?? '') === 'district' ? 'btn-primary' : 'btn-outline-secondary' }}">
                الأحياء / الأقسام
            </a>
            <a href="{{ route('admin.locations.index', ['type' => 'all']) }}"
               class="btn btn-sm {{ ($type ?? '') === 'all' ? 'btn-success' : 'btn-outline-success' }}">
                كل الأماكن
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="locationsTable" class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم (عربي)</th>
                        <th>الاسم (إنجليزي)</th>
                        <th>النوع</th>
                        <th>الأب</th>
                        <th>رئيسي؟</th>
                        <th>مُفعّل؟</th>
                        <th>الترتيب</th>
                        <th>الأماكن التابعة</th>
                        <th>العمليات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($locations as $location)
                        @php
                            // نحدّد نوع الأطفال الممكن
                            $childrenType = null;
                            if ($location->type === 'governorate') {
                                $childrenType = 'zone';      // مناطق
                            } elseif ($location->type === 'zone') {
                                $childrenType = 'district';  // أحياء
                            }
                        @endphp
                        <tr>
                            <td>{{ $location->id }}</td>
                            <td>{{ $location->name_ar }}</td>
                            <td>{{ $location->name_en ?? '-' }}</td>
                            <td>
                                @if($location->type === 'governorate')
                                    محافظة
                                @elseif($location->type === 'zone')
                                    منطقة
                                @else
                                    حي / قسم
                                @endif
                            </td>
                            <td>{{ $location->parent?->name_ar ?? '-' }}</td>
                            <td>
                                @if($location->is_primary)
                                    <span class="badge bg-success">رئيسية</span>
                                @else
                                    <span class="badge bg-secondary">عادية</span>
                                @endif
                            </td>
                            <td>
                                @if($location->is_active)
                                    <span class="badge bg-success">مُفعّل</span>
                                @else
                                    <span class="badge bg-danger">موقوف</span>
                                @endif
                            </td>
                            <td>{{ $location->sort_order }}</td>

                            {{-- الأماكن التابعة: لازم دايمًا يبقى في <td> حتى لو مفيش أطفال --}}
                            <td>
                                @if($childrenType)
                                    <a href="{{ route('admin.locations.index', [
                                            'type'      => $childrenType,
                                            'parent_id' => $location->id,
                                        ]) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        الأماكن التابعة
                                        ({{ $location->children()->count() }})
                                    </a>
                                @else
                                    -
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('admin.locations.edit', $location) }}"
                                   class="btn btn-sm btn-info">
                                    تعديل
                                </a>

                                <form action="{{ route('admin.locations.destroy', $location) }}"
                                      method="POST"
                                      style="display:inline-block;"
                                      onsubmit="return confirm('هل أنتِ متأكدة من حذف هذا المكان؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        حذف
                                    </button>
                                </form>
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
        $('#locationsTable').DataTable({
            // لو عندك ملف ترجمة عربي لـ DataTables حطيه هنا
            // language: {
            //     url: '{{ asset('admin_assets/plugins/datatables/ar.json') }}'
            // }
        });
    });
</script>
@endsection
