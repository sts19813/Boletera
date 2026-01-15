<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-HPF7EK17R3"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'G-HPF7EK17R3');
    </script>
    <!-- Google Tag Manager -->
    <script>(function (w, d, s, l, i) {
            w[l] = w[l] || []; w[l].push({
                'gtm.start':
                    new Date().getTime(), event: 'gtm.js'
            }); var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : ''; j.async = true; j.src =
                    'https://www.googletagmanager.com/gtm.js?id=' + i + dl; f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-W848MPWG');</script>
    <!-- End Google Tag Manager -->
    <meta name="description"
        content="Compra tus boletos en línea con STOM TICKETS. Encuentra entradas para conciertos, festivales, deportes y eventos exclusivos en Yucatán y toda México. Compra fácil, segura y con soporte personalizado." />
    <meta name="keywords" content="boletos, eventos, conciertos, festivales, stom tickets" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Canonical -->
    <link rel="canonical" href="{{ url()->current() }}" />

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">

    <!-- Open Graph / Social Share -->
    <meta property="og:locale" content="es_MX" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Stom Tickets" />
    <meta property="og:title" content="Stom Tickets - Compra tus boletos en línea" />
    <meta property="og:description"
        content="Compra tus boletos en línea con STOM TICKETS. Conciertos, festivales, deportes y eventos exclusivos en México." />
    <meta property="og:url" content="{{ url()->current() }}" />

    <meta property="og:image" content="{{ asset('og-image.png') }}" />
    <meta property="og:image:secure_url" content="{{ asset('og-image.png') }}" />
    <meta property="og:image:type" content="image/png" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />

    <!-- Vendor Stylesheets -->
    <link href="{{ asset('assets/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet"
        type="text/css" />

    <!-- Global Stylesheets -->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/layout.css') }}" rel="stylesheet" type="text/css" />

    <script>
        // Forzar tema oscuro por defecto
        document.documentElement.setAttribute("data-bs-theme", "dark");
        localStorage.setItem("kt_theme_mode_value", "dark");
    </script>

</head>

<body>

    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-W848MPWG" height="0" width="0"
            style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
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
                            6:00 PM - 12:00 AM
                        </div>
                        <div>
                            <i class="ki-duotone ki-geolocation fs-5 me-2"></i>
                            Centro de Convenciones Siglo XXI, Mérida
                        </div>
                        <div style="max-width: 950px">
                           <i class="ki-duotone ki-geolocation fs-5 me-2"></i>
                            Las mesas VIP están diseñadas para un máximo de 6 personas. Al adquirir una mesa VIP, se entrega un único boleto con código QR, el cual es válido para el acceso de hasta 6 personas y puede ser escaneado hasta seis veces, una por cada asistente.
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