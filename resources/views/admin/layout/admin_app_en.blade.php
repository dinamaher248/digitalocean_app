<!DOCTYPE html>
<html lang="en">

<head>
    <base href="./">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="description" content="CoreUI - Open Source Bootstrap Admin Template">
    <meta name="author" content="Łukasz Holeczek">
    <meta name="keyword" content="Bootstrap,Admin,Template,Open,Source,jQuery,CSS,HTML,RWD,Dashboard">
    <title>CoreUI Free Bootstrap Admin Template</title>
    <link rel="apple-touch-icon" sizes="57x57" href={{ asset('/public/admin/assets/favicon/apple-icon-57x57.png') }}>
    <link rel="apple-touch-icon" sizes="60x60" href={{ asset('/public/admin/assets/favicon/apple-icon-60x60.png') }}>
    <link rel="apple-touch-icon" sizes="72x72" href={{ asset('/public/admin/assets/favicon/apple-icon-72x72.png') }}>
    <link rel="apple-touch-icon" sizes="76x76" href={{ asset('/public/admin/assets/favicon/apple-icon-76x76.png') }}>
    <link rel="apple-touch-icon" sizes="114x114" href={{ asset('/public/admin/assets/favicon/apple-icon-114x114.png')
        }}>
    <link rel="apple-touch-icon" sizes="120x120" href={{ asset('/public/admin/assets/favicon/apple-icon-120x120.png')
        }}>
    <link rel="apple-touch-icon" sizes="144x144" href={{ asset('/public/admin/assets/favicon/apple-icon-144x144.png')
        }}>
    <link rel="apple-touch-icon" sizes="152x152" href={{ asset('/public/admin/assets/favicon/apple-icon-152x152.png')
        }}>
    <link rel="apple-touch-icon" sizes="180x180" href={{ asset('/public/admin/assets/favicon/apple-icon-180x180.png')
        }}>
    <link rel="icon" type="image/png" sizes="192x192" href={{
        asset('/public/admin/assets/favicon/android-icon-192x192.png') }}>
    <link rel="icon" type="image/png" sizes="32x32" href={{ asset('/public/admin/assets/favicon/favicon-32x32.png') }}>
    <link rel="icon" type="image/png" sizes="96x96" href={{ asset('/public/admin/assets/favicon/favicon-96x96.png') }}>
    <link rel="icon" type="image/png" sizes="16x16" href={{ asset('/public/admin/assets/favicon/favicon-16x16.png') }}>
    <link rel="manifest" href={{ asset('/public/admin/assets/favicon/manifest.json') }}>
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content={{ asset('/public/admin/assets/favicon/ms-icon-144x144.png') }}>
    <meta name="theme-color" content="#ffffff">
    <link rel="stylesheet" href={{ asset('/public/admin/vendors/simplebar/css/simplebar.css') }}>
    <link rel="stylesheet" href={{ asset('/public/admin/css/vendors/simplebar.css') }}>
    <link href={{ asset('/public/admin/css/style.css') }} rel="stylesheet">
    <script src={{ asset('/public/admin/js/config.js') }}></script>
    <script src={{ asset('/public/admin/js/color-modes.js') }}></script>
    <link href={{ url('/public/admin/vendors/@coreui/chartjs/css/coreui-chartjs.css') }} rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    @yield('style')
</head>

