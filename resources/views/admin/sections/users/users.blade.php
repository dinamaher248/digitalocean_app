@extends('admin.layout.admin_app')

@section('content')
<div class="container-fluid">
    <h2 class="mb-3">إدارة المستخدمين</h2>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @php
        $segment = $segment ?? 'current';
        $search = $search ?? '';
        $filter = $filter ?? 'all';
        $cardClass = function ($active) {
            return $active
                ? 'card text-center p-3 text-decoration-none border-primary bg-primary text-white h-100'
                : 'card text-center p-3 text-decoration-none text-body h-100';
        };
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <a class="{{ $cardClass($filter === 'all') }}" href="{{ route('admin.user.index', ['type' => 'all']) }}">
                <small>الكل</small>
                <h4 class="mb-0">{{ $stats['all'] ?? 0 }}</h4>
            </a>
        </div>
        <div class="col-md-3">
            <a class="{{ $cardClass($filter === 'users') }}" href="{{ route('admin.user.index', ['type' => 'users']) }}">
                <small>عملاء</small>
                <h4 class="mb-0">{{ $stats['users'] ?? 0 }}</h4>
            </a>
        </div>
        <div class="col-md-3">
            <a class="{{ $cardClass($filter === 'drivers') }}" href="{{ route('admin.user.index', ['type' => 'drivers', 'segment' => 'current']) }}">
                <small>سائقون</small>
                <h4 class="mb-0">{{ $stats['drivers'] ?? 0 }}</h4>
            </a>
        </div>
        <div class="col-md-3">
            <a class="{{ $cardClass($filter === 'merchants') }}" href="{{ route('admin.user.index', ['type' => 'merchants']) }}">
                <small>أصحاب نشاط</small>
                <h4 class="mb-0">{{ $stats['merchants'] ?? 0 }}</h4>
            </a>
        </div>
    </div>

    @if($filter === 'drivers')
    <div class="mb-3 p-3 rounded border-start border-4 border-primary bg-body-tertiary">
        <div class="small text-muted mb-2">تحت السائقين:</div>
        <div class="btn-group flex-wrap" role="group">
            <a href="{{ route('admin.user.index', array_filter(['type' => 'drivers', 'segment' => 'current', 'search' => $search ?: null])) }}"
               class="btn {{ $segment === 'current' ? 'btn-primary' : 'btn-outline-primary' }}">
                سائقين حاليين (معتمدين) — {{ $stats['drivers_current'] ?? 0 }}
            </a>
            <a href="{{ route('admin.user.index', array_filter(['type' => 'drivers', 'segment' => 'new', 'search' => $search ?: null])) }}"
               class="btn {{ $segment === 'new' ? 'btn-warning' : 'btn-outline-warning' }}">
                سائقين لسه ما تمش مراجعتهم — {{ $stats['drivers_new'] ?? 0 }}
            </a>
        </div>
    </div>
    @endif

    <form method="GET" class="mb-3">
        <input type="hidden" name="type" value="{{ $filter }}">
        @if($filter === 'drivers')
            <input type="hidden" name="segment" value="{{ $segment }}">
        @endif
        <div class="input-group">
            <input type="text" name="search" class="form-control"
                   placeholder="بحث بالاسم أو الهاتف أو البريد أو رقم المستخدم"
                   value="{{ $search }}">
            <button type="submit" class="btn btn-primary">بحث</button>
            @if($search !== '')
                <a href="{{ route('admin.user.index', array_filter(['type' => $filter, 'segment' => $filter === 'drivers' ? $segment : null])) }}"
                   class="btn btn-outline-secondary">مسح</a>
            @endif
        </div>
        @if($search !== '')
            <div class="form-text">نتائج البحث عن: <strong>{{ $search }}</strong></div>
        @endif
    </form>

    <div class="card"><div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>#</th><th>الاسم</th><th>البريد</th><th>الهاتف</th><th>النوع</th><th>الحالة</th><th>إجراءات</th></tr></thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone }}</td>
                    <td>
                        @if($user->title === 'driver')<span class="badge bg-info">سائق</span>
                        @elseif(in_array($user->title, ['merchant', 'vendor'], true))<span class="badge bg-primary">صاحب نشاط</span>
                        @else<span class="badge bg-secondary">عميل</span>@endif
                    </td>
                    <td>
                        @if($user->is_suspended)<span class="badge bg-danger">موقوف</span>
                        @elseif($user->is_approved)<span class="badge bg-success">معتمد</span>
                        @else<span class="badge bg-warning">معلق — بانتظار المراجعة</span>@endif
                    </td>
                    <td>
                        <a href="{{ route('admin.user.details', $user->id) }}" class="btn btn-sm btn-primary">مراجعة</a>
                        @if(in_array($user->title, ['merchant', 'vendor'], true))
                            @if($user->ownedMerchant)
                                <a href="{{ route('admin.products.index', ['merchant_id' => $user->ownedMerchant->id]) }}" class="btn btn-sm btn-info">عرض المنتجات</a>
                            @else
                                <a href="{{ route('admin.products.index', ['owner_id' => $user->id]) }}" class="btn btn-sm btn-outline-info">عرض المنتجات</a>
                            @endif
                        @endif
                        @if($filter === 'drivers' && $segment === 'new' && !$user->is_approved)
                            <form method="POST" action="{{ route('admin.user.approve', $user->id) }}" class="d-inline"
                                  onsubmit="return confirm('اعتماد حساب {{ $user->name }}؟');">
                                @csrf
                                <button class="btn btn-sm btn-success">اعتماد</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.user.delete', $user->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('حذف؟')">حذف</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        @if($search !== '')
                            لا توجد نتائج مطابقة لـ «{{ $search }}».
                        @elseif($filter === 'drivers' && $segment === 'new')
                            لا يوجد سائقون بانتظار المراجعة حالياً.
                        @elseif($filter === 'drivers' && $segment === 'current')
                            لا يوجد سائقون معتمدون حالياً.
                        @else
                            لا يوجد بيانات لعرضها حالياً.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if(method_exists($users, 'links'))
            {{ $users->links() }}
        @endif
    </div></div>
</div>
@endsection
