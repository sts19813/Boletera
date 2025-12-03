<!DOCTYPE html>
<html lang="en">

<head>
				<meta charset="utf-8" />
				<title>@yield('title', 'Dashboard')</title>

				<!-- Meta -->
				<meta name="description"
								content="The most advanced Bootstrap 5 Admin Theme with 40 unique prebuilt layouts on Themeforest trusted by 100,000 beginners and professionals." />
				<meta name="keywords"
								content="metronic, bootstrap, bootstrap 5, admin themes, web design, web development, free templates" />
				<meta name="viewport" content="width=device-width, initial-scale=1" />
				<meta property="og:locale" content="en_US" />
				<meta property="og:type" content="article" />
				<meta property="og:title" content="Metronic - The World's #1 Selling Bootstrap Admin Template" />
				<meta property="og:url" content="https://keenthemes.com/metronic" />
				<meta property="og:site_name" content="Metronic by Keenthemes" />
				<link rel="canonical" href="https://preview.keenthemes.com/metronic8" />
				<link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.ico') }}" />

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

</head>

<body>


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
