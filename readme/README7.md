<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

今回はLaravel Socialiteでtwitterで認証を追加しいます。  
Laravel Socialiteは他にもFacebook、Twitter、LinkedIn、Google、GitHub、GitLab、Bitbucketをサポートしています。  
他にもコミュニティによりたくさんのアダプタが作られているます。  
[Socialiteプロバイダ一覧](https://socialiteproviders.netlify.app/about.html)  
  
ここではTwitterを実装してみます。  
詳しい設定方法はいろんなとこで紹介されているので参考にして作成してください。  

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

# Socialite - ソーシャルログイン

## サンプル
- このセクションを始める前  
[github ldocker 07](https://github.com/kohx/ldocker/tree/07)  
  
- 完成  
[github ldocker 08](https://github.com/kohx/ldocker/tree/08)

------------------------------------------------------------------------------------------

## フォルダ構成

```text

└─ server
   ├─ app
   |  ├─ Http
   |  |  └─ Controllers
   |  |     └─ Auth
   |  |        ├─ LoginController.php
   |  |        ├─ VerificationController.php
   |  |        └─ ResetPasswordController.php
   |  ├─ User.php
+  |  └─ Traits
+  |     └─ Vueable.php
   ├─ config
   |  ├─ services.php
   |  └─ auth.php
   ├─ database
   |  └─ migrations
+  |     └─ 20xx_xx_xx_xx_change_users_table.php
   ├─ resources
   |  └─ js
   |     └─ pages
   |        └─ Login.vue
   ├─ routes
   |  └─ web.php
   └─ .env
```

------------------------------------------------------------------------------------------

## ホットリロードスタート

gitからクローンした場合は`.env`の作成と設定を忘れないように！

```bash
# コンテナスタート
docker-compose start

# コンテナに入る
docker-compose exec php bash

# composerをインストール（前回からの続きで行う場合はいらない）
composer install

# npmをインストール（前回からの続きで行う場合はいらない）
npm i

# encryption keyを作成（前回からの続きで行う場合はいらない）
php artisan key:generate

# ホットリリード開始
npm run watch
```

------------------------------------------------------------------------------------------

## インストール

composerで`laravel/socialite`をインストール

```bash

composer require laravel/socialite

```

### サービスプロバイダへの登録とエイリアスの作成

`server\config\app.php`を編集して
`use Laravel\Socialite\Facades\Socialite;`を`use Socialite;`とかけるようにする

```php:server\config\app.php

    <?php

    return [
          ...
          'providers' => [
            ...
            /*
            * Package Service Providers...
            */
+           Laravel\Socialite\SocialiteServiceProvider::class,
            ...
          ],

          'aliases' => [
            ...
            'View' => Illuminate\Support\Facades\View::class,
+           'Socialite' => Laravel\Socialite\Facades\Socialite::class,
          ],

    ];

```

### コンフィグをクリアする

```bash:terminal

php artisan config:clear

```

---

## プロバイダの準備

### プロバイダカウントを作る

今回はTwitterで行う  

[https://developer.twitter.com](https://developer.twitter.com)でAppを作成

### .envに設定する

`server\.env`を修正

```text:server\.env

    TWITTER_CLIENT_ID=xxxxxxxxxxxxxxxxxxxxxxxxx
    TWITTER_CLIENT_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
    TWITTER_CALLBACK_URL=/login/twitter/callback

```

### configを設定

`server\config\services.php`を修正

```php:server\config\services.php

    <?php

    return [

        ...
        // twitter
        'twitter' => [
            'client_id' => env('TWITTER_CLIENT_ID'),
            'client_secret' => env('TWITTER_CLIENT_SECRET'),
            'redirect' => env('TWITTER_CLIENT_CALLBACK')
        ],
      ];

```

------------------------------------------------------------------------------------------

## 前準備としてトレイトをつくって処理をまとめる

### トレイトの作成

なんどもvueのルートにクッキー付きでリダイレクトしているのでトレイトをつくって使い回す。  
後で修正する場合にも楽になるはず。  
名前はVueに関係するので「Vueable」にする  

`server\app\Traits\Vueable.php`としてトレイトを作成

```php:server\app\Traits\Vueable.php

    <?php

    namespace App\Traits;

    trait Vueable
    {
        /**
         * redirect to vue route with message cookie
         *
         * @param string $vueRoute
         * @param string $message
         * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
         */
        public function redirectVue($vueRoute = '', $message = '')
        {
            // vueでアクセスするルートを作る
            // コールバックURLをルート名で取得
            // TODO: これだとホットリロードでポートがとれない
            // $route = url($vueRoute);

            // TODO: とりあえずこれで対応
            // .envの「APP_URL」に設定したurlを取得
            $baseUrl = config('app.url');
            $route = "{$baseUrl}/{$vueRoute}";

            if ($message) {
                return redirect($route);
            } else {
                return redirect($route)
                // PHPネイティブのsetcookieメソッドに指定する引数同じ
                // ->cookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly)
                ->cookie('MESSAGE', $message, 0, '', '', false, false);
            }
        }
    }

```

### トレイトのメソッドと差し替え

つくったトレイトをuseしてトレイトで作ったメソッドと入れ替える  

`server\app\Http\Controllers\Auth\VerificationController.php`を修正する  

```php:server\app\Http\Controllers\Auth\VerificationController.php

    <?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use App\User;
    use Illuminate\Auth\Events\Registered;
    use Illuminate\Support\Facades\Redirect;
    use Illuminate\Support\Facades\Auth;
    use App\Models\RegisterUser;

    // Vueableトレイトを読み込む
+   use App\Traits\Vueable;

    class VerificationController extends Controller
    {
      // Vueableトレイトをuse
+     use Vueable

-     // vueでアクセスするホームへのルート
-     protected $vueRouteHome = '';
-     // vueでアクセスするログインへのルート
-     protected $vueRouteLogin = 'login';

      ...

      /**
       * Complete registration
       * 登録を完了させる
       *
       * @param  \Illuminate\Http\Request  $request
       * @return \Illuminate\Http\Response
       */
      public function register($token)
      {
          // 仮登録のデータをトークンで取得
          $registerUser = $this->getRegisterUser($token);

              // 取得できなかった場合
              if (!$registerUser) {

                // 失敗メッセージを作成
                $message = __('not provisionally registered.');

                // メッセージをつけてリダイレクト
    -           return $this->redirectWithMessage($this->vueRouteLogin, $message);
    +           return $this->redirectVue('login', $message);
          }

          // 仮登録のデータでユーザを作成
          event(new Registered($user = $this->createUser($registerUser->toArray())));

          // 作成したユーザをログインさせる
          Auth::login($user, true);

          // 成功メッセージ
          $message = __('completion of registration.');

          // メッセージをつけてリダイレクト
-         return $this->redirectWithMessage($this->vueRouteHome, $message);
+         return $this->redirectVue('', $message);
      }

    ...

-    /**
-     * redirect with message
-     * メッセージをクッキーに付けてリダイレクト
-     *
-     * @param  string  $vueRoute
-     * @param  string  $message
-     * @return Redirect
-     */
-    protected function redirectWithMessage($vueRoute, $message)
-    {
-         // vueでアクセスするルートを作る
-         // コールバックURLをルート名で取得
-         // TODO: これだとホットリロードでポートがとれない
-         // $route = url($vueRoute);
-  
-         // TODO: とりあえずこれで対応
-         // .envの「APP_URL」に設定したurlを取得
-         $baseUrl = config('app.url');
-         $route = "{$baseUrl}/{$vueRoute}";
-  
-         return redirect($route)
-             // PHPネイティブのsetcookieメソッドに指定する引数同じ
-             // ->cookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly)
-             ->cookie('MESSAGE', $message, 0, '', '', false, false);
-     }
    }
```

`server\app\Http\Controllers\Auth\ResetPasswordController.php`を修正

```php:server\app\Http\Controllers\Auth\ResetPasswordController.php

    <?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use Illuminate\Foundation\Auth\ResetsPasswords;
    use Illuminate\Http\Request;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Lang;
    use Illuminate\Support\Facades\Redirect;
    use Illuminate\Support\Facades\Validator;
    use Illuminate\Support\Facades\Crypt;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Auth\Events\PasswordReset;
    use App\User;
    use App\Models\ResetPassword;

    // Vueableトレイトを読み込む
+   use App\Traits\Vueable;

    class ResetPasswordController extends Controller
    {
      // Vueableトレイトをuse
-     use ResetsPasswords;
+     use ResetsPasswords, Vueable;

-     // vueでアクセスするログインへのルート
-     protected $vueRouteLogin = 'login';
-     // vueでアクセスするリセットへのルート
-     protected $vueRouteReset = 'reset';
      // server\config\auth.phpで設定していない場合のデフォルト
      protected $expires = 600 * 5;

      ...

      /**
       * reset password
       * パスワード変更メールからのコールバック
       *
       * @param string $token
       * @return Redirect
       */
      public function resetPassword($token = null)
      {
          // トークンがあるかチェック
          $isNotFoundResetPassword = ResetPassword::where('token', $token)
              ->doesntExist();

          // なかったとき
          if ($isNotFoundResetPassword) {
              // メッセージをクッキーにつけてリダイレクト
              $message = Lang::get('password reset email has not been sent.');
-             return $this->redirectWithMessage($this->vueRouteLogin, $message);
+             return $this->redirectVue('login', 'MESSAGE', $message);
          }

          // トークンをクッキーにつけてリセットページにリダイレクト
-         return $this->redirectWithToken($this->vueRouteReset, $token);
+         return $this->redirectVue('reset', 'RESETTOKEN', $token);
      }

      ...
      
-     /**
-      * redirect with message
-      *
-      * @param  string  $route
-      * @param  string  $message
-      * @return Redirect
-      */
-     protected function redirectWithMessage($vueRoute, $message)
-     {
-         // vueでアクセスするルートを作る
-         // コールバックURLをルート名で取得
-         // TODO: これだとホットリロードでポートがとれない
-         // $route = url($vueRoute);
-
-         // TODO: とりあえずこれで対応
-         // .envの「APP_URL」に設定したurlを取得
-         $baseUrl = config('app.url');
-         $route = "{$baseUrl}/{$vueRoute}";
-
-         return redirect($route)
-             // PHPネイティブのsetcookieメソッドに指定する引数同じ
-             // ->cookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly)
-             ->cookie('MESSAGE', $message, 0, '', '', false, false);
-     }
-
-     /**
-      * redirect with token
-      *
-      * @param  string  $route
-      * @param  string  $message
-      * @return Redirect
-      */
-     protected function redirectWithToken($vueRoute, $token)
-     {
-         // vueでアクセスするルートを作る
-         // コールバックURLをルート名で取得
-         // TODO: これだとホットリロードでポートがとれない
-         // $route = url($vueRoute);
-
-         // TODO: とりあえずこれで対応
-         // .envの「APP_URL」に設定したurlを取得
-         $baseUrl = config('app.url');
-         $route = "{$baseUrl}/{$vueRoute}";
-         return redirect($route)
-             ->cookie('RESETTOKEN', $token, 0, '', '', false, false);
-     }
    }

```

------------------------------------------------------------------------------------------

## データベースを修正する

### マイグレーションファイルを作成
ユーザテーブルにsocialite用のカラムを追加するためにマイグレーションを作成する

```bash:terminal

  php artisan make:migration change_users_table

```

`server\database\migrations\20xx_xx_xx_xx_change_users_table.php`という感じにマイグレーションファイルがさくせいされるので、これを編集

```php:server\database\migrations\20xx_xx_xx_xx_change_users_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
+       Schema::table('users', function (Blueprint $table) {
            // provider_id を id の後ろに追加して add for socialite のコメントを入れる
+           $table->string('provider_id')->nullable()->after('id')->comment('add for socialite');
            // provider_name を provider_id の後ろに追加して add for socialite のコメントを入れる
+           $table->string('provider_name')->nullable()->after('provider_id')->comment('add for socialite');
            // nickname を name の後ろに追加して add for socialite のコメントを入れる
+           $table->string('nickname')->nullable()->after('name')->comment('add for socialite');
            // avatar を email の後ろに追加して add for socialite のコメントを入れる
+           $table->string('avatar')->nullable()->after('email')->comment('add for socialite');
            // socialiteでログインした場合メールアドレスがない場合があるのでnull許容に変更
+           $table->string('email')->nullable()->change()->comment('change to nullable for socialite');
            // socialiteでログインした場合パスワードがないのでnull許容に変更
+           $table->string('password')->nullable()->change()->comment('change to nullable for socialite');
+       });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
+       Schema::table('users', function (Blueprint $table) {
+           $table->dropColumn(['provider_id', 'provider_name', 'nickname', 'avatar']);
+           $table->string('email')->nullable(false)->change();
+           $table->string('password')->nullable(false)->change();
+       });
    }
}

```

### マイグレーション実行

```bash:terminal

  # カラムの変更に必要なライブラなのでこれを入れないとエラー
  composer require doctrine/dbal
  
  php artisan migrate

```

------------------------------------------------------------------------------------------

## socialite組み込み

### ルートを追加

各プロバイダにリダイレクトするルート、各プロバイダからのコールバックを受けるルートを作成するので`server\routes\web.php`を編集

```php:server\routes\web.php

    // socialite 各プロバイダにリダイレクトするルート
+   Route::get('/login/{provider}', 'Auth\LoginController@redirectToProvider');

    // socialite 各プロバイダからのコールバックを受けるルート
+   Route::get('/login/{provider}/callback', 'Auth\LoginController@handleProviderCallback');

    // API以外はindexを返すようにして、VueRouterで制御
    Route::get('/{any?}', fn () => view('index'))->where('any', '.+');
```

### Userモデルを修正

マイグレーションで追加したカラムを更新できるように`server\app\User.php`を修正する

```php:server\app\User.php
    ...
    class User extends Authenticatable
    {
        use Notifiable;

        /**
        * The attributes that are mass assignable.
        *
        * @var array
        */
        protected $fillable = [
            'name', 'email', 'password', 'email_verified_at',
+           'provider_id', 'provider_name', 'nickname', 'avatar',
        ];
    ...
```

### LoginControllerを編集

`server\app\Http\Controllers\Auth\LoginController.php`


```php:server\app\Http\Controllers\Auth\LoginController.php

<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use App\Providers\RouteServiceProvider;
+   use App\User;
    use Illuminate\Foundation\Auth\AuthenticatesUsers;
    use Illuminate\Http\Request;
+   use Auth;
+   use Lang;
+   use Socialite;
+   use App\Traits\Vueable;

    class LoginController extends Controller
    {
-       use AuthenticatesUsers;
+       use AuthenticatesUsers, Vueable;

        /**
         * ログイン制限が設定
         *
         * laravel標準のLoginControllerを使う場合、ログイン失敗の数によってログイン制限が設定されている
         * 場所は上の「AuthenticatesUsersトレイト」でuseされている「ThrottlesLoginsトレイト」にある「maxAttemptsメソッド」と「decayMinutesメソッド」
         * $this->maxAttemptsがない場合は5がデフォルト
         * $this->decayMinutesがない場合1がデフォルト
         * よって、試行回数：5回 / ロック時間：1分
         */
        // ログイン試行回数（回）
+       protected $maxAttempts = 5;
        // ログインロックタイム（分）
+       protected $decayMinutes = 1;

        /**
         * Where to redirect users after login.
         *
         * @var string
         */
        protected $redirectTo = RouteServiceProvider::HOME;

        /**
         * Create a new controller instance.
         *
         * @return void
         */
        public function __construct()
        {
            $this->middleware('guest')->except('logout');

            // ログイン制限 試行回数（回）
+           $this->maxAttempts = config('auth.throttles_logins.maxAttempts', $this->maxAttempts);

            // ログイン制限 ロックタイム（分）
+           $this->decayMinutes = config('auth.throttles_logins.decayMinutes', $this->decayMinutes);
        }

        /**
         * Send the response after the user was authenticated.
         *
         * @param  \Illuminate\Http\Request  $request
         * @return \Illuminate\Http\Response
         */
        protected function authenticated(Request $request, $user)
        {
            return $user;
        }

        /**
         * The user has logged out of the application.
         *
         * @param  \Illuminate\Http\Request  $request
         * @return mixed
         */
        protected function loggedOut(Request $request)
        {
            // セッションの再生成
            $request->session()->regenerate();

            return response()->json();
        }

+       /**
+        * 認証ページヘユーザーをリダイレクト
+        *
+        * @return \Illuminate\Http\Response
+        */
+       public function redirectToProvider($provider)
+       {
+           return Socialite::driver($provider)
+               // オプションパラメータを含めるには、withメソッドを呼び出し、連想配列を渡す
+               // ->with(['hd' => 'example.com'])
+               // scopesメソッドを使用し、リクエストへ「スコープ」を追加することもでる
+               // ->scopes(['read:user', 'public_repo'])
+               ->redirect();
+       }

+       /**
+        * プロバイダからユーザー情報を取得
+        *
+        * @return \Illuminate\Http\Response
+        */
+       public function handleProviderCallback($provider)
+       {
+           try {
+               // プロバイダからのレスのの中からユーザ情報を取得
+               $providerUser = Socialite::with($provider)->user();
+
+               // メールを取得
+               $email = $providerUser->getEmail() ?? null;
+
+               // プロバイダID
+               $providerId = $providerUser->getId();
+
+               // メールがあるとき
+               if ($email) {
+
+                   // ユーザを作成または更新
+                   $user = User::firstOrCreate([
+                       'email' => $email,
+                   ], [
+                       'provider_id' => $providerId,
+                       'provider_name' => $provider,
+                       'email' => $email,
+                       'name' => $providerUser->getName(),
+                       'nickname' => $providerUser->getNickname() ?? null,
+                       'avatar' => $providerUser->getAvatar() ?? '',
+                   ]);
+               }
+               // プロバイダIDがあるとき
+               elseif($providerId) {
+                    // ユーザを作成または更新
+                    $user = User::firstOrCreate([
+                        'provider_id' => $providerId,
+                        'provider_name' => $provider,
+                    ], [
+                        'provider_id' => $providerUser->getId(),
+                        'provider_name' => $provider,
+                        'email' => $email,
+                        'name' => $providerUser->getName(),
+                        'nickname' => $providerUser->getNickname() ?? null,
+                        'avatar' => $providerUser->getAvatar() ?? '',
+                    ]);
+                }
+                else {
+                    throw new \Exception();
+                }
+
+                // login with remember
+                Auth::login($user, true);
+
+                // メッセージをつけてリダイレクト
+                $message = Lang::get('socialite login success.');
+                return $this->redirectVue('', 'MESSAGE', $message);
+
+           } catch(\Exception $e) {
+
+               // メッセージをつけてリダイレクト
+               $message = $message = Lang::get('authentication failed.');
+               return $this->redirectVue('login', 'MESSAGE', $message);
+           }
+       }
    }

```

### ログイン制限の設定

上記で追加したログイン制限の設定をコンフィグファイルに追加するので`server\config\auth.php`を編集

```php:server\config\auth.php

    <?php
      return [

      ...
    
+     /*
+     |--------------------------------------------------------------------------
+     | throttles logins
+     |--------------------------------------------------------------------------
+     |
+     | ログインロック機能
+     |
+     */
+     'throttles_logins' => [
+         // ログイン試行回数（回）
+         'maxAttempts' => 2,
+         // ログインロックタイム（分）
+         'decayMinutes' => 10,
+     ],
    ];

```


### Vue側の修正

socialiteのログインリンクを作るので`server\resources\js\pages\Login.vue`を修正する

```javascript:server\resources\js\pages\Login.vue

<template>
    <div class="container">
        <!-- tabs -->
        ...
        <!-- /tabs -->

        <!-- login -->
        <section class="login" v-show="tab === 1">
            <h2>Login</h2>

            ...

+           <h2>Socialite</h2>
+           <a class="button" href="/login/twitter" title="twitter">twitter</a>
        </section>
        <!-- /login -->

```

これでAuth関係がだいたいできたと思います。

次はLaravelとVueを多言語化してみます。
 
<!-- > [Laravel mix vue No.8 - Laravel Internationalization - Laravel多言語化](https://www.aska-ltd.jp/jp/blog/73) -->

<script src="https://www.aska-ltd.jp/js/blog.js"></script>
<link href="https://www.aska-ltd.jp/css/blog.css" rel="stylesheet">
