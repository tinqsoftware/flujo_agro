<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Redirect users after login based on their role.
     */
    protected function redirectTo()
    {
        $user = Auth::user();
        
        if ($user && $user->rol) {
            switch (strtoupper($user->rol->nombre)) {
                case 'SUPERADMIN':
                    return '/superadmin/dashboard';
                case 'ADMINISTRADOR':
                    return '/admin/dashboard';
                case 'ADMINISTRATIVO':
                    return '/user/dashboard';
                default:
                    return '/dashboard';
            }
        }
        
        return '/dashboard';
    }
}
