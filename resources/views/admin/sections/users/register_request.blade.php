@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid py-3">

    {{-- العنوان + الإحصائيات --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">طلبات اشتراك السائقين</h2>

        <div>
            <span class="badge bg-warning text-dark me-1">
                قيد المراجعة: {{ $stats['pending'] ?? 0 }}
            </span>
            <span class="badge bg-success me-1">
                المقبولة: {{ $stats['approved'] ?? 0 }}
            </span>
            <span class="badge bg-danger">
                المرفوضة: {{ $stats['rejected'] ?? 0 }}
            </span>
        </div>
    </div>

    {{-- رسائل النجاح / الخطأ --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- فلتر بسيط على الحالة (اختياري حالياً) --}}
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="pending"  {{ ($status ?? 'pending') == 'pending' ? 'selected' : '' }}>قيد المراجعة</option>
                <option value="approved" {{ ($status ?? '') == 'approved' ? 'selected' : '' }}>المقبولة</option>
                <option value="rejected" {{ ($status ?? '') == 'rejected' ? 'selected' : '' }}>المرفوضة</option>
                <option value="all"      {{ ($status ?? '') == 'all' ? 'selected' : '' }}>الكل</option>
            </select>
        </div>
    </form>

    <div class="table-responsive">
        <table id="subscriptionsTable" class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th>#</th>
                    <th>اسم السائق</th>
                    <th>رقم الجوال</th>
                    <th>الباقة</th>
                    <th>السعر (ج.م)</th>
                    <th>المدة (شهور)</th>
                    <th>الحالة</th>
                    <th>إيصال الدفع</th>
                    <th>تاريخ الطلب</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registerRequests as $subscription)
                    <tr>
                        <td>{{ $subscription->id }}</td>
                        <td>{{ $subscription->user->name ?? '-' }}</td>
                        <td>{{ $subscription->user->phone ?? '-' }}</td>
                        <td>{{ $subscription->plan->name ?? '-' }}</td>
                        <td>{{ $subscription->price ?? '-' }}</td>
                        <td>{{ $subscription->months ?? '-' }}</td>

                        {{-- الحالة --}}
                        <td class="text-center">
                            @if ($subscription->status == 'approved')
                                <span class="badge bg-success">مقبول</span>
                            @elseif ($subscription->status == 'rejected')
                                <span class="badge bg-danger">مرفوض</span>
                            @else
                                <span class="badge bg-warning text-dark">قيد المراجعة</span>
                            @endif
                        </td>

                        {{-- صورة إيصال الدفع --}}
                        <td class="text-center">
                            @if ($subscription->payment_image)
                                <button type="button"
                                        class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#paymentModal{{ $subscription->id }}">
                                    عرض
                                </button>

                                <!-- Modal -->
                                <div class="modal fade"
                                     id="paymentModal{{ $subscription->id }}"
                                     tabindex="-1"
                                     aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    إيصال الدفع - طلب رقم {{ $subscription->id }}
                                                </h5>
                                                <button type="button"
                                                        class="btn-close"
                                                        data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-center">
                                                <img src="{{ asset('storage/' . $subscription->payment_image) }}"
                                                     alt="إيصال الدفع"
                                                     class="img-fluid rounded">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted">لا يوجد</span>
                            @endif
                        </td>

                        {{-- تاريخ الطلب --}}
                        <td>{{ optional($subscription->created_at)->format('Y-m-d H:i') }}</td>

                        {{-- الإجراءات --}}
                        <td class="text-center">
                            @if ($subscription->status == 'pending')
                                <a href="{{ route('admin.subscriptions.update', [$subscription->id, 'approved']) }}"
                                   class="btn btn-success btn-sm mb-1"
                                   onclick="return confirm('تأكيد قبول هذا الطلب؟');">
                                    قبول
                                </a>
                                <a href="{{ route('admin.subscriptions.update', [$subscription->id, 'rejected']) }}"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('تأكيد رفض هذا الطلب؟');">
                                    رفض
                                </a>
                            @else
                                <span class="text-muted">تمت المراجعة</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center">
                            لا توجد طلبات حالياً.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('#subscriptionsTable').DataTable();
    });
</script>
@endsection
