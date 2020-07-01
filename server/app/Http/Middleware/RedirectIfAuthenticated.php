<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {

            // 非同期通信の場合
            if ($request->ajax()) {
                // リダイレクトしてユーザ情報をを返す
                return redirect()->route('user');
            }
            // 同期通信の場合
            else {
                // ルートにリダイレクト
                return redirect('/');
            }
        }

        return $next($request);
    }
}
