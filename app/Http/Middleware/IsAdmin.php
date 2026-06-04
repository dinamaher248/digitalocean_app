<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    public function handle($request, Closure $next)
    {
        // return print_r(Auth::guard('web')->check());
        if (Auth::guard('web')->check() && Auth::guard('web')->user()->is_admin == 1) {
            return $next($request);
        }

        return redirect('/admin/login');
    }
}

