<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        return redirect()->route('users.index', ['tab' => 'permissions']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name'
        ]);

        Permission::create([
            'name' => $request->name
        ]);

        return redirect()
            ->route('users.index', ['tab' => 'permissions'])
            ->with('success', 'Permiso creado correctamente');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()
            ->route('users.index', ['tab' => 'permissions'])
            ->with('success', 'Permiso eliminado');
    }
}
