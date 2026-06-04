<!DOCTYPE html>
<html lang="ar" dir="rtl" data-coreui-theme="auto">
  <head>
    <base href="./">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>لوحة الإدارة — تسجيل الدخول</title>

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('/public/admin/assets/favicon/apple-icon-57x57.png') }}">
    <link rel="apple-touch-icon" sizes="60x60" href="{{ asset('/public/admin/assets/favicon/apple-icon-60x60.png') }}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('/public/admin/assets/favicon/apple-icon-72x72.png') }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('/public/admin/assets/favicon/apple-icon-76x76.png') }}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{ asset('/public/admin/assets/favicon/apple-icon-114x114.png') }}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{ asset('/public/admin/assets/favicon/apple-icon-120x120.png') }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('/public/admin/assets/favicon/apple-icon-144x144.png') }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('/public/admin/assets/favicon/apple-icon-152x152.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('/public/admin/assets/favicon/apple-icon-180x180.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('/public/admin/assets/favicon/android-icon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="32x32"  href="{{ asset('/public/admin/assets/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="96x96"  href="{{ asset('/public/admin/assets/favicon/favicon-96x96.png') }}">
    <link rel="icon" type="image/png" sizes="16x16"  href="{{ asset('/public/admin/assets/favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('/public/admin/assets/favicon/manifest.json') }}">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{ asset('/public/admin/assets/favicon/ms-icon-144x144.png') }}">
    <meta name="theme-color" content="#ffffff">

    <!-- Vendors styles -->
    <link rel="stylesheet" href="{{ asset('/public/admin/vendors/simplebar/css/simplebar.css') }}">
    <link rel="stylesheet" href="{{ asset('/public/admin/css/vendors/simplebar.css') }}">

    <!-- CoreUI main styles -->
    <link href="{{ asset('/public/admin/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('/public/admin/css/examples.css') }}" rel="stylesheet">

    <!-- CoreUI color modes (للدعم التلقائي للداكن/الفاتح) -->
    <script src="{{ asset('/public/admin/js/config.js') }}"></script>
    <script src="{{ asset('/public/admin/js/color-modes.js') }}"></script>

    <style>
      /* تحسين خلفية الصفحة مع احترام الثيم */
      body {
        background:
          radial-gradient(1200px 600px at 90% -10%, rgba(0,0,0,.04), transparent),
          radial-gradient(1000px 500px at -10% 110%, rgba(0,0,0,.04), transparent);
      }
      [data-coreui-theme="dark"] body,
      [data-coreui-theme="dark"] .bg-body-tertiary {
        background:
          radial-gradient(1200px 600px at 90% -10%, rgba(255,255,255,.05), transparent),
          radial-gradient(1000px 500px at -10% 110%, rgba(255,255,255,.05), transparent);
      }

      /* كارت الدخول */
      .login-card {
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,.08);
      }
      [data-coreui-theme="dark"] .login-card {
        box-shadow: 0 10px 30px rgba(0,0,0,.4);
      }

      /* زر مبدّل الثيم */
      .theme-switch {
        position: fixed;
        top: 12px;
        left: 12px; /* في RTL على اليسار */
        z-index: 1050;
      }

      /* تحسين أيقونات الإدخال في RTL */
      .input-group > .input-group-text {
        min-width: 42px;
        justify-content: center;
      }
    </style>
  </head>

  <body>
    <!-- مبدّل الثيم: فاتح / داكن / تلقائي -->
    <div class="dropdown theme-switch">
      <button class="btn btn-outline-secondary btn-sm" id="bd-theme" type="button" data-coreui-toggle="dropdown" aria-expanded="false" aria-label="تغيير المظهر">
        <svg class="icon"><use xlink:href="{{ url('/public/admin/vendors/@coreui/icons/svg/free.svg') }}#cil-contrast"></use></svg>
        <span class="d-none d-sm-inline ms-1">المظهر</span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme">
        <li>
          <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-coreui-theme-value="light">
            <svg class="icon"><use xlink:href="{{ url('/public/admin/vendors/@coreui/icons/svg/free.svg') }}#cil-sun"></use></svg>
            فاتح
          </button>
        </li>
        <li>
          <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-coreui-theme-value="dark">
            <svg class="icon"><use xlink:href="{{ url('/public/admin/vendors/@coreui/icons/svg/free.svg') }}#cil-moon"></use></svg>
            داكن
          </button>
        </li>
        <li>
          <button type="button" class="dropdown-item d-flex align-items-center gap-2 active" data-coreui-theme-value="auto">
            <svg class="icon"><use xlink:href="{{ url('/public/admin/vendors/@coreui/icons/svg/free.svg') }}#cil-contrast"></use></svg>
            تلقائي
          </button>
        </li>
      </ul>
    </div>

    <div class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-5 col-md-7">
            <div class="card login-card border-0">
              <div class="card-body p-4 p-sm-5">
                <div class="text-center mb-3">
                  <img src="{{ asset('/public/admin/assets/brand/motorent_logo.png') }}" alt="Logo" height="42">
                </div>

                <h1 class="h4 fw-bold text-center mb-1">تسجيل الدخول</h1>
                <p class="text-body-secondary text-center mb-4">مرحبًا بك في لوحة الإدارة</p>

                {{-- رسائل النظام --}}
                @if(session('error'))
                  <div class="alert alert-danger small">{{ session('error') }}</div>
                @endif
                @if(session('status'))
                  <div class="alert alert-info small">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                  <div class="alert alert-danger small">
                    <ul class="mb-0 ps-3">
                      @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                      @endforeach
                    </ul>
                  </div>
                @endif

                <form method="POST" action="{{ route('admin.login.post') }}" novalidate>
                  @csrf

                  <div class="input-group mb-3">
                    <span class="input-group-text">
                      <svg class="icon"><use xlink:href="{{ url('/public/admin/vendors/@coreui/icons/svg/free.svg') }}#cil-user"></use></svg>
                    </span>
                    <input
                      class="form-control @error('email') is-invalid @enderror"
                      name="email"
                      type="email"
                      inputmode="email"
                      autocomplete="username"
                      placeholder="البريد الإلكتروني"
                      value="{{ old('email') }}"
                      required
                      autofocus
                    >
                    @error('email')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="input-group mb-3">
                    <span class="input-group-text">
                      <svg class="icon"><use xlink:href="{{ url('/public/admin/vendors/@coreui/icons/svg/free.svg') }}#cil-lock-locked"></use></svg>
                    </span>
                    <input
                      class="form-control @error('password') is-invalid @enderror"
                      id="password"
                      name="password"
                      type="password"
                      autocomplete="current-password"
                      placeholder="كلمة المرور"
                      required
                    >
                    <button type="button" class="btn btn-outline-secondary" id="togglePassword" tabindex="-1" aria-label="إظهار/إخفاء كلمة المرور">
                      <svg class="icon" id="eyeOpen"><use xlink:href="{{ url('/public/admin/vendors/@coreui/icons/svg/free.svg') }}#cil-eye"></use></svg>
                      <svg class="icon d-none" id="eyeClosed"><use xlink:href="{{ url('/public/admin/vendors/@coreui/icons/svg/free.svg') }}#cil-low-vision"></use></svg>
                    </button>
                    @error('password')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember" @checked(old('remember'))>
                      <label class="form-check-label" for="remember">تذكرني</label>
                    </div>
                    <a class="link-underline link-underline-opacity-0 small" href="#" role="button">نسيت كلمة المرور؟</a>
                  </div>

                  <div class="d-grid">
                    <button class="btn btn-primary py-2" type="submit">
                      دخول
                    </button>
                  </div>
                </form>

              </div> <!-- /card-body -->
            </div> <!-- /card -->
          </div>
        </div>
      </div>
    </div>

    <!-- CoreUI and necessary plugins -->
    <script src="{{ asset('/public/admin/vendors/@coreui/coreui/js/coreui.bundle.min.js') }}"></script>
    <script src="{{ asset('/public/admin/vendors/simplebar/js/simplebar.min.js') }}"></script>

    <script>
      // زر إظهار/إخفاء كلمة المرور
      (function () {
        const input = document.getElementById('password');
        const btn   = document.getElementById('togglePassword');
        const eyeO  = document.getElementById('eyeOpen');
        const eyeC  = document.getElementById('eyeClosed');
        btn?.addEventListener('click', () => {
          const isPwd = input.type === 'password';
          input.type = isPwd ? 'text' : 'password';
          eyeO.classList.toggle('d-none', !isPwd);
          eyeC.classList.toggle('d-none', isPwd);
          input.focus();
        });
      })();
    </script>
  </body>
</html>
