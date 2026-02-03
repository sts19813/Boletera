<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="250px"
    data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">

    <div id="kt_app_sidebar_wrapper" class="app-sidebar-wrapper">
        <div class="hover-scroll-y my-5 my-lg-2 mx-4" data-kt-scroll="true"
            data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-height="auto"
            data-kt-scroll-dependencies="#kt_app_header" data-kt-scroll-wrappers="#kt_app_sidebar_wrapper"
            data-kt-scroll-offset="5px">

            <div id="kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false"
                class="app-sidebar-menu-primary menu menu-column menu-rounded menu-sub-indention menu-state-bullet-primary px-3 mb-5">

                @auth
                    {{-- ================================================= --}}
                    {{-- ============ ROL: INSCRIPTION =================== --}}
                    {{-- ================================================= --}}
                    @if(auth()->user()->isInscription())
                    <!-- Dashboard 

                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('dashboard*') ? 'active' : '' }}" href="/dashboard">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-chart-line fs-2"></i>
                                </span>
                                <span class="menu-title">{{ __('messages.dashboards') }}</span>
                            </a>
                        </div>-->

                        <!-- Inscripciones -->
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('registrations*') ? 'active' : '' }}" href="/registrations">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-user-square fs-2"></i>
                                </span>
                                <span class="menu-title">Inscripciones</span>
                            </a>
                        </div>

                    @else
                        {{-- ================================================= --}}
                        {{-- ============ ADMIN + CLIENT ===================== --}}
                        {{-- ================================================= --}}

                        <!-- Eventos -->
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('events*') ? 'active' : '' }}" href="/events">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-home-2 fs-2"></i>
                                </span>
                                <span class="menu-title">{{ __('messages.eventos') }}</span>
                            </a>
                        </div>

                        <!-- Tickets -->
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('ticket-instances*') ? 'active' : '' }}"
                                href="/ticket-instances">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-element-7 fs-2"></i>
                                </span>
                                <span class="menu-title">{{ __('messages.tickets') }}</span>
                            </a>
                        </div>

                        <!-- Inscripciones -->
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('registrations*') ? 'active' : '' }}" href="/registrations">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-user-square fs-2"></i>
                                </span>
                                <span class="menu-title">Inscripciones</span>
                            </a>
                        </div>

                        <!-- CRM Naboo -->
                        <div class="menu-item menu-accordion
                                            {{ request()->is('projects*', 'phases*', 'stages*', 'tickets*') ? 'show' : '' }}"
                            data-kt-menu-trigger="click">

                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-briefcase fs-2"></i>
                                </span>
                                <span class="menu-title">{{ __('messages.catalogo_naboo') }}</span>
                                <span class="menu-arrow"></span>
                            </span>

                            <div class="menu-sub menu-sub-accordion">
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('projects*') ? 'active' : '' }}" href="/projects">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">{{ __('messages.proyectos') }}</span>
                                    </a>
                                </div>

                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('phases*') ? 'active' : '' }}" href="/phases">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">{{ __('messages.fases') }}</span>
                                    </a>
                                </div>

                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('stages*') ? 'active' : '' }}" href="/stages">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">{{ __('messages.etapas') }}</span>
                                    </a>
                                </div>

                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('tickets*') ? 'active' : '' }}" href="/tickets">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">{{ __('messages.tickets') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- ================= ADMIN ONLY ================= --}}
                        @if(auth()->user()->isAdmin())

                            <!-- Dashboard -->
                            <div class="menu-item">
                                <a class="menu-link {{ request()->is('dashboard*') ? 'active' : '' }}" href="/dashboard">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-chart-line fs-2"></i>
                                    </span>
                                    <span class="menu-title">{{ __('messages.dashboards') }}</span>
                                </a>
                            </div>

                            <!-- Bitácora -->
                            <div class="menu-item">
                                <a class="menu-link {{ request()->is('corte*') ? 'active' : '' }}" href="/corte">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-notepad fs-2"></i>
                                    </span>
                                    <span class="menu-title">{{ __('messages.bitacora') }}</span>
                                </a>
                            </div>

                            <!-- Configuraciones -->
                            <div class="menu-item menu-accordion
                                                        {{ request()->is('users*', 'connections*', 'access*') ? 'show' : '' }}"
                                data-kt-menu-trigger="click">

                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-setting-3 fs-2"></i>
                                    </span>
                                    <span class="menu-title">{{ __('messages.configuraciones') }}</span>
                                    <span class="menu-arrow"></span>
                                </span>

                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->is('users*') ? 'active' : '' }}" href="/users">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">{{ __('messages.usuarios') }}</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                        @endif
                    @endif
                @endauth

            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">