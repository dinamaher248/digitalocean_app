@extends('admin.layout.admin_app')

@section('content')
<div class="container">
  <h2 class="my-4">الإشعارات</h2>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

  @unless($fcmReady)
  <div class="alert alert-warning">
    <strong>الإشعارات معطّلة حالياً.</strong>
    ملف Firebase Service Account غير موجود على السيرفر، ولن يصل أي إشعار حتى يُرفع.
    <div class="small mt-2">المسار المتوقع: <code dir="ltr">{{ $fcmExpectedPath }}</code></div>
  </div>
  @endunless

  <form method="POST" action="{{ route('admin.send_notification_to_topic_post') }}" class="row g-3">
    @csrf
    <div class="col-12">
      <label class="form-label">الجمهور المستهدف</label>
      <select name="audience" id="audience" class="form-select" required>
        <option value="all">الجميع (Topic)</option>
        <option value="customers">العملاء فقط</option>
        <option value="drivers">السائقون فقط</option>
        <option value="merchants">أصحاب النشاط فقط</option>
        <option value="debtors">أصحاب المديونيات ({{ $debtorsCount }})</option>
        <option value="user">مستخدم محدد</option>
      </select>
    </div>

    <div class="col-12" id="userBox" style="display:none">
      <label class="form-label">اختر المستخدم</label>
      <select name="user_id" class="form-select">
        <option value="">—</option>
        @foreach($users as $u)
        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->title }}) — {{ $u->phone }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-12" id="debtorsBox" style="display:none">
      <div class="card p-3">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">من عليهم مديونية</label>
            <select name="debtors_scope" class="form-select">
              <option value="all">السائقون وأصحاب النشاط</option>
              <option value="drivers">السائقون فقط</option>
              <option value="merchants">أصحاب النشاط فقط</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">الحد الأدنى للمديونية (ج.م)</label>
            <input type="number" step="0.01" min="0" name="debtors_min_amount" class="form-control" value="1">
          </div>
        </div>
        <div class="small text-body-secondary mt-3">
          يمكنك استخدام <code dir="ltr">{amount}</code> لقيمة مديونية كل مستخدم،
          و<code dir="ltr">{name}</code> لاسمه، داخل العنوان أو النص.
          <br>
          مثال: <span dir="ltr">عليك مبلغ مستحق {amount} ج.م، برجاء السداد.</span>
        </div>
      </div>
    </div>

    <div class="col-12"><label class="form-label">عنوان الإشعار</label><input name="head" class="form-control" required></div>
    <div class="col-12"><label class="form-label">نص الإشعار</label><textarea name="desc" class="form-control" rows="4" required></textarea></div>
    <div class="col-12">
      <label class="form-label">نوع الإشعار</label>
      <select name="action" class="form-select">
        <option value="general">رسالة عامة</option>
        <option value="admin_alert">تنبيه من الإدارة</option>
        <option value="app_update">تحديث التطبيق</option>
      </select>
    </div>
    <div class="col-12"><label class="form-label">رابط (اختياري)</label><input type="url" name="url" class="form-control"></div>
    <div class="col-12"><button class="btn btn-primary w-100">إرسال الإشعار</button></div>
  </form>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('audience').addEventListener('change', function () {
  document.getElementById('userBox').style.display = this.value === 'user' ? 'block' : 'none';
  document.getElementById('debtorsBox').style.display = this.value === 'debtors' ? 'block' : 'none';
});
</script>
@endsection
