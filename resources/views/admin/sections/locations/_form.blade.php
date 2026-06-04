@php
    // نحسب نوع المكان من الـ old أو من المتغيرات القادمة من الكنترولر
    $effectiveType = old('type', $type ?? $location->type ?? 'governorate');
@endphp

@csrf

<input type="hidden" name="type" value="{{ $effectiveType }}">

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">نوع المكان</label>
        <input type="text"
               class="form-control"
               value="{{ $effectiveType === 'area' ? 'مدينة / حي' : 'محافظة' }}"
               disabled>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">
            الاسم بالعربية <span class="text-danger">*</span>
        </label>
        <input type="text"
               name="name_ar"
               class="form-control @error('name_ar') is-invalid @enderror"
               value="{{ old('name_ar', $location->name_ar ?? '') }}"
               placeholder="مثال: القاهرة، الجيزة، الزقازيق">
        @error('name_ar')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">الاسم بالإنجليزية</label>
        <input type="text"
               name="name_en"
               class="form-control @error('name_en') is-invalid @enderror"
               value="{{ old('name_en', $location->name_en ?? '') }}"
               placeholder="Example: Cairo, Giza, Zagazig">
        @error('name_en')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

{{-- لو النوع مدينة/حي نظهر حقل اختيار المحافظة --}}
@if($effectiveType === 'area')
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">
                المحافظة التابعة لها <span class="text-danger">*</span>
            </label>
            <select name="parent_id"
                    class="form-select @error('parent_id') is-invalid @enderror">
                <option value="">اختر المحافظة</option>
                @foreach($parents ?? [] as $parent)
                    <option value="{{ $parent->id }}"
                        {{ (int) old('parent_id', $location->parent_id ?? 0) === $parent->id ? 'selected' : '' }}>
                        {{ $parent->name_ar }}
                    </option>
                @endforeach
            </select>
            @error('parent_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
@endif

<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">ترتيب العرض</label>
        <input type="number"
               name="sort_order"
               min="0"
               class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $location->sort_order ?? 0) }}">
        @error('sort_order')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">رقم أصغر يعني يظهر أعلى في القائمة.</small>
    </div>

    <div class="col-md-3 mb-3 d-flex align-items-center">
        <div class="form-check mt-4">
            <input type="checkbox"
                   name="is_primary"
                   id="is_primary"
                   class="form-check-input"
                   {{ old('is_primary', $location->is_primary ?? 0) ? 'checked' : '' }}>
            <label for="is_primary" class="form-check-label">مكان أساسي؟</label>
        </div>
    </div>

    <div class="col-md-3 mb-3 d-flex align-items-center">
        <div class="form-check mt-4">
            <input type="checkbox"
                   name="is_active"
                   id="is_active"
                   class="form-check-input"
                   {{ old('is_active', $location->is_active ?? 1) ? 'checked' : '' }}>
            <label for="is_active" class="form-check-label">مفعل</label>
        </div>
    </div>
</div>

<hr>

<div class="d-flex justify-content-between">
    <a href="{{ route('admin.locations.index', ['type' => $effectiveType]) }}"
       class="btn btn-secondary">
        رجوع للقائمة
    </a>

    <button type="submit" class="btn btn-primary">
        {{ $buttonText ?? 'حفظ' }}
    </button>
</div>
