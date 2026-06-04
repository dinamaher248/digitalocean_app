@csrf

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">اسم الخطة <span class="text-danger">*</span></label>
        <input type="text"
               name="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $plan->name ?? '') }}"
               placeholder="مثال: خطة شهر واحد">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">السعر الأساسي (جنيه) <span class="text-danger">*</span></label>
        <input type="number"
               name="price"
               min="0"
               class="form-control @error('price') is-invalid @enderror"
               value="{{ old('price', $plan->price ?? 0) }}">
        @error('price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">قيمة الخصم (جنيه) <span class="text-danger">*</span></label>
        <input type="number"
               name="offer"
               min="0"
               class="form-control @error('offer') is-invalid @enderror"
               value="{{ old('offer', $plan->offer ?? 0) }}">
        @error('offer')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">مدة الخطة (أيام) <span class="text-danger">*</span></label>
        <input type="number"
               name="days"
               min="1"
               class="form-control @error('days') is-invalid @enderror"
               value="{{ old('days', $plan->days ?? 30) }}">
        @error('days')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<hr>

<div class="d-flex justify-content-between">
    <a href="{{ route('admin.plans.index') }}" class="btn btn-secondary">
        رجوع للقائمة
    </a>
    <button type="submit" class="btn btn-primary">
        {{ $buttonText ?? 'حفظ' }}
    </button>
</div>
