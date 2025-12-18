<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Stom tickets')</title>

    <!-- Meta -->
    <meta name="description"
        content="Compra tus boletos en línea con STOM TICKETS. Encuentra entradas para conciertos, festivales, deportes y eventos exclusivos en Yucatán y toda México. Compra fácil, segura y con soporte personalizado." />
    <meta name="keywords"
        content="box, eventos, stom" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="StomTickets - Compra tus boletos en línea con STOM TICKETS. Encuentra entradas para conciertos, festivales, deportes y eventos exclusivos en Yucatán y toda México. Compra fácil, segura y con soporte personalizado." />
    <meta property="og:site_name" content="Stom Tickets" />
    <link rel="canonical" href="https://preview.keenthemes.com/metronic8" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />

    <!-- Vendor Stylesheets (para páginas específicas, opcional) -->
    <link href="{{ asset('assets/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet"
        type="text/css" />

    <!-- Global Stylesheets Bundle (obligatorios) -->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />

    <script>
        // Forzar tema oscuro por defecto
        document.documentElement.setAttribute("data-bs-theme", "dark");
        localStorage.setItem("kt_theme_mode_value", "dark");
    </script>



    <style>
        .event-header {
            background: radial-gradient(circle at top left, #2b2b2b, #000);
            padding: 35px 0 100px;
            color: #fff;
        }


        .event-title {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 12px;
            color: white;
        }

        .event-price {
            font-size: 22px;
            font-weight: 600;
            color: #ff9f43;
            margin-bottom: 20px;
        }

        .event-price span {
            color: #b5b5b5;
            font-size: 14px;
            font-weight: 400;
        }

        .event-meta {
            color: #d1d1d1;
            font-size: 15px;
        }

        .event-meta div {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .event-date {
            width: 90px;
            height: 110px;
            border-radius: 14px;
            background: linear-gradient(135deg, #8f5cff, #ff9f43);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 15px 40px rgba(0, 0, 0, .4);
        }

        .event-date .day {
            font-size: 36px;
            line-height: 1;
        }

        .event-date .month {
            font-size: 14px;
            letter-spacing: 1px;
        }

        .btn-primary {
            background: #7723FF !important;
        }

        .bodyform {}
    </style>


</head>

<body>

    {{-- HEADER EVENTO --}}
    <section class="event-header position-relative">
        <div class="container">
            <div class="d-flex justify-content-between align-items-start flex-wrap">

                {{-- INFO EVENTO --}}
                <div class="event-info">
                    <img src="/assets/logo.svg" alt="Stom Tickets" class="event-logo mb-6" style="width:180px;">

                    <h1 class="event-title">Box Azteca</h1>



                    <div class="event-meta">
                        <div>
                            <i class="ki-duotone ki-time fs-5 me-2"></i>
                            7:30 PM - 12:00 AM
                        </div>
                        <div>
                            <i class="ki-duotone ki-geolocation fs-5 me-2"></i>
                            Centro de Convenciones Siglo XXI, Mérida
                        </div>
                    </div>
                </div>

                {{-- FECHA --}}
                <div class="event-date">
                    <span class="day">17</span>
                    <span class="month">ENE</span>
                </div>

            </div>
        </div>


    </section>



    @yield('content')


    <script>
        var hostUrl = "{{ asset('assets') }}/";
    </script>

    <!--begin::Global Javascript Bundle (obligatorio para todas las páginas)-->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <!--end::Global Javascript Bundle-->

    <!--begin::Custom Javascript (usados solo en algunas páginas)-->
    <script src="{{ asset('assets/js/widgets.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/custom/widgets.js') }}"></script>
    <script src="{{ asset('assets/js/custom/apps/chat/chat.js') }}"></script>
    <script src="{{ asset('assets/js/custom/utilities/modals/upgrade-plan.js') }}"></script>
    <script src="{{ asset('assets/js/custom/utilities/modals/users-search.js') }}"></script>
    <!--end::Custom Javascript-->

    <!-- Para cargar scripts adicionales desde otras vistas -->
    @stack('scripts')
    <!--end::Javascript-->
</body>

</html>