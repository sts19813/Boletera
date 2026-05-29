@extends('layouts.app')

@section('title', 'Usuarios y permisos')

@section('content')
@php
    $activeTab = request('tab', old('active_tab', 'users'));
    if (!in_array($activeTab, ['users', 'roles', 'permissions'], true)) {
        $activeTab = 'users';
    }
@endphp

<div class="card shadow-sm">
    <div class="card-header border-0 pt-6">
        <h3 class="card-title fw-bold mb-0">Usuarios y permisos</h3>
    </div>

    <div class="card-body pt-2">
        @if(session('success'))
            <div class="alert alert-success mb-6">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger mb-6">
                <ul class="mb-0 ps-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <ul class="nav nav-tabs nav-line-tabs mb-8 fs-6" id="usersPermissionsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'users' ? 'active' : '' }}" id="users-tab" data-bs-toggle="tab"
                    data-bs-target="#users-tab-pane" type="button" role="tab" aria-controls="users-tab-pane"
                    aria-selected="{{ $activeTab === 'users' ? 'true' : 'false' }}" data-tab-key="users">
                    Usuarios
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'roles' ? 'active' : '' }}" id="roles-tab" data-bs-toggle="tab"
                    data-bs-target="#roles-tab-pane" type="button" role="tab" aria-controls="roles-tab-pane"
                    aria-selected="{{ $activeTab === 'roles' ? 'true' : 'false' }}" data-tab-key="roles">
                    Roles
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'permissions' ? 'active' : '' }}" id="permissions-tab"
                    data-bs-toggle="tab" data-bs-target="#permissions-tab-pane" type="button" role="tab"
                    aria-controls="permissions-tab-pane" aria-selected="{{ $activeTab === 'permissions' ? 'true' : 'false' }}"
                    data-tab-key="permissions">
                    Permisos
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade {{ $activeTab === 'users' ? 'show active' : '' }}" id="users-tab-pane"
                role="tabpanel" aria-labelledby="users-tab">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 mb-6">
                    <div class="w-100 w-md-350px">
                        <input type="text" id="usersSearchInput" class="form-control form-control-solid"
                            placeholder="Buscar usuario por nombre, email o rol...">
                    </div>

                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#createUserModal">
                        Nuevo usuario
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed" id="usersTable">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Eventos</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @foreach($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <span class="badge badge-light-primary">
                                            {{ $user->getRoleNames()->first() ?? 'Sin rol' }}
                                        </span>
                                    </td>
                                    <td>{{ $user->events->count() }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-light-primary me-2">
                                            Editar
                                        </a>

                                        <button type="button" class="btn btn-sm btn-light-warning me-2 js-open-password-modal"
                                            data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}">
                                            Contraseña
                                        </button>

                                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-light-danger">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade {{ $activeTab === 'roles' ? 'show active' : '' }}" id="roles-tab-pane" role="tabpanel"
                aria-labelledby="roles-tab">
                <div class="d-flex justify-content-end mb-6">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                        Crear rol
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed" id="rolesTable">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>Rol</th>
                                <th>Permisos asignados</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @foreach($allRoles as $role)
                                <tr>
                                    <td>{{ ucfirst($role->name) }}</td>
                                    <td>
                                        @if($role->permissions->count())
                                            @foreach($role->permissions as $permission)
                                                <span class="badge badge-light-primary me-1 mb-1">{{ $permission->name }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">Sin permisos</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-light-primary me-2">
                                            Editar
                                        </a>

                                        <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('¿Seguro que deseas eliminar este rol?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-light-danger">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade {{ $activeTab === 'permissions' ? 'show active' : '' }}" id="permissions-tab-pane"
                role="tabpanel" aria-labelledby="permissions-tab">
                <div class="d-flex justify-content-end mb-6">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#createPermissionModal">
                        Crear permiso
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed" id="permissionsTable">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>Permiso</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @foreach($allPermissions as $permission)
                                <tr>
                                    <td>{{ $permission->name }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('permissions.destroy', $permission) }}"
                                            onsubmit="return confirm('¿Seguro que deseas eliminar este permiso?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-light-danger">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="createUserForm" method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div id="create-user-general-error" class="alert alert-danger d-none"></div>

                    <div class="row g-5 mb-7">
                        <div class="col-md-6">
                            <label class="form-label required">Nombre</label>
                            <input type="text" name="name" class="form-control form-control-solid" required>
                            <div class="invalid-feedback" data-error-for="name"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required">Email</label>
                            <input type="email" name="email" id="create-user-email" class="form-control form-control-solid" required>
                            <div class="invalid-feedback" id="create-user-email-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required">Contraseña</label>
                            <input type="password" name="password" class="form-control form-control-solid" required minlength="6">
                            <div class="invalid-feedback" data-error-for="password"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required">Rol</label>
                            <select name="role" class="form-select form-select-solid" required>
                                <option value="">Selecciona</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-error-for="role"></div>
                        </div>
                    </div>

                    <div class="mb-7">
                        <h5 class="fw-bold mb-4 text-primary">Permisos adicionales</h5>
                        <div class="row">
                            @foreach($permissions as $permission)
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="permissions[]"
                                            value="{{ $permission->name }}">
                                        <label class="form-check-label">{{ $permission->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h5 class="fw-bold mb-4 text-primary">Acceso a eventos</h5>
                        <div class="row">
                            @foreach($events as $event)
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="events[]" value="{{ $event->id }}">
                                        <label class="form-check-label">{{ $event->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="createUserSubmitBtn">Crear usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="updatePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="updatePasswordForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Actualizar contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <p class="text-muted mb-5">Usuario: <strong id="password-user-name">-</strong></p>

                    <div class="mb-5">
                        <label class="form-label required">Nueva contraseña</label>
                        <input type="password" name="password" class="form-control form-control-solid" minlength="6" required>
                    </div>

                    <div>
                        <label class="form-label required">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-solid" minlength="6"
                            required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Guardar contraseña</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="createRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('roles.store') }}">
                @csrf
                <input type="hidden" name="active_tab" value="roles">

                <div class="modal-header">
                    <h5 class="modal-title">Crear rol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-6">
                        <label class="form-label required">Nombre del rol</label>
                        <input type="text" name="name" class="form-control form-control-solid" required>
                    </div>

                    <div>
                        <h6 class="fw-bold mb-3">Permisos asignados</h6>
                        <div class="row">
                            @foreach($allPermissions as $permission)
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="permissions[]"
                                            value="{{ $permission->name }}">
                                        <label class="form-check-label">{{ $permission->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear rol</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="createPermissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('permissions.store') }}">
                @csrf
                <input type="hidden" name="active_tab" value="permissions">

                <div class="modal-header">
                    <h5 class="modal-title">Crear permiso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <label class="form-label required">Nombre del permiso</label>
                    <input type="text" name="name" class="form-control form-control-solid" required>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear permiso</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const usersTableElement = document.getElementById('usersTable');
        const usersSearchInput = document.getElementById('usersSearchInput');
        let usersDataTable = null;

        if (usersTableElement && window.jQuery && $.fn.DataTable) {
            usersDataTable = $(usersTableElement).DataTable({
                pageLength: 10,
                order: [],
                language: { url: '//cdn.datatables.net/plug-ins/2.3.2/i18n/es-MX.json' }
            });
        }

        if (usersSearchInput && usersDataTable) {
            usersSearchInput.addEventListener('keyup', function (event) {
                usersDataTable.search(event.target.value || '').draw();
            });
        }

        const tabsRoot = document.getElementById('usersPermissionsTabs');
        if (tabsRoot) {
            tabsRoot.querySelectorAll('button[data-bs-toggle="tab"]').forEach((tabButton) => {
                tabButton.addEventListener('shown.bs.tab', function (event) {
                    const key = event.target.getAttribute('data-tab-key') || 'users';
                    const nextUrl = new URL(window.location.href);
                    nextUrl.searchParams.set('tab', key);
                    window.history.replaceState({}, '', nextUrl.toString());
                });
            });
        }

        const updatePasswordModalEl = document.getElementById('updatePasswordModal');
        const updatePasswordForm = document.getElementById('updatePasswordForm');
        const passwordUserNameEl = document.getElementById('password-user-name');

        document.querySelectorAll('.js-open-password-modal').forEach((button) => {
            button.addEventListener('click', function () {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name') || '-';

                if (!userId || !updatePasswordForm) {
                    return;
                }

                updatePasswordForm.action = `/users/${userId}/password`;
                if (passwordUserNameEl) {
                    passwordUserNameEl.textContent = userName;
                }

                if (updatePasswordModalEl) {
                    const modal = bootstrap.Modal.getOrCreateInstance(updatePasswordModalEl);
                    modal.show();
                }
            });
        });

        const createUserForm = document.getElementById('createUserForm');
        const createUserSubmitBtn = document.getElementById('createUserSubmitBtn');
        const createUserEmail = document.getElementById('create-user-email');
        const createUserEmailError = document.getElementById('create-user-email-error');
        const createUserGeneralError = document.getElementById('create-user-general-error');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const resetCreateUserErrors = () => {
            if (!createUserForm) {
                return;
            }

            createUserForm.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
            createUserForm.querySelectorAll('.invalid-feedback').forEach((feedback) => {
                if (feedback.id !== 'create-user-email-error') {
                    feedback.textContent = '';
                }
            });

            if (createUserEmailError) {
                createUserEmailError.textContent = '';
            }

            if (createUserGeneralError) {
                createUserGeneralError.classList.add('d-none');
                createUserGeneralError.textContent = '';
            }
        };

        const setFieldError = (name, message) => {
            if (!createUserForm) {
                return;
            }

            const field = createUserForm.querySelector(`[name="${name}"]`);
            if (field) {
                field.classList.add('is-invalid');
            }

            const feedback = createUserForm.querySelector(`[data-error-for="${name}"]`);
            if (feedback) {
                feedback.textContent = message;
            }
        };

        const checkEmailExists = async () => {
            if (!createUserEmail) {
                return false;
            }

            const email = (createUserEmail.value || '').trim();
            if (!email) {
                createUserEmail.classList.remove('is-invalid');
                if (createUserEmailError) {
                    createUserEmailError.textContent = '';
                }
                return false;
            }

            try {
                const response = await fetch('{{ route('users.check-email') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ email })
                });

                const payload = await response.json();
                const exists = Boolean(payload?.exists);

                if (exists) {
                    createUserEmail.classList.add('is-invalid');
                    if (createUserEmailError) {
                        createUserEmailError.textContent = payload?.message || 'El correo ya existe.';
                    }
                } else {
                    createUserEmail.classList.remove('is-invalid');
                    if (createUserEmailError) {
                        createUserEmailError.textContent = '';
                    }
                }

                return exists;
            } catch (error) {
                return false;
            }
        };

        if (createUserEmail) {
            createUserEmail.addEventListener('blur', function () {
                checkEmailExists();
            });
        }

        if (createUserForm) {
            createUserForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                resetCreateUserErrors();

                const duplicateEmail = await checkEmailExists();
                if (duplicateEmail) {
                    return;
                }

                const formData = new FormData(createUserForm);

                if (createUserSubmitBtn) {
                    createUserSubmitBtn.disabled = true;
                }

                try {
                    const response = await fetch(createUserForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: formData
                    });

                    const payload = await response.json();

                    if (response.ok) {
                        window.location.href = '{{ route('users.index', ['tab' => 'users']) }}';
                        return;
                    }

                    if (response.status === 422 && payload?.errors) {
                        Object.entries(payload.errors).forEach(([name, messages]) => {
                            const message = Array.isArray(messages) ? messages[0] : messages;
                            setFieldError(name, message);

                            if (name === 'email' && createUserEmailError) {
                                createUserEmailError.textContent = message;
                            }
                        });
                        return;
                    }

                    if (createUserGeneralError) {
                        createUserGeneralError.classList.remove('d-none');
                        createUserGeneralError.textContent = payload?.message || 'No se pudo crear el usuario.';
                    }
                } catch (error) {
                    if (createUserGeneralError) {
                        createUserGeneralError.classList.remove('d-none');
                        createUserGeneralError.textContent = 'No se pudo crear el usuario. Intenta de nuevo.';
                    }
                } finally {
                    if (createUserSubmitBtn) {
                        createUserSubmitBtn.disabled = false;
                    }
                }
            });
        }
    });
</script>
@endpush
