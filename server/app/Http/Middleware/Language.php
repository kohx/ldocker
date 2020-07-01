<?php

namespace App\Http\Middleware;

use Closure;
use App;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 設定されているロケールを取得
        $locale = App::getLocale();

        // セッションのロケールを取得ないときはデフォルトロケール
        $leng = $request->session()->get('language');

        // ロケールがあって、言語が変わる場合
        if ($leng && $locale !== $leng) {

            // 言語をセット
            // ない場合は「config/app.php」に設定した「fallback_locale」の値になる
            App::setLocale($leng);
        }

        return $next($request);
    }
}
