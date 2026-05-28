<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Eventos;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'events'])->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();
        $events = Eventos::orderBy('name')->get();
        $allRoles = Role::with('permissions')->orderBy('name')->get();
        $allPermissions = Permission::orderBy('name')->get();

        return view('admin.users.index', compact(
            'users',
            'roles',
            'permissions',
            'events',
            'allRoles',
            'allPermissions'
        ));
    }

    public function create()
    {
        $roles = Role::all();
        $events = Eventos::all();
        $permissions = Permission::all();

        return view('admin.users.create', compact('roles', 'events', 'permissions'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required'
        ], [
            'email.unique' => 'El correo ya existe. Usa otro diferente.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole($request->role);
        $user->syncPermissions($request->permissions ?? []);

        // sincronizar eventos
        $user->events()->sync($request->events ?? []);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Usuario creado correctamente.',
                'user_id' => $user->id,
            ]);
        }

        return redirect()->route('users.index')
            ->with('success', 'Usuario creado correctamente');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $events = Eventos::all();
        $permissions = Permission::all();

        return view('admin.users.edit', compact('user', 'roles', 'events', 'permissions'));
    }


    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required'
        ], [
            'email.unique' => 'El correo ya existe. Usa otro diferente.',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        $user->syncRoles([$request->role]);
        $user->syncPermissions($request->permissions ?? []);

        $user->events()->sync($request->events ?? []);

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado correctamente');
    }

    public function checkEmail(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $query = User::query()->where('email', $data['email']);

        if (!empty($data['user_id'])) {
            $query->where('id', '!=', $data['user_id']);
        }

        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'El correo ya existe. Usa otro diferente.' : null,
        ]);
    }

    public function updatePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        $user->update([
            'password' => bcrypt((string) $request->input('password')),
        ]);

        return redirect()
            ->route('users.index', ['tab' => 'users'])
            ->with('success', 'Contraseña actualizada correctamente.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success', 'Usuario eliminado');
    }
}
