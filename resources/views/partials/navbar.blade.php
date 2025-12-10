<div class="app-navbar flex-grow-1 justify-content-end" id="kt_app_header_navbar">
    <div class="app-navbar-item d-flex align-items-stretch flex-lg-grow-1">
        <!--begin::Search-->

    </div>

    @php
        $name = Auth::user()->name;
        $initials = collect(explode(' ', $name))->map(fn($w) => mb_substr($w, 0, 1))->join('');
    @endphp
    <!--begin::Notifications-->
    <div class="app-navbar-item ms-2 ms-lg-6">

        <!--begin::User menu-->
        <div class="app-navbar-item ms-2 ms-lg-6" id="kt_header_user_menu_toggle">
            <!--begin::Menu wrapper-->
            <div class="cursor-pointer symbol symbol-circle symbol-30px symbol-lg-45px"
                data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                data-kt-menu-attach="parent"
                data-kt-menu-placement="bottom-end">

                @if (Auth::user()->profile_photo)
                    <img src="{{ asset(Auth::user()->profile_photo) }} xdd" 
                        alt="user"
                        class="symbol-label"
                        style="object-fit: cover;">
                @else
                    <div class="symbol-label fw-bold d-flex justify-content-center align-items-center"
                        style="background:#0d6efd; color:white;">
                        {{ $initials }}
                    </div>
                @endif

            </div>
            <!--end::Menu wrapper-->

            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px"
                data-kt-menu="true">
                <!--begin::Menu item-->
                <div class="menu-item px-3">
                    <div class="menu-content d-flex align-items-center px-3">
                       <div class="symbol symbol-50px me-5">
                            @if (Auth::user()->profile_photo)
                                <img src="{{ asset(Auth::user()->profile_photo) }}"
                                    alt="Foto de perfil"
                                    class="symbol-label"
                                    style="object-fit: cover;">
                            @else
                                <div class="symbol-label fw-bold d-flex justify-content-center align-items-center"
                                    style="background:#0d6efd; color:white; font-size:18px;">
                                    {{ $initials }}
                                </div>
                            @endif

                        </div>
                        <!--end::Avatar-->
                        <!--begin::Username-->
                        <div class="d-flex flex-column">
                            <div class="fw-bold d-flex align-items-center fs-5">
                                {{ Auth::user()->name }}

                                @if(Auth::user()->is_admin)
                                    <span
                                        class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">{{ __('messages.admin_badge') }}</span>
                                @endif
                            </div>

                            <a href="mailto:{{ Auth::user()->email }}"
                                class="fw-semibold text-muted text-hover-primary fs-7">
                                {{ Auth::user()->email }}
                            </a>
                        </div>
                        <!--end::Username-->
                    </div>
                </div>
                <!--end::Menu item-->
                <!--begin::Menu separator-->
                <div class="separator my-2"></div>
                <!--end::Menu separator-->
                <!--begin::Menu item-->
                <div class="menu-item px-5">
                    <a href="{{ route('profile.index') }}" class="menu-link px-5">{{ __('messages.user_profile') }}</a>
                </div>


                <!--end::Menu item-->

                <!--begin::Menu separator-->
                <div class="separator my-2"></div>
                <!--end::Menu separator-->
                <!--begin::Menu item-->
                <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                    data-kt-menu-placement="left-start" data-kt-menu-offset="-15px, 0">
                    <a href="#" class="menu-link px-5">
                        <span class="menu-title position-relative">{{ __('messages.mode') }}
                            <span class="ms-5 position-absolute translate-middle-y top-50 end-0">
                                <i class="ki-outline ki-night-day theme-light-show fs-2"></i>
                                <i class="ki-outline ki-moon theme-dark-show fs-2"></i>
                            </span></span>
                    </a>
                    <!--begin::Menu-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px"
                        data-kt-menu="true" data-kt-element="theme-mode-menu">
                        <!--begin::Menu item-->
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-outline ki-night-day fs-2"></i>
                                </span>
                                <span class="menu-title">{{ __('messages.mode_light') }}</span>
                            </a>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-outline ki-moon fs-2"></i>
                                </span>
                                <span class="menu-title">{{ __('messages.mode_dark') }}</span>
                            </a>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-outline ki-screen fs-2"></i>
                                </span>
                                <span class="menu-title">{{ __('messages.mode_system') }}</span>
                            </a>
                        </div>
                        <!--end::Menu item-->
                    </div>
                    <!--end::Menu-->
                </div>
                <!--end::Menu item-->
                <!--begin::Menu item-->
                <!--begin::Menu item-->
                <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                    data-kt-menu-placement="left-start" data-kt-menu-offset="-15px, 0">

                    <a href="#" class="menu-link px-5">
                        <span class="menu-title position-relative">
                            {{ __('messages.language') }}
                            @php
                                $currentLocale = app()->getLocale();
                                $currentLangName = $currentLocale === 'en'
                                    ? __('messages.language_english')
                                    : __('messages.language_spanish');
                                $currentFlag = $currentLocale === 'en'
                                    ? 'assets/media/flags/united-states.svg'
                                    : 'assets/media/flags/mexico.svg';
                            @endphp

                            <span
                                class="fs-8 rounded bg-light px-3 py-2 position-absolute translate-middle-y top-50 end-0">
                                {{ $currentLangName }}
                                <img class="w-15px h-15px rounded-1 ms-2" src="{{ asset($currentFlag) }}" alt="" />
                            </span>
                        </span>
                    </a>

                    <!--begin::Menu sub-->
                    <div class="menu-sub menu-sub-dropdown w-175px py-4">

                        <!-- English -->
                        <div class="menu-item px-3">
                            <a href="{{ route('lang.switch', 'en') }}"
                                class="menu-link d-flex px-5 {{ $currentLocale == 'en' ? 'active' : '' }}">
                                <span class="symbol symbol-20px me-4">
                                    <img class="rounded-1" src="{{ asset('assets/media/flags/united-states.svg') }}"
                                        alt="" />
                                </span>
                                English
                            </a>
                        </div>

                        <!-- Spanish -->
                        <div class="menu-item px-3">
                            <a href="{{ route('lang.switch', 'es') }}"
                                class="menu-link d-flex px-5 {{ $currentLocale == 'es' ? 'active' : '' }}">
                                <span class="symbol symbol-20px me-4">
                                    <img class="rounded-1" src="{{ asset('assets/media/flags/mexico.svg') }}" alt="" />
                                </span>
                                Español
                            </a>
                        </div>

                    </div>
                </div>

                <!--end::Menu item-->
                <div class="menu-item px-5">
                    <a onclick="event.preventDefault(); document.getElementById('logout-form').submit();" href="#"
                        class="menu-link px-5">{{ __('messages.logout') }}</a>
                </div>
            </div>
            <!--end::User account menu-->
            <!--end::Menu wrapper-->
        </div>

        <!--end::Menu wrapper-->
    </div>
    <!--end::Notifications-->
    <!--begin::Quick links-->

    <!--end::User menu-->
    <!--begin::Action-->
    <div class="app-navbar-item ms-2 ms-lg-6 me-lg-6">
        <!--begin::Link-->
        <!-- Botón logout -->
        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            class="btn btn-icon btn-custom btn-color-gray-600 btn-active-color-primary w-35px h-35px w-md-40px h-md-40px">
            <i class="ki-outline ki-exit-right fs-1"></i>
        </a>

        <!-- Formulario oculto -->
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
        <!--end::Link-->
    </div>
    <!--end::Action-->
    <!--begin::Header menu toggle-->
    <div class="app-navbar-item ms-2 ms-lg-6 ms-n2 me-3 d-flex d-lg-none">
        <div class="btn btn-icon btn-custom btn-color-gray-600 btn-active-color-primary w-35px h-35px w-md-40px h-md-40px"
            id="kt_app_aside_mobile_toggle">
            <i class="ki-outline ki-burger-menu-2 fs-2"></i>
        </div>
    </div>
    <!--end::Header menu toggle-->
</div>