<body>
    <div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
        <div class="sidebar-header border-bottom align-items-center justify-content-center">
            <div class="sidebar-brand">
                <img src={{ asset('/public/admin/assets/brand/motorent_logo.png') }} class="sidebar-brand-full"
                    width="118" height="46" alt="CoreUI Logo">
            </div>
            <button class="btn-close d-lg-none" type="button" data-coreui-theme="dark" aria-label="Close"
                onclick="coreui.Sidebar.getInstance(document.querySelector(&quot;#sidebar&quot;)).toggle()"></button>
        </div>
        <ul class="sidebar-nav" data-coreui="navigation" data-simplebar="">
            <li class="nav-item"><a class="nav-link" href="{{route('admin.dashboard')}}">
                    <svg class="nav-icon">
                        <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-speedometer"></use>
                    </svg> لوحة التحكم</a>
            </li>

            <li class="nav-title">Me</li>
            <li class="nav-group"><a class="nav-link nav-group-toggle" href="#">
                    <svg class="nav-icon">
                        <use xlink:href="{{asset('/public/admin/vendors/@coreui/icons/svg/free.svg')}}#cil-puzzle">
                        </use>
                    </svg> Users</a>
                <ul class="nav-group-items compact">
                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.register_request.index') }}"><span
                                class="nav-icon"><span class="nav-icon-bullet"></span></span> Registeration Requests</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="{{route('admin.user.index')}}"><span
                                class="nav-icon"><span class="nav-icon-bullet"></span></span> All Users</a></li>
                </ul>
            </li>
            {{-- <li class="nav-title">Me</li> --}}
            <li class="nav-group"><a class="nav-link nav-group-toggle" href="#">
                    <svg class="nav-icon">
                        <use xlink:href="{{ asset('/public/admin/vendors/@coreui/icons/svg/free.svg') }}#cil-puzzle">
                        </use>
                    </svg> Plans</a>
                <ul class="nav-group-items compact">

                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.plan.index') }}"><span
                                class="nav-icon"><span class="nav-icon-bullet"></span></span> Plans</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.plan.add') }}"><span
                                class="nav-icon"><span class="nav-icon-bullet"></span></span> add Plan</a></li>
                </ul>
            </li>

            <li class="nav-item"><a class="nav-link" href="{{route('admin.send_notification_by_topic_get')}}"><span
                        class="nav-icon"><span class="nav-icon-bullet"></span></span> push notify to all</a></li>
            <li class="nav-item"><a class="nav-link" href="{{route('admin.privacy_policy')}}"><span
                        class="nav-icon"><span class="nav-icon-bullet"></span></span> Privacy Policy</a></li>

        </ul>
        <div class="sidebar-footer border-top d-none d-md-flex">
            <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
        </div>
    </div>
    <div class="wrapper d-flex flex-column min-vh-100">
        <header class="header header-sticky p-0 mb-4">
            <div class="container-fluid border-bottom px-4">
                <button class="header-toggler" type="button"
                    onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"
                    style="margin-inline-start: -14px;">
                    <svg class="icon icon-lg">
                        <use xlink:href="{{asset('/public/admin/vendors/@coreui/icons/svg/free.svg')}}#cil-menu"></use>
                    </svg>
                </button>
                <ul class="header-nav">
                    <li class="nav-item py-1">
                        <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
                    </li>
                    <li class="nav-item dropdown">
                        <button class="btn btn-link nav-link py-2 px-2 d-flex align-items-center" type="button"
                            aria-expanded="false" data-coreui-toggle="dropdown">
                            <svg class="icon icon-lg theme-icon-active">
                                <use
                                    xlink:href="{{asset('/public/admin/vendors/@coreui/icons/svg/free.svg')}}#cil-contrast">
                                </use>
                            </svg>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="--cui-dropdown-min-width: 8rem;">
                            <li>
                                <button class="dropdown-item d-flex align-items-center" type="button"
                                    data-coreui-theme-value="light">
                                    <svg class="icon icon-lg me-3">
                                        <use
                                            xlink:href="{{asset('/public/admin/vendors/@coreui/icons/svg/free.svg')}}#cil-sun">
                                        </use>
                                    </svg>Light
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item d-flex align-items-center" type="button"
                                    data-coreui-theme-value="dark">
                                    <svg class="icon icon-lg me-3">
                                        <use
                                            xlink:href="{{asset('/public/admin/vendors/@coreui/icons/svg/free.svg')}}#cil-moon">
                                        </use>
                                    </svg>Dark
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item d-flex align-items-center active" type="button"
                                    data-coreui-theme-value="auto">
                                    <svg class="icon icon-lg me-3">
                                        <use
                                            xlink:href="{{asset('/public/admin/vendors/@coreui/icons/svg/free.svg')}}#cil-contrast">
                                        </use>
                                    </svg>Auto
                                </button>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item py-1">
                        <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
                    </li>
                    <li class="nav-item dropdown"><a class="nav-link py-0 pe-0" data-coreui-toggle="dropdown" href="#"
                            role="button" aria-haspopup="true" aria-expanded="false">
                            <div class="avatar avatar-md"><img class="avatar-img"
                                    src="{{asset('/public/admin/assets/img/avatars/Female-Avatar.png')}}"
                                    alt="user@email.com"></div>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="container-fluid px-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb my-0">
                        <li class="breadcrumb-item"><a href="#">Home</a>
                        </li>
                        <li class="breadcrumb-item active"><span>Dashboard</span>
                        </li>
                    </ol>
                </nav>
            </div>
        </header>
        <div class="body flex-grow-1">
            <div class="container-lg px-4">
                @yield('content')
            </div>
        </div>
        <footer class="footer px-4">
        </footer>
    </div>
    <script>

    </script>

    <script src={{ asset('/public/admin/vendors/@coreui/coreui/js/coreui.bundle.min.js') }}></script>
    <script src={{ asset('/public/admin/vendors/simplebar/js/simplebar.min.js') }}></script>
    <script>
        const header = document.querySelector('header.header');

        document.addEventListener('scroll', () => {
            if (header) {
                header.classList.toggle('shadow-sm', document.documentElement.scrollTop > 0);
            }
        });
    </script>
    <script src={{ asset('/public/admin/vendors/chart.js/js/chart.umd.js') }}></script>
    <script src={{ asset('/public/admin/vendors/@coreui/chartjs/js/coreui-chartjs.js') }}></script>
    <script src={{ asset('/public/admin/vendors/@coreui/utils/js/index.js') }}></script>
    <script src={{ asset('/public/admin/js/main.js') }}></script>
    <script></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    @yield('scripts')
</body>

</html>