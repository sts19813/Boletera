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
                {{-- ================= EVENTOS ================= --}}
                @canany(['crear eventos', 'editar eventos', 'eliminar eventos', 'configurar eventos'])
                    <div class="menu-item">
                        <a class="menu-link {{ request()->is('events*') ? 'active' : '' }}" href="/events">
                            <span class="menu-icon">
                                <i class="ki-outline ki-home-2 fs-2"></i>
                            </span>
                            <span class="menu-title">Eventos</span>
                        </a>
                    </div>

                    <div class="menu-item menu-accordion {{ request()->is('tickets*') ? 'show' : '' }}"
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
                                <a class="menu-link {{ request()->is('tickets*') ? 'active' : '' }}" href="/tickets">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">{{ __('messages.tickets') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="menu-item">
                        <a class="menu-link {{ request()->is('ticket-instances*') ? 'active' : '' }}"
                            href="/ticket-instances">
                            <span class="menu-icon">
                                <i class="ki-outline ki-element-7 fs-2"></i>
                            </span>
                            <span class="menu-title">{{ __('messages.tickets') }}</span>
                        </a>
                    </div>
                @endcanany


                {{-- ================= TAQUILLA ================= --}}
                @role('taquillero')


                <div class="menu-item menu-accordion {{ request()->is('taquilla*', 'reimpresion*') ? 'show' : '' }}"
                    data-kt-menu-trigger="click">

                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-outline ki-element-7 fs-2"></i>
                        </span>
                        <span class="menu-title">Taquilla</span>
                        <span class="menu-arrow"></span>
                    </span>

                    <div class="menu-sub menu-sub-accordion">

                        {{-- Venta --}}
                        @can('vender boletos')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->is('taquilla*') ? 'active' : '' }}" href="/taquilla">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Venta</span>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>


                <div class="menu-item menu-accordion {{ request()->is('taquilla*', 'reimpresion*') ? 'show' : '' }}"
                    data-kt-menu-trigger="click">

                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-outline ki-element-7 fs-2"></i>
                        </span>
                        <span class="menu-title">Reimpresión</span>
                        <span class="menu-arrow"></span>
                    </span>

                    <div class="menu-sub menu-sub-accordion">

                        {{-- Eventos dinámicos --}}
                        @foreach($sidebarEvents as $event)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->is('registrations/' . $event->id) ? 'active' : '' }}"
                                    href="{{ route('admin.registrations.index', $event->id) }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">
                                        {{ $event->name }}
                                    </span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endrole



                {{-- ================= ESCANER ================= --}}
                @can('escanear boletos')
                    <div class="menu-item">
                        <a class="menu-link {{ request()->is('checkin*') ? 'active' : '' }}" href="/checkin">
                            <span class="menu-icon">
                                <i class="ki-outline ki-scan-barcode fs-2"></i>
                            </span>
                            <span class="menu-title">Escanear boletos</span>
                        </a>
                    </div>
                @endcan





                {{-- ================= REPORTES ================= --}}
                @canany(['ver reportes', 'exportar reportes'])
                    <div class="menu-item">
                        <a class="menu-link {{ request()->is('dashboard*') ? 'active' : '' }}" href="/dashboard">
                            <span class="menu-icon">
                                <i class="ki-outline ki-chart-line fs-2"></i>
                            </span>
                            <span class="menu-title">Reportes</span>
                        </a>
                    </div>
                @endcanany


                {{-- ================= CONFIGURACIONES () =================--}}
                @can('crear eventos')
                    <div class="menu-item menu-accordion {{ request()->is('users*') ? 'show' : '' }}"
                        data-kt-menu-trigger="click">

                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-outline ki-setting-3 fs-2"></i>
                            </span>
                            <span class="menu-title">Configuraciones</span>
                            <span class="menu-arrow"></span>
                        </span>

                        <div class="menu-sub menu-sub-accordion">
                            @can('crear eventos')
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('users*') ? 'active' : '' }}" href="/users">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Usuarios</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('roles*') ? 'active' : '' }}" href="/roles">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Roles</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('permissions*') ? 'active' : '' }}"
                                        href="/permissions">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Permisos</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('admin/checkin-management*') ? 'active' : '' }}"
                                        href="{{ route('admin.checkin_management.index') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Modulo checkin</span>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endcan

                {{-- ================= INSCRIPCIONES ================= --}}
                @canany(['ver registros cumbres', 'ver inscripciones'])
                <div class="menu-item menu-accordion {{ request()->is('registrations*') ? 'show' : '' }}"
                    data-kt-menu-trigger="click">

                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-outline ki-user-square fs-2"></i>
                        </span>
                        <span class="menu-title">Ventas</span>
                        <span class="menu-arrow"></span>
                    </span>

                    <div class="menu-sub menu-sub-accordion">

                        @canany(['crear eventos', 'editar eventos', 'eliminar eventos', 'configurar eventos'])
                        {{-- Ver todos --}}
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('registrations') ? 'active' : '' }}"
                                href="{{ route('admin.registrations.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Todos</span>
                            </a>
                        </div>
                        @endcan
                        {{-- Eventos dinámicos --}}
                        @foreach($sidebarEvents as $event)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->is('registrations/' . $event->id) ? 'active' : '' }}"
                                    href="{{ route('admin.registrations.index', $event->id) }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">
                                        {{ $event->name }}
                                    </span>
                                </a>
                            </div>
                        @endforeach

                    </div>
                </div>
                @endcan
                @endauth
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
