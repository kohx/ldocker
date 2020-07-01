<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

今回はLaravel Socialateでtwitterで認証を追加しいます。  
Laravel Socialateは他にもFacebook、Twitter、LinkedIn、Google、GitHub、GitLab、Bitbucketをサポートしています。  
他にもコミュニティによりたくさんのアダプタが作られているます。  
[Socialiteプロバイダ一覧](https://socialiteproviders.netlify.app/about.html)  
  
ここではTwitterを実装してみます。  
詳しい設定方法はいろんなとこで紹介されているので参考にして作成してください。  

前回の記事は[Laravel mix vue No.6 - パスワードリセット](https://www.aska-ltd.jp/jp/blog/70)

# Socialite ソーシャルログイン

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
+         return $this->redirectVue('reset', 'RESETTOKEN', $message);
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

+           <h2>socialate</h2>
+           <a class="button" href="/login/twitter" title="twitter">twitter</a>
        </section>
        <!-- /login -->

```

これでAuth関係がだいたいできたと思います。

次はLaravelとVueを多言語化してみます。
 
[Laravel mix vue No.8 - Laravel多言語化](https://www.aska-ltd.jp/jp/blog/73)

<script>
    document.addEventListener('DOMContentLoaded', () => {

        document.querySelectorAll('code').forEach(code => {
            fileName(code);
            diffStyle(code);
        })
    });

    function fileName(code){
        const codeList = code.classList;
        if (codeList)
        {
            codeList.forEach(className => {
                if (className.indexOf(':') != -1)
                {
                    const file = className.split(':')[1];
                    const name = className.split(':')[0];
                    code.parentElement.dataset.file = file;
                    code.parentElement.classList.add('code-file');
                    code.classList.add('name')
                }
            })
        }
    }

    function diffStyle(code){
        let diff = document.createElement('code');
        const strings = code.textContent.split(/\r\n|\r|\n/)

        strings.forEach((line, index) => {

            let n = index === 0 ? `` : `\n`;

            const mark = line.slice(0, 1);
            if(mark === '+' || mark === '-'){
                const nNode = document.createTextNode(n);

                const markElm = document.createElement('i');
                markElm.textContent = mark;

                const lineNode = document.createTextNode(line.slice(1));

                const lineElm = document.createElement('em');
                lineElm.classList.add(mark === '+' ? '_plus' : '_min');
                lineElm.append(nNode)
                lineElm.append(markElm)
                lineElm.append(lineNode)

                diff.append(lineElm)
            } else {
                const lineNode = document.createTextNode(`${n}${line}`);
                diff.append(lineNode)
            }
        })

        if(code.parentNode){
            code.parentNode.replaceChild(diff, code);
        }
    }
</script>

<style>
    .blog pre.code-file {
        position: relative;
        padding-top: 2rem;
    }
    .code-file::before {
        content: attr(data-file);
        background: #ccc;
        color: #222;
        display: block;
        font-size: 12px;
        padding: 1px 10px;
        position: absolute;
        top: 0;
        left: 0;
        z-index: 10;
    }

    em._plus,
    em._min,
    em._plus > i,
    em._min > i {
        font-style: normal;
        font-weight: bold;
    }
    em._plus,
    em._plus > i,
    em._plus > i > span {
        /* border-bottom: thin solid blue; */
    }
    em._min,
    em._min > i,
    em._min > i > span {
        /*  border-bottom: thin solid red; */
    }
    em._plus > i, 
		em._plus > i .hljs-comment,
		em._plus > i .hljs-quote,
		em._plus > i .hljs-variable,
		em._plus > i .hljs-addition
		{
        color: blue;
    }
    em._min > i, 
		em._min > i .hljs-comment,
		em._min > i .hljs-quote,
		em._min > i .hljs-variable,
		em._min > i .hljs-addition
		{
        color: red;
    }
</style>

<style>
  .blog .octicon {
    display: inline-block;
    fill: currentColor;
    vertical-align: text-bottom;
  }
  .blog .anchor {
    float: left;
    line-height: 1;
    margin-left: -20px;
    padding-right: 4px;
  }
  .blog .anchor:focus {
    outline: none;
  }
  .blog h1 .octicon-link,
  .blog h2 .octicon-link,
  .blog h3 .octicon-link,
  .blog h4 .octicon-link,
  .blog h5 .octicon-link,
  .blog h6 .octicon-link {
    color: #1b1f23;
    vertical-align: middle;
    visibility: hidden;
  }
  .blog h1:hover .anchor,
  .blog h2:hover .anchor,
  .blog h3:hover .anchor,
  .blog h4:hover .anchor,
  .blog h5:hover .anchor,
  .blog h6:hover .anchor {
    text-decoration: none;
  }
  .blog h1:hover .anchor .octicon-link,
  .blog h2:hover .anchor .octicon-link,
  .blog h3:hover .anchor .octicon-link,
  .blog h4:hover .anchor .octicon-link,
  .blog h5:hover .anchor .octicon-link,
  .blog h6:hover .anchor .octicon-link {
    visibility: visible;
  }
  .blog h1:hover .anchor .octicon-link:before,
  .blog h2:hover .anchor .octicon-link:before,
  .blog h3:hover .anchor .octicon-link:before,
  .blog h4:hover .anchor .octicon-link:before,
  .blog h5:hover .anchor .octicon-link:before,
  .blog h6:hover .anchor .octicon-link:before {
    width: 16px;
    height: 16px;
    content: " ";
    display: inline-block;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' version='1.1' width='16' height='16' aria-hidden='true'%3E%3Cpath fill-rule='evenodd' d='M4 9h1v1H4c-1.5 0-3-1.69-3-3.5S2.55 3 4 3h4c1.45 0 3 1.69 3 3.5 0 1.41-.91 2.72-2 3.25V8.59c.58-.45 1-1.27 1-2.09C10 5.22 8.98 4 8 4H4c-.98 0-2 1.22-2 2.5S3 9 4 9zm9-3h-1v1h1c1 0 2 1.22 2 2.5S13.98 12 13 12H9c-.98 0-2-1.22-2-2.5 0-.83.42-1.64 1-2.09V6.25c-1.09.53-2 1.84-2 3.25C6 11.31 7.55 13 9 13h4c1.45 0 3-1.69 3-3.5S14.5 6 13 6z'%3E%3C/path%3E%3C/svg%3E");
  }
  .blog {
    -ms-text-size-adjust: 100%;
    -webkit-text-size-adjust: 100%;
    line-height: 1.5;
    color: #24292e;
    font-size: 16px;
    line-height: 1.5;
    word-wrap: break-word;
  }
  .blog details {
    display: block;
  }
  .blog summary {
    display: list-item;
  }
  .blog a {
    background-color: initial;
  }
  .blog a:active,
  .blog a:hover {
    outline-width: 0;
  }
  .blog strong {
    font-weight: inherit;
    font-weight: bolder;
  }
  .blog h1 {
    font-size: 2em;
    margin: 0.67em 0;
  }
  .blog img {
    border-style: none;
  }
  .blog code,
  .blog kbd,
  .blog pre {
    font-family: monospace, monospace;
    font-size: 1em;
  }
  .blog hr {
    box-sizing: initial;
    height: 0;
    overflow: visible;
  }
  .blog input {
    font: inherit;
    margin: 0;
  }
  .blog input {
    overflow: visible;
  }
  .blog [type="checkbox"] {
    box-sizing: border-box;
    padding: 0;
  }
  .blog * {
    box-sizing: border-box;
  }
  .blog input {
    font-family: inherit;
    font-size: inherit;
    line-height: inherit;
  }
  .blog a {
    color: #0366d6;
    text-decoration: none;
  }
  .blog a:hover {
    text-decoration: underline;
  }
  .blog strong {
    font-weight: 600;
  }
  .blog hr {
    height: 0;
    margin: 15px 0;
    overflow: hidden;
    background: transparent;
    border: 0;
    border-bottom: 1px solid #dfe2e5;
  }
  .blog hr:after,
  .blog hr:before {
    display: table;
    content: "";
  }
  .blog hr:after {
    clear: both;
  }
  .blog table {
    border-spacing: 0;
    border-collapse: collapse;
  }
  .blog td,
  .blog th {
    padding: 0;
  }
  .blog details summary {
    cursor: pointer;
  }
  .blog kbd {
    display: inline-block;
    padding: 3px 5px;
    font: 11px SFMono-Regular, Consolas, Liberation Mono, Menlo, monospace;
    line-height: 10px;
    color: #444d56;
    vertical-align: middle;
    background-color: #fafbfc;
    border: 1px solid #d1d5da;
    border-radius: 3px;
    box-shadow: inset 0 -1px 0 #d1d5da;
  }
  .blog h1,
  .blog h2,
  .blog h3,
  .blog h4,
  .blog h5,
  .blog h6 {
    margin-top: 0;
    margin-bottom: 0;
  }
  .blog h1 {
    font-size: 32px;
  }
  .blog h1,
  .blog h2 {
    font-weight: 600;
  }
  .blog h2 {
    font-size: 24px;
  }
  .blog h3 {
    font-size: 20px;
  }
  .blog h3,
  .blog h4 {
    font-weight: 600;
  }
  .blog h4 {
    font-size: 16px;
  }
  .blog h5 {
    font-size: 14px;
  }
  .blog h5,
  .blog h6 {
    font-weight: 600;
  }
  .blog h6 {
    font-size: 12px;
  }
  .blog p {
    margin-top: 0;
    margin-bottom: 10px;
  }
  .blog blockquote {
    margin: 0;
  }
  .blog ol,
  .blog ul {
    padding-left: 0;
    margin-top: 0;
    margin-bottom: 0;
  }
  .blog ol ol,
  .blog ul ol {
    list-style-type: lower-roman;
  }
  .blog ol ol ol,
  .blog ol ul ol,
  .blog ul ol ol,
  .blog ul ul ol {
    list-style-type: lower-alpha;
  }
  .blog dd {
    margin-left: 0;
  }
  .blog code,
  .blog pre {
    font-family: SFMono-Regular, Consolas, Liberation Mono, Menlo, monospace;
    font-size: 12px;
  }
  .blog pre {
    margin-top: 0;
    margin-bottom: 0;
  }
  .blog input::-webkit-inner-spin-button,
  .blog input::-webkit-outer-spin-button {
    margin: 0;
    -webkit-appearance: none;
    appearance: none;
  }
  .blog :checked + .radio-label {
    position: relative;
    z-index: 1;
    border-color: #0366d6;
  }
  .blog .border {
    border: 1px solid #e1e4e8 !important;
  }
  .blog .border-0 {
    border: 0 !important;
  }
  .blog .border-bottom {
    border-bottom: 1px solid #e1e4e8 !important;
  }
  .blog .rounded-1 {
    border-radius: 3px !important;
  }
  .blog .bg-white {
    background-color: #fff !important;
  }
  .blog .bg-gray-light {
    background-color: #fafbfc !important;
  }
  .blog .text-gray-light {
    color: #6a737d !important;
  }
  .blog .mb-0 {
    margin-bottom: 0 !important;
  }
  .blog .my-2 {
    margin-top: 8px !important;
    margin-bottom: 8px !important;
  }
  .blog .pl-0 {
    padding-left: 0 !important;
  }
  .blog .py-0 {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
  }
  .blog .pl-1 {
    padding-left: 4px !important;
  }
  .blog .pl-2 {
    padding-left: 8px !important;
  }
  .blog .py-2 {
    padding-top: 8px !important;
    padding-bottom: 8px !important;
  }
  .blog .pl-3,
  .blog .px-3 {
    padding-left: 16px !important;
  }
  .blog .px-3 {
    padding-right: 16px !important;
  }
  .blog .pl-4 {
    padding-left: 24px !important;
  }
  .blog .pl-5 {
    padding-left: 32px !important;
  }
  .blog .pl-6 {
    padding-left: 40px !important;
  }
  .blog .f6 {
    font-size: 12px !important;
  }
  .blog .lh-condensed {
    line-height: 1.25 !important;
  }
  .blog .text-bold {
    font-weight: 600 !important;
  }
  .blog .pl-c {
    color: #6a737d;
  }
  .blog .pl-c1,
  .blog .pl-s .pl-v {
    color: #005cc5;
  }
  .blog .pl-e,
  .blog .pl-en {
    color: #6f42c1;
  }
  .blog .pl-s .pl-s1,
  .blog .pl-smi {
    color: #24292e;
  }
  .blog .pl-ent {
    color: #22863a;
  }
  .blog .pl-k {
    color: #d73a49;
  }
  .blog .pl-pds,
  .blog .pl-s,
  .blog .pl-s .pl-pse .pl-s1,
  .blog .pl-sr,
  .blog .pl-sr .pl-cce,
  .blog .pl-sr .pl-sra,
  .blog .pl-sr .pl-sre {
    color: #032f62;
  }
  .blog .pl-smw,
  .blog .pl-v {
    color: #e36209;
  }
  .blog .pl-bu {
    color: #b31d28;
  }
  .blog .pl-ii {
    color: #fafbfc;
    background-color: #b31d28;
  }
  .blog .pl-c2 {
    color: #fafbfc;
    background-color: #d73a49;
  }
  .blog .pl-c2:before {
    content: "^M";
  }
  .blog .pl-sr .pl-cce {
    font-weight: 700;
    color: #22863a;
  }
  .blog .pl-ml {
    color: #735c0f;
  }
  .blog .pl-mh,
  .blog .pl-mh .pl-en,
  .blog .pl-ms {
    font-weight: 700;
    color: #005cc5;
  }
  .blog .pl-mi {
    font-style: italic;
    color: #24292e;
  }
  .blog .pl-mb {
    font-weight: 700;
    color: #24292e;
  }
  .blog .pl-md {
    color: #b31d28;
    background-color: #ffeef0;
  }
  .blog .pl-mi1 {
    color: #22863a;
    background-color: #f0fff4;
  }
  .blog .pl-mc {
    color: #e36209;
    background-color: #ffebda;
  }
  .blog .pl-mi2 {
    color: #f6f8fa;
    background-color: #005cc5;
  }
  .blog .pl-mdr {
    font-weight: 700;
    color: #6f42c1;
  }
  .blog .pl-ba {
    color: #586069;
  }
  .blog .pl-sg {
    color: #959da5;
  }
  .blog .pl-corl {
    text-decoration: underline;
    color: #032f62;
  }
  .blog .mb-0 {
    margin-bottom: 0 !important;
  }
  .blog .my-2 {
    margin-bottom: 8px !important;
  }
  .blog .my-2 {
    margin-top: 8px !important;
  }
  .blog .pl-0 {
    padding-left: 0 !important;
  }
  .blog .py-0 {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
  }
  .blog .pl-1 {
    padding-left: 4px !important;
  }
  .blog .pl-2 {
    padding-left: 8px !important;
  }
  .blog .py-2 {
    padding-top: 8px !important;
    padding-bottom: 8px !important;
  }
  .blog .pl-3 {
    padding-left: 16px !important;
  }
  .blog .pl-4 {
    padding-left: 24px !important;
  }
  .blog .pl-5 {
    padding-left: 32px !important;
  }
  .blog .pl-6 {
    padding-left: 40px !important;
  }
  .blog .pl-7 {
    padding-left: 48px !important;
  }
  .blog .pl-8 {
    padding-left: 64px !important;
  }
  .blog .pl-9 {
    padding-left: 80px !important;
  }
  .blog .pl-10 {
    padding-left: 96px !important;
  }
  .blog .pl-11 {
    padding-left: 112px !important;
  }
  .blog .pl-12 {
    padding-left: 128px !important;
  }
  .blog kbd {
    display: inline-block;
    padding: 3px 5px;
    font: 11px SFMono-Regular, Consolas, Liberation Mono, Menlo, monospace;
    line-height: 10px;
    color: #444d56;
    vertical-align: middle;
    background-color: #fafbfc;
    border: 1px solid #d1d5da;
    border-radius: 3px;
    box-shadow: inset 0 -1px 0 #d1d5da;
  }
  .blog:after,
  .blog:before {
    display: table;
    content: "";
  }
  .blog:after {
    clear: both;
  }
  .blog > :first-child {
    margin-top: 0 !important;
  }
  .blog > :last-child {
    margin-bottom: 0 !important;
  }
  .blog a:not([href]) {
    color: inherit;
    text-decoration: none;
  }
  .blog blockquote,
  .blog details,
  .blog dl,
  .blog ol,
  .blog p,
  .blog pre,
  .blog table,
  .blog ul {
    margin-top: 0;
    margin-bottom: 16px;
  }
  .blog hr {
    height: 0;
    padding: 0;
    margin: 2rem 0 8rem 0;
    border: dashed 0.1rem #999;
  }
  .blog blockquote {
    padding: 0 1em;
    color: #6a737d;
    border-left: 0.25em solid #dfe2e5;
  }
  .blog blockquote > :first-child {
    margin-top: 0;
  }
  .blog blockquote > :last-child {
    margin-bottom: 0;
  }
  .blog h1,
  .blog h2,
  .blog h3,
  .blog h4,
  .blog h5,
  .blog h6 {
    margin-top: 24px;
    margin-bottom: 16px;
    font-weight: 600;
    line-height: 1.25;
  }
  .blog h1 {
    font-size: 2em;
  }
  .blog h1,
  .blog h2 {
    padding-bottom: 0.3em;
    border-bottom: 1px solid #eaecef;
  }
  .blog h2 {
    font-size: 1.5em;
  }
  .blog h3 {
    font-size: 1.25em;
  }
  .blog h4 {
    font-size: 1em;
  }
  .blog h5 {
    font-size: 0.875em;
  }
  .blog h6 {
    font-size: 0.85em;
    color: #6a737d;
  }
  .blog ol,
  .blog ul {
    padding-left: 2em;
  }
  .blog ol ol,
  .blog ol ul,
  .blog ul ol,
  .blog ul ul {
    margin-top: 0;
    margin-bottom: 0;
  }
  .blog li {
    word-wrap: break-all;
  }
  .blog li > p {
    margin-top: 16px;
  }
  .blog li + li {
    margin-top: 0.25em;
  }
  .blog dl {
    padding: 0;
  }
  .blog dl dt {
    padding: 0;
    margin-top: 16px;
    font-size: 1em;
    font-style: italic;
    font-weight: 600;
  }
  .blog dl dd {
    padding: 0 16px;
    margin-bottom: 16px;
  }
  .blog table {
    display: block;
    width: 100%;
    overflow: auto;
  }
  .blog table th {
    font-weight: 600;
  }
  .blog table td,
  .blog table th {
    padding: 6px 13px;
    border: 1px solid #dfe2e5;
  }
  .blog table tr {
    background-color: #fff;
    border-top: 1px solid #c6cbd1;
  }
  .blog table tr:nth-child(2n) {
    background-color: #f6f8fa;
  }
  .blog img {
    max-width: 100%;
    box-sizing: initial;
    background-color: #fff;
  }
  .blog img[align="right"] {
    padding-left: 20px;
  }
  .blog img[align="left"] {
    padding-right: 20px;
  }
  .blog code {
    padding: 0.2em 0.4em;
    margin: 0;
    font-size: 85%;
    background-color: rgba(27, 31, 35, 0.05);
    border-radius: 3px;
  }
  .blog pre {
    word-wrap: normal;
  }
  .blog pre > code {
    padding: 0;
    margin: 0;
    font-size: 100%;
    word-break: normal;
    white-space: pre;
    background: transparent;
    border: 0;
  }
  .blog .highlight {
    margin-bottom: 16px;
  }
  .blog .highlight pre {
    margin-bottom: 0;
    word-break: normal;
  }
  .blog .highlight pre,
  .blog pre {
    padding: 16px;
    overflow: auto;
    font-size: 85%;
    line-height: 1.45;
    background-color: #f6f8fa;
    border-radius: 3px;
  }
  .blog pre code {
    display: inline;
    max-width: auto;
    padding: 0;
    margin: 0;
    overflow: visible;
    line-height: inherit;
    word-wrap: normal;
    background-color: initial;
    border: 0;
  }
  .blog .commit-tease-sha {
    display: inline-block;
    font-family: SFMono-Regular, Consolas, Liberation Mono, Menlo, monospace;
    font-size: 90%;
    color: #444d56;
  }

  .blog .full-commit .btn-outline:not(:disabled):hover {
    color: #005cc5;
    border-color: #005cc5;
  }
  .blog .blob-wrapper {
    overflow-x: auto;
    overflow-y: hidden;
  }
  .blog .blob-wrapper-embedded {
    max-height: 240px;
    overflow-y: auto;
  }
  .blog .blob-num {
    width: 1%;
    min-width: 50px;
    padding-right: 10px;
    padding-left: 10px;
    font-family: SFMono-Regular, Consolas, Liberation Mono, Menlo, monospace;
    font-size: 12px;
    line-height: 20px;
    color: rgba(27, 31, 35, 0.3);
    text-align: right;
    white-space: nowrap;
    vertical-align: top;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
  }
  .blog .blob-num:hover {
    color: rgba(27, 31, 35, 0.6);
  }
  .blog .blob-num:before {
    content: attr(data-line-number);
  }
  .blog .blob-code {
    position: relative;
    padding-right: 10px;
    padding-left: 10px;
    line-height: 20px;
    vertical-align: top;
  }
  .blog .blob-code-inner {
    overflow: visible;
    font-family: SFMono-Regular, Consolas, Liberation Mono, Menlo, monospace;
    font-size: 12px;
    color: #24292e;
    word-wrap: normal;
    white-space: pre;
  }
  .blog .pl-token.active,
  .blog .pl-token:hover {
    cursor: pointer;
    background: #ffea7f;
  }
  .blog .tab-size[data-tab-size="1"] {
    -moz-tab-size: 1;
    tab-size: 1;
  }
  .blog .tab-size[data-tab-size="2"] {
    -moz-tab-size: 2;
    tab-size: 2;
  }
  .blog .tab-size[data-tab-size="3"] {
    -moz-tab-size: 3;
    tab-size: 3;
  }
  .blog .tab-size[data-tab-size="4"] {
    -moz-tab-size: 4;
    tab-size: 4;
  }
  .blog .tab-size[data-tab-size="5"] {
    -moz-tab-size: 5;
    tab-size: 5;
  }
  .blog .tab-size[data-tab-size="6"] {
    -moz-tab-size: 6;
    tab-size: 6;
  }
  .blog .tab-size[data-tab-size="7"] {
    -moz-tab-size: 7;
    tab-size: 7;
  }
  .blog .tab-size[data-tab-size="8"] {
    -moz-tab-size: 8;
    tab-size: 8;
  }
  .blog .tab-size[data-tab-size="9"] {
    -moz-tab-size: 9;
    tab-size: 9;
  }
  .blog .tab-size[data-tab-size="10"] {
    -moz-tab-size: 10;
    tab-size: 10;
  }
  .blog .tab-size[data-tab-size="11"] {
    -moz-tab-size: 11;
    tab-size: 11;
  }
  .blog .tab-size[data-tab-size="12"] {
    -moz-tab-size: 12;
    tab-size: 12;
  }
  .blog .task-list-item {
    list-style-type: none;
  }
  .blog .task-list-item + .task-list-item {
    margin-top: 3px;
  }
  .blog .task-list-item input {
    margin: 0 0.2em 0.25em -1.6em;
    vertical-align: middle;
  }
  
  /* over white */
  .blog div#share {
    margin: 0;
  }
  .blog img {
    width: 100%;
    max-height: none;
    border-radius: 0;
  }
  .blog li {
    display: list-item;
    list-style: circle;
  }
  .blog li + li {
    margin-top: 0;
  }
  .blog li:nth-child(2){
    color: inherit;
  }
</style>