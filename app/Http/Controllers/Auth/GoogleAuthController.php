<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Client;
class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')
            ->stateless()
            ->setHttpClient(new Client(['verify' => false]))
            ->user();

        $user = User::updateOrCreate(
            ['google_id' => $googleUser->id],
            [
                'name'  => $googleUser->name,
                'email' => $googleUser->email,
            ]
        );

        Auth::login($user);

        return redirect()->intended('/events');
    }
}
