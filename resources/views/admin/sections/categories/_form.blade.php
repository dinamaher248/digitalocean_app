@csrf
<div class="mb-3"><label class="form-label">اسم القسم</label><input name="name" class="form-control" value="{{ old('name', $category->name) }}" required></div>
<div class="mb-3"><label class="form-label">Slug</label><input name="slug" class="form-control" value="{{ old('slug', $category->slug) }}"></div>
<div class="mb-3">
  <label class="form-label">نوع الإضافة <span class="text-danger">*</span></label>
  @php $selectedHub = old('hub_section', $category->hub_section ?? 'orders'); @endphp
  <select name="hub_section" class="form-select" required>
    <option value="orders" {{ $selectedHub === 'orders' ? 'selected' : '' }}>الطلبات</option>
    <option value="bookings" {{ $selectedHub === 'bookings' ? 'selected' : '' }}>الحجوزات</option>
    <option value="services" {{ $selectedHub === 'services' ? 'selected' : '' }}>خدمات أخرى</option>
  </select>
  <div class="form-text">حدد ظهور القسم في تطبيق العميل: تبويب الطلبات أو الحجوزات أو خدمات أخرى.</div>
</div>
<div class="mb-3">
  <label class="form-label">عدد الصور المسموحة لكل نشاط</label>
  <input type="number" min="1" max="100" name="max_images" class="form-control"
         value="{{ old('max_images', $category->max_images ?: 10) }}">
  <div class="form-text">
    الحد الأقصى لعدد الصور/الأصناف التي يرفعها صاحب النشاط داخل هذا القسم.
    مثال: مطاعم 10 صور، فنادق 20 صورة.
  </div>
</div>
<div class="mb-3">
  <label class="form-label">صورة خلفية القسم</label>
  @php
    $currentImage = $category->icon ?? null;
    $looksLikeImage = is_string($currentImage)
      && $currentImage !== ''
      && (str_contains($currentImage, '/') || preg_match('/\.(jpe?g|png|webp|gif)$/i', $currentImage));
  @endphp
  @if($looksLikeImage)
    <div class="mb-2">
      <img src="{{ asset('storage/' . ltrim($currentImage, '/')) }}"
           alt="خلفية القسم"
           style="max-width: 220px; max-height: 140px; object-fit: cover; border-radius: 10px;">
    </div>
  @endif
  <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp,image/jpg">
  <div class="form-text">تظهر كخلفية لكارت القسم داخل تطبيق العميل. يُفضّل صورة أفقية واضحة.</div>
</div>
<div class="mb-3"><label class="form-label">الترتيب</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $category->sort_order ?? 0) }}"></div>
<div class="form-check mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}><label class="form-check-label" for="is_active">مفعّل</label></div>
<button class="btn btn-primary">حفظ</button>
