<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = Auth::user();
        
        if ($user && $user->role === 'admin') {
            return $request->wantsJson()
                ? response()->json(['two_factor' => false])
                : redirect('/admin');
        }

        $redirectUrl = Fortify::redirects('login', config('fortify.home'));

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($redirectUrl);
    }
}
