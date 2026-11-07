@php
  $isEdit = isset($type);
@endphp
<div class="row g-3">
  <div class="col-md-4">
    <label class="form-label">المفتاح (إنجليزي)</label>
    <input type="text" name="key" class="form-control" required
           value="{{ old('key', $type->key ?? '') }}"
           placeholder="scooter">
    <div class="form-text">مثال: motorcycle / scooter / tricycle</div>
  </div>
  <div class="col-md-4">
    <label class="form-label">الاسم بالعربي</label>
    <input type="text" name="name_ar" class="form-control" required
           value="{{ old('name_ar', $type->name_ar ?? $type->name ?? '') }}">
  </div>
  <div class="col-md-4">
    <label class="form-label">الاسم بالإنجليزي</label>
    <input type="text" name="name_en" class="form-control"
           value="{{ old('name_en', $type->name_en ?? '') }}">
  </div>
  <div class="col-md-4">
    <label class="form-label">يظهر تحت</label>
    <select name="category" class="form-select" required>
      @php $cat = old('category', $type->category ?? 'quick_trip'); @endphp
      <option value="quick_trip" @selected($cat === 'quick_trip')>مشوار سريع (موتوسيكل / سكوتر…)</option>
      <option value="goods" @selected($cat === 'goods')>نقل بضائع (تروسيكل / نقل…)</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">نطاق الظهور والإشعار (كم)</label>
    <input type="number" step="0.1" min="0.5" max="500" name="service_radius_km" class="form-control" required
           value="{{ old('service_radius_km', $type->service_radius_km ?? 7) }}">
    <div class="form-text">يظهر للسائقين داخل هذا النطاق من موقع الاستلام</div>
  </div>
  <div class="col-md-2">
    <label class="form-label">الترتيب</label>
    <input type="number" name="sort_order" class="form-control"
           value="{{ old('sort_order', $type->sort_order ?? 0) }}">
  </div>
  <div class="col-md-2 d-flex align-items-end">
    <div class="form-check mb-2">
      <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
             {{ old('is_active', $type->is_active ?? true) ? 'checked' : '' }}>
      <label class="form-check-label" for="is_active">مفعّل</label>
    </div>
  </div>
  <div class="col-12">
    <button type="submit" class="btn btn-primary">حفظ</button>
    <a href="{{ route('admin.vehicle_types.index') }}" class="btn btn-link">رجوع</a>
  </div>
</div>
