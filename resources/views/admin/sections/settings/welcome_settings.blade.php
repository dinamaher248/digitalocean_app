@extends('admin.layout.admin_app')

@section('content')
  <div class="container" style="max-width: 760px;">
    <h2 class="my-4">إدارة شاشة الترحيب</h2>

    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <p class="text-muted">
      تظهر شاشة الترحيب للمستخدمين الجدد قبل التسجيل. عند إيقافها لن تظهر لأي مستخدم.
    </p>

    <form method="POST" action="{{ route('admin.welcome_settings.update') }}" class="row g-3">
      @csrf

      <div class="col-12">
        <div class="form-check form-switch">
          <input
            class="form-check-input"
            type="checkbox"
            role="switch"
            id="enabled"
            name="enabled"
            value="1"
            {{ old('enabled', $enabled) ? 'checked' : '' }}
          >
          <label class="form-check-label" for="enabled">تفعيل شاشة الترحيب</label>
        </div>
      </div>

      <div class="col-12">
        <label for="title" class="form-label">عنوان الرسالة</label>
        <input
          type="text"
          name="title"
          id="title"
          class="form-control @error('title') is-invalid @enderror"
          value="{{ old('title', $title) }}"
          required
        >
        @error('title')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="col-12">
        <label for="message" class="form-label">نص الرسالة</label>
        <textarea
          name="message"
          id="message"
          rows="4"
          class="form-control @error('message') is-invalid @enderror"
          required
        >{{ old('message', $message) }}</textarea>
        @error('message')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="col-12">
        <label for="features_text" class="form-label">المميزات (سطر لكل ميزة)</label>
        <textarea
          name="features_text"
          id="features_text"
          rows="7"
          class="form-control @error('features_text') is-invalid @enderror"
          required
        >{{ old('features_text', $features_text) }}</textarea>
        @error('features_text')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="col-12">
        <label for="button_text" class="form-label">نص زر ابدأ الآن</label>
        <input
          type="text"
          name="button_text"
          id="button_text"
          class="form-control @error('button_text') is-invalid @enderror"
          value="{{ old('button_text', $button_text) }}"
          required
        >
        @error('button_text')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="col-12">
        <button type="submit" class="btn btn-primary w-100">حفظ الإعدادات</button>
      </div>
    </form>
  </div>
@endsection
