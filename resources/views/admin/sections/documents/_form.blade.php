@csrf
<div class="mb-3"><label>اسم المستند</label><input name="label" class="form-control" value="{{ old('label', $document->label) }}" required></div>
<div class="mb-3"><label>المفتاح (key)</label><input name="key" class="form-control" value="{{ old('key', $document->key) }}"></div>
<div class="mb-3"><label>نوع الحقل</label><select name="field_type" class="form-select"><option value="image" @selected(old('field_type', $document->field_type)==='image')>صورة</option><option value="text" @selected(old('field_type', $document->field_type)==='text')>نص</option></select></div>

@include('admin.sections.documents._audience_select', [
    'document' => $document,
    'vehicleTypes' => $vehicleTypes ?? collect(),
    'selectedKeys' => $selectedKeys ?? [],
    'selectedHubs' => $selectedHubs ?? [],
])

<div class="mb-3"><label>الترتيب</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $document->sort_order ?? 0) }}"></div>
<div class="form-check mb-2"><input type="checkbox" name="is_required" value="1" class="form-check-input" id="is_required" {{ old('is_required', $document->is_required ?? true) ? 'checked' : '' }}><label for="is_required">إجباري</label></div>
<div class="form-check mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $document->is_active ?? true) ? 'checked' : '' }}><label for="is_active">مفعّل</label></div>
<button class="btn btn-primary">حفظ</button>
