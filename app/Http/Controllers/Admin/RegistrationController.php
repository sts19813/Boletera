<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegistrationInstance;
class RegistrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $instances = RegistrationInstance::with([
            'evento',
            'registration.players'
        ])->latest()->get();

        return view('admin.registrations.index', compact('instances'));
    }
}
