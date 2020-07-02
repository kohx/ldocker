<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

前回作成したUIで使用する認証Apiをlaravelで作ります。


> 連載記事

<ul class="blog-index-list">
  <li><a href="https://www.aska-ltd.jp/jp/blog/52">Laravel mix vue No.1 - Docker Environment - Dockerでlaravel環境 (laradockを使わない)</li></a>
  <li><a href="https://www.aska-ltd.jp/jp/blog/56">Laravel mix vue No.2 - Vue Router, Component - Vueルータの設定とコンポネント作成</li></a>
  <li><a href="https://www.aska-ltd.jp/jp/blog/65">Laravel mix vue No.3 - Authentication API - Apiで認証</li></a>
  <li><a href="https://www.aska-ltd.jp/jp/blog/66">Laravel mix vue No.4 - Vuex - Vuexで状態管理</li></a>
  <li><a href="https://www.aska-ltd.jp/jp/blog/69">Laravel mix vue No.5 - Api Email Verification - メール認証に変更</li></a>
  <li><a href="https://www.aska-ltd.jp/jp/blog/70">Laravel mix vue No.6 - Api Resetting Passwords - パスワードリセット</li></a>
  <li><a href="https://www.aska-ltd.jp/jp/blog/72">Laravel mix vue No.7 - Socialite - ソーシャルログイン</li></a>
  <li><a href="https://www.aska-ltd.jp/jp/blog/73">Laravel mix vue No.8 - Laravel Internationalization - Laravel多言語化</li></a>
  <li><a href="https://www.aska-ltd.jp/jp/blog/76">Laravel mix vue No.9 - Vue Internationalization - Vue多言語化</li></a>
  <!-- <li><a href="https://www.aska-ltd.jp/jp/blog/76">Laravel mix vue No.10 - Vue Font Awesome - フォントアイコン追加</a></li> -->
</ul>

# Authentication API - Apiで認証

## サンプル
- このセクションを始める前  
[github ldocker 03](https://github.com/kohx/ldocker/tree/03)  
  
- 完成  
[github ldocker 04](https://github.com/kohx/ldocker/tree/04)

---

## フォルダ構成

```text

└─ server
   └─ app
   │  └─ Providers
   │  │ └─ RouteServiceProvider.php
   │  └─ Http
   │    └─ Controllers
   │       └─ Auth
   │          └─ RegisterController.php
   │          └─ LoginController.php
   │    └─ Kernel.php
   └─ routes
   │   └─ web.php
   │
   └─ vendor
      └─ laravel
         └─ ui
            └─ auth-backend
               └─ RegistersUsers.php
               └─ AuthenticatesUsers.php
```

---

## Apiのルート作成

### Apiのルート定義を確認とホームルートの設定

```php:server\app\Providers\RouteServiceProvider.php

    ...

    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
-   public const HOME = '/home';
+   public const HOME = '/';

    ...

    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api') // これは「app/Http/Kernel.php」の「protected $middlewareGroups」に定義
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    ...

```

### ミドルウェアの設定

```php:server\app\Http\Kernel.php

    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
-           'throttle:60,1',
+           // 'throttle:60,1',
            // 'web'の内容をペースト
+           \App\Http\Middleware\EncryptCookies::class,
+           \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
+           \Illuminate\Session\Middleware\StartSession::class,
+           // \Illuminate\Session\Middleware\AuthenticateSession::class,
+           \Illuminate\View\Middleware\ShareErrorsFromSession::class,
+           \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

```

### ルートの設定

```php:server\routes\api.php

-   Route::middleware('auth:api')->get('/user', function (Request $request) {
-       return $request->user();
-   });

    // register
+   Route::post('/register', 'Auth\RegisterController@register')->name('register');
    // login
+   Route::post('/login', 'Auth\LoginController@login')->name('login');
    // logout
+   Route::post('/logout', 'Auth\LoginController@logout')->name('logout');

    // user
+   Route::get('/user', function () {
+       return Auth::user();
+   })->name('user');

    // トークンリフレッシュ
+   Route::get('/refresh-token', function (Illuminate\Http\Request $request) {
+       $request->session()->regenerateToken();
+       return response()->json();
+   })->name('refresh-token');

```

---

## 登録Apiの作成

### registeredメソッドの確認

RegistersUsersトレイトの中にメソッドのみが定義されている

```php:server\vendor\laravel\ui\auth-backend\RegistersUsers.php

...

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        //
    }

...

```

### registeredメソッドの作成

registered メソッドをカスタマイズするために 「server\app\Http\Controllers\Auth\RegisterController.php」
のなかに上記トレイトで追加されている registered メソッドをオーバーライドする

```JS:server\app\Http\Controllers\Auth\RegisterController.php

<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use App\Providers\RouteServiceProvider;
    use App\User;
    use Illuminate\Foundation\Auth\RegistersUsers;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Validator;
+   use Illuminate\Http\Request;

    class RegisterController extends Controller
    {
        ...

+       protected function registered(Request $request, $user)
+       {
+           return $user;
+       }

        ...
    }

```

### authenticatedメソッドとloggedOutメソッドの確認

AuthenticatesUsers トレイトの中にメソッドのみが定義されている

```php:server\vendor\laravel\ui\auth-backend\AuthenticatesUsers.php

...

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        //
    }

...

    /**
     * The user has logged out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function loggedOut(Request $request)
    {
        //
    }

...

```

### authenticatedメソッドとloggedOutメソッドの作成

authenticated メソッドをカスタマイズするために 「server\app\Http\Controllers\Auth\LoginController.php」
のなかに上記トレイトで追加されている authenticatedメソッドと loggedOutメソッドをオーバーライドする

```JS:server\app\Http\Controllers\Auth\RegisterController.php

<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use App\Providers\RouteServiceProvider;
    use Illuminate\Foundation\Auth\AuthenticatesUsers;
+   use Illuminate\Http\Request;

    class LoginController extends Controller
    {

        ...

+       /**
+        * Send the response after the user was authenticated.
+        *
+        * @param  \Illuminate\Http\Request  $request
+        * @return \Illuminate\Http\Response
+        */
+       protected function authenticated(Request $request, $user)
+       {
+           return $user;
+       }

+       /**
+        * The user has logged out of the application.
+        *
+        * @param  \Illuminate\Http\Request  $request
+        * @return mixed
+        */
+       protected function loggedOut(Request $request)
+       {
+           // セッションの再生成
+            $request->session()->regenerate();
+
+            return response()->json();
+       }

    }


```

## ドルウェアによる認証時のリダイレクトコントロールを修正

ログインには非ログイン状態でのみアクセスできるようにドルウェアでコントロールされているので、
ログイン時に認証するとデフォルトでは`RouteServiceProvider`に設定している`HOME`にリダイレクトされる

ApiでHOMEに設定されたページのHTMLが返されるのではなく、userApi（/user）のレスポンスが返されるように
`server\app\Http\Middleware\RedirectIfAuthenticated.php`を修正する

```php:server\app\Http\Middleware\RedirectIfAuthenticated.php

...

    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
-           return redirect(RouteServiceProvider::HOME);
+           return redirect()->route('user');
        }

        return $next($request);
    }

...

```

これでUIで使用する認証Apiができました！

> 次はこれ[Laravel mix vue No.4 - Vuex - Vuexで状態管理](https://www.aska-ltd.jp/jp/blog/66)

<script src="https://www.aska-ltd.jp/js/blog.js"></script>
<link href="https://www.aska-ltd.jp/css/blog.css" rel="stylesheet">