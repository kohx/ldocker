<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

今回は前回までに作成したものを、メール認証に対応するように変更します。  
最善の方法がわからないので自分なりに作っていきます。  
もっと良い方法を知ってる方はコメントで教えてくれたら嬉しいです！  

> 連載記事

<ul class="blog-index-list">
  <li><a href="https://www.aska-ltd.jp/jp/blog/52">Laravel mix vue No.1 - Docker Environment - Dockerでlaravel環境 (laradockを使わない)</li></a>
  <li><a href="https://www.aska-ltd.jp/jp/blog/56">Laravel mix vue No.2 - Vue Router, Component - Vueルータの設定とコンポネント作成</li></a>
  <li><a href="https://www.aska-ltd.jp/jp/blog/65">Laravel mix vue No.3 - Authentication API - Apiで認証</li></a>
  <li><a href="https://www.aska-ltd.jp/jp/blog/66">Laravel mix vue No.4 - Vuex - Vuexで状態管理</li></a>
  <li><a href="https://www.aska-ltd.jp/jp/blog/69">Laravel mix vue No.5 - Api Email Verification - メール認証に変更</li></a>
  <li><a href="https://www.aska-ltd.jp/jp/blog/70">Laravel mix vue No.6 - Api Resetting Passwords - パスワードリセット</li></a>
  <!-- <li><a href="https://www.aska-ltd.jp/jp/blog/72">Laravel mix vue No.7 - Socialite - ソーシャルログイン</li></a> -->
  <!-- <li><a href="https://www.aska-ltd.jp/jp/blog/73">Laravel mix vue No.8 - Laravel Internationalization - Laravel多言語化</li></a> -->
  <!-- <li><a href="https://www.aska-ltd.jp/jp/blog/76">Laravel mix vue No.9 - Vue Internationalization - Vue多言語化</li></a> -->
  <!-- <li><a href="https://www.aska-ltd.jp/jp/blog/76">Laravel mix vue No.10 - Vue Font Awesome - フォントアイコン追加</a></li> -->
</ul>

# Api Email Verification - メール認証に変更

## サンプル
- このセクションを始める前  
[github ldocker 05](https://github.com/kohx/ldocker/tree/05)  
  
- 完成  
[github ldocker 06](https://github.com/kohx/ldocker/tree/06)

------------------------------------------------------------------------------------------

## フォルダ構成

```text

└─ server
   ├─ app
   |  ├─ Http
   |  |  ├─ Controllers
   |  |  |  └─ Auth
   |  |  |     ├─ RegisterController.php
   |  |  |     └─ VerificationController.php
   |  |  └─ Middleware
   |  |     ├─ EncryptCookies.php
   |  |     └─ RedirectIfAuthenticated.php
+  |  ├─ Mail
+  |  |  └─ VerificationMail.php
   |  └─ Models
+  |     └─ RegisterUser.php
   ├─ database
   |  └─ migrations
   |     └─ 20xx_xx_xx_xxxxxx_create_register_user_table.php
   ├─ resources
   |  ├─ views
+  |  |  └─ mails
+  |  |     └─ verification_mail.blade.php
   |  └─ js
   |     └─ pages
   |        └─ Login.vue
   ├─ routes
   |  ├─ api.php
   |  └─ web.php
   └─ .env

```

------------------------------------------------------------------------------------------

## dockerスタートとnpmモジュール追加

gitからクローンした場合は`.env`の作成と設定を忘れないように！

```bash:terminal

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

## テストメールの設定

[https://mailtrap.io](https://mailtrap.io)をメールのテスト用に使う。

サインアップを行って 「HOME -> Demo inbox -> SMTP Setting タブ」 へ進んでいくと設定情報が表示される

### .envにSMTPを設定

```text:server\.env

    MAIL_MAILER=smtp
    MAIL_HOST=smtp.mailtrap.io
    MAIL_PORT=2525
-   MAIL_USERNAME=null
+   MAIL_USERNAME=xxxxxxxxxxxxxx
-   MAIL_PASSWORD=null
+   MAIL_PASSWORD=xxxxxxxxxxxxxx
    MAIL_ENCRYPTION=null
-   MAIL_FROM_ADDRESS=null
+   MAIL_FROM_ADDRESS=xxx@xxx.xxx
    MAIL_FROM_NAME="${APP_NAME}"

```

------------------------------------------------------------------------------------------

## 仮登録を管理するテーブルを作成

### マイグレーションファイルを作る

laravelのartisanでマイグレーションファイルを作成
テーブル名は`register_users`とする

```bash:terminal

# コンテナに入ってない場合は以下でコンテナに入る
docker-compose exec php bash

#php artisan make:migration {テーブル名}_table
php artisan make:migration register_users_table

```

`server\database\migrations\20xx_xx_xx_xxxxxx_register_users_table.php`という感じにファイルが作成されるのでこれを編集

```php:server\database\migrations\20xx_xx_xx_xxxxxx_register_users_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RegisterUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
+       Schema::create('register_users', function (Blueprint $table) {
+           $table->string('email')->index();
+           $table->string('token');
+           $table->string('name');
+           $table->string('password');
+           $table->timestamp('created_at')->nullable();
+       });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
+       Schema::dropIfExists('register_users');
    }
}

```

### RegisterUserモデルを作る

テーブル名は`register_users`なのでクラス名は`RegisterUser`として`Models`フォルダに作成

```bash:terminal

php artisan make:model Models/RegisterUser

```

`server\app\Models\RegisterUser.php`が作られるので以下のように編集

```php:server\app\Models\RegisterUser.php

    <?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    class RegisterUser extends Model
    {
        // テーブル名を指定
+       protected $table = 'register_users';
    
        // プライマリキーを「email」に変更
        // デフォルトは「id」
+       protected $primaryKey = 'email';
        // プライマリキーのタイプを指定
+       protected $keyType = 'string';
        // タイプがストリングの場合はインクリメントを「false」にしないといけない
+       public $incrementing = false;
    
        // モデルが以下のフィールド以外を持たないようにする
+       protected $fillable = [
+           'email',
+           'name',
+       ];
    
        // タイムスタンプは「created_at」のフィールドだけにしたいので、「false」を指定
+       public $timestamps = false;
        // 自前で用意する
+       public static function boot()
+       {
            parent::boot();
    
+           static::creating(function ($model) {
+               $model->created_at = $model->freshTimestamp();
+           });
+       }
    }

```

------------------------------------------------------------------------------------------

## 仮登録をして、認証メールを送る

### RegisterControllerの修正

前回までに作成した`server\app\Http\Controllers\Auth\RegisterController.php`を仮登録用にまるっと修正

```php:server\app\Http\Controllers\Auth\RegisterController.php

    <?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Validator;
    use Illuminate\Support\Facades\Mail;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Str;

    // 作成したものを使う
    use App\Models\RegisterUser;
    use App\Mail\VerificationMail;

    class RegisterController extends Controller
    {
        /**
         * Send Register Link Email
         * 送られてきた内容をテーブルに保存して認証メールを送信
         *
         * @param Request $request
         * @return RegisterUser
         */
        public function sendMail(Request $request)
        {
            // validation
            // 送られてきた内容のバリデーション
            $this->validator($request->all())->validate();

            // create token
            // トークンを作成
            $token = $this->createToken();

            // delete old data
            // 同じメールアドレスが残っていればテーブルから削除
            RegisterUser::destroy($request->email);

            // insert
            // 送られてきた内容をテーブルに保存
            $passwordReset = new RegisterUser($request->all());
            $passwordReset->token = $token;
            // パスワードはハッシュ
            $passwordReset->password = Hash::make($request->password);
            $passwordReset->save();

            // send email
            // メールクラスでメールを送信
            $this->sendVerificationMail($passwordReset->email, $token);

            // モデルを返す
            return $passwordReset;
        }

        /**
         * validator
         * バリデーション
         *
         * @param  array  $data
         * @return \Illuminate\Contracts\Validation\Validator
         */
        protected function validator(array $data)
        {
            return Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
        }

        /**
         * create activation token
         * トークンを作成する
         * @return string
         */
        private function createToken()
        {
            return hash_hmac('sha256', Str::random(40), config('app.key'));
        }

        /**
         * send verification mail
         * メールクラスでメールを送信
         * 
         * @param string $email
         * @param string $token
         * @return void
         */
        private function sendVerificationMail($email, $token)
        {
            Mail::to($email)
                ->send(new VerificationMail($token));
        }
    }

```

### 仮登録で送信するメールクラスを作成

laravelのartisanでクラス名を`VerificationMail`としてメールクラスを作成

```bash:terminal

php artisan make:mail VerificationMail

```

`server\app\Mail\VerificationMail.php`が作成されるので編集

```php:server\app\Mail\VerificationMail.php

...

    class VerificationMail extends Mailable
    {
        use Queueable, SerializesModels;

        protected $token;

        /**
         * Create a new message instance.
         *
         * @return void
         */
        public function __construct($token)
        {
            // 引数でトークンを受け取る
            $this->token = $token;
        }

        /**
         * Build the message.
         *
         * @return $this
         */
        public function build()
        {
           // 件名
           $subject = 'Verification mail';

            // コールバックURLをルート名で取得
            //TODO: これだとホットリロードでホストがおかしくなる
            // $url = route('verification', ['token' => $this->token]);

            //TODO: とりあえずこれで対応
            // .envの「APP_URL」に設定したurlを取得
            $baseUrl = config('app.url');
            $token = $this->token;
            $url = "{$baseUrl}/verification/{$token}";

           // 送信元のアドレス
           // .envの「MAIL_FROM_ADDRESS」に設定したアドレスを取得
           $from = config('mail.from.address');

            // メール送信
            return $this
              ->from($from)
              ->subject($subject)
              // 送信メールのビュー
              ->view('mails.verification_mail')
              // ビューで使う変数を渡す
              ->with('url', $url);
        }
    }

```

### 仮登録で使用するメールテンプレートの作成

#### レイアウトテンプレート
`server\resources\views\layouts\mail.blade.php`でレイアウトテンプレートを作成

```php:server\resources\views\layouts\mail.blade.php

<html>
    <head>
        <title>@yield('title')</title>
    </head>
    <body>
        @yield('content')
    </body>
</html>


```

#### 認証メールテンプレート

その中に入れる`server\resources\views\mails\verification_mail.blade.php`を作成

```php:server\resources\views\mails\verification_mail.blade.php

    @extends('layouts.mail')

    @section('title', '登録認証')

    @section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">登録認証</div>

                    <div class="card-body">
                        <a href='{{$url}}'>こちらのリンク</a>をクリックして、メールを認証してください。
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection

```

### 仮登録で使用するルートの修正

`server\routes\api.php`を修正する

```php:server\routes\api.php

    <?php

    // register
-   Route::post('/register', 'Auth\RegisterController@register')->name('register');
+   Route::post('/register', 'Auth\RegisterController@sendMail')->name('register');

...

```

### 送信後の処理をVueに入れる

`server\resources\js\pages\Login.vue`のメッセージを変更

```javascript:server\resources\js\pages\Login.vue

...
    /*
     * register
     */
    async register() {
        // authストアのregisterアクションを呼び出す
        await this.$store.dispatch("auth/register", this.registerForm);
        // 通信成功
        if (this.apiStatus) {
            // メッセージストアで表示
            this.$store.commit("message/setContent", {
-               content: "登録しました。",
+               content: "認証メールを送りました。",
                timeout: 10000
            });
            // AUTHストアのエラーメッセージをクリア
            this.clearError();
            // フォームをクリア
            this.clearForm();
        }
    },
...

```

### ストアの修正

AUTHストアのregistereアクションでuserステートをろぐいんじょうたいにしているので、仮認証の時点では実行しないようにする

`server\resources\js\store\auth.js`を修正する

```javascript:server\resources\js\store\auth.js

    ...
    /*
     * registerのアクション
     */
    async register(context, data) {
        // apiStatusのクリア
        context.commit("setApiStatus", null);

        // Apiリクエスト
        const response = await axios.post("/api/register", data);

        // 通信成功の場合 201
        if (response.status === CREATED) {
            // apiStatus を true に更新
            context.commit("setApiStatus", true);
            // user にデータを登録 -> 仮認証の時点では実行しないようにする
-           context.commit("setUser", response.data);
            // ここで終了
            return false;
        }
    ...

```

------------------------------------------------------------------------------------------

## 認証メールからのコールバックを処理して認証を完了する

### Userモデルの修正

認証時に`email_verified_at`フィールドもいれたいので`server\app\User.php`を修正

```php:server\app\User.php

    ...
    protected $fillable = [
-       'name', 'email', 'password',
+       'name', 'email', 'password', 'email_verified_at'
    ];
    ...
```

### VerificationControllerの修正

`server\app\Http\Controllers\Auth\VerificationController.php`を認証完了用にまるっと修正

```php:server\app\Http\Controllers\Auth\VerificationController.php

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use App\Models\RegisterUser;

class VerificationController extends Controller
{
    // vueでアクセスするホームへのルート
    protected $vueRouteHome = '';
    // vueでアクセスするログインへのルート
    protected $vueRouteLogin = 'login';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // guestミドルウェアはRedirectIfAuthenticatedクラスを指定しているので
        // 認証済み（ログイン済み）の状態でログインページにアクセスすると、ログイン後のトップページにリダイレクトする
        $this->middleware('guest');
    }

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
            return $this->redirectWithMessage($this->vueRouteLogin, $message);
        }

        // 仮登録のデータでユーザを作成
        event(new Registered($user = $this->createUser($registerUser->toArray())));

        // 作成したユーザをログインさせる
        Auth::login($user, true);

        // 成功メッセージ
        $message = __('completion of registration.');

        // メッセージをつけてリダイレクト
        return $this->redirectWithMessage($this->vueRouteHome, $message);
    }

    /**
     * get register user and clean table
     * トークンで仮登録ユーザデータを取得して仮登録データをテーブルから削除する
     *
     * @param string $activationCode
     * @return RegisterUser|null
     */
    private function getRegisterUser($token)
    {
        // トークンで仮登録ユーザデータを取得
        $registerUser = RegisterUser::where('token', $token)->first();

        // 取得できた場合は仮登録データを削除
        if ($registerUser) {

            RegisterUser::destroy($registerUser->email);
        }

        // モデルを返す
        return $registerUser;
    }

    /**
     * Create a new user instance after a valid registration.
     * ユーザインスタンスを作成
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function createUser(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'email_verified_at' => now(),
            'password' => $data['password'],
        ]);
    }

    /**
     * redirect with message
     * メッセージをクッキーに付けてリダイレクト
     *
     * @param  string  $vueRoute
     * @param  string  $message
     * @return Redirect
     */
    protected function redirectWithMessage($vueRoute, $message)
    {
        // vueでアクセスするルートを作る
        $route = url($vueRoute);
        
        return redirect($route)
          // PHPネイティブのsetcookieメソッドに指定する引数同じ
          // ->cookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly)
          ->cookie('MESSAGE', $message, 0, '', '', false, false);
    }
}

```

### 認証を完了で使用するルートの追加

認証メールからのコールバックを受けるルートを追加する

```php:server\routes\web.php

    <?php

    use Illuminate\Support\Facades\Route;

    // verification callback
+   Route::get('/verification/{token}', 'Auth\VerificationController@register')
+     ->name('verification');

    // API以外はindexを返すようにして、VueRouterで制御
    Route::get('/{any?}', fn () => view('index'))->where('any', '.+');

```

### cookieで追加するメッセージを暗号化しないようにする

cookieは暗号化されているので「MESSAGE」を暗号化しないように`server\app\Http\Middleware\EncryptCookies.php`を設定

```php:server\app\Http\Middleware\EncryptCookies.php

...
    protected $except = [
+       'MESSAGE'
    ];
...

```

### cookieで送ったメッセージをMESSAGEストアで表示できるようにする

`server\resources\js\App.vue`を編集

```javascript:server\resources\js\App.vue

    ...

    // js-cookieをインポート
+   import Cookies from "js-cookie";

    export default {
        // 使用するコンポーネントを宣言
        components: {
            ...
        },
        computed: {
            ...
        },
        watch: {
            ....
        },
+       created() {
+           // cookiesにMESSAGEがある場合
+           const message = Cookies.get("MESSAGE");
+           if (message) {
                // cookieをクリア
+               Cookies.remove("MESSAGE");
                // MESSAGEストアでメッセージを表示
+               this.$store.commit("message/setContent", {
+                   content: message,
+                   timeout: 6000
+               });
+          }
+       }
    };
    </script>

```

### guestミドルウェアで追加されていRedirectIfAuthenticatedクラスを修正

認証済み（ログイン済み）の状態でログインページにアクセスすると、ログイン後のトップページにリダイレクトする場合にユーザ情報を返しているので、非同期通信でない場合はホームへリダイレクトするように`server\app\Http\Middleware\RedirectIfAuthenticated.php`を修正する

```php:server\app\Http\Middleware\RedirectIfAuthenticated.php

...
      public function handle($request, Closure $next, $guard = null)
      {
          // 認証済み（ログイン済み）の状態でログインページにアクセスした場合
          if (Auth::guard($guard)->check()) {

            // 非同期通信の場合
+           if ($request->ajax()) {
              // リダイレクトしてユーザ情報をを返す
              return redirect()->route('user');
+           }
            // 同期通信の場合
+           else {
              // ルートにリダイレクト
+             return redirect('/');
+           }
          }

          return $next($request);
      }
...

```

------------------------------------------------------------------------------------------

## 仮登録と認証メールで登録完了をテスト

1. 仮登録  

![キャプチャ1](http://www.aska-ltd.jp/uploads/blogs/2006170254キャプチャ1.PNG)  

データベース  
![キャプチャ2](http://www.aska-ltd.jp/uploads/blogs/2006170202キャプチャ2.PNG)  

2. メールの確認

![キャプチャ3](http://www.aska-ltd.jp/uploads/blogs/2006170208キャプチャ3.PNG)  

3. 移動後

![キャプチャ4](http://www.aska-ltd.jp/uploads/blogs/2006170215キャプチャ4.PNG)  

データベース  
![キャプチャ5](http://www.aska-ltd.jp/uploads/blogs/2006170222キャプチャ5.PNG)  


次は残っているパスワード変更の部分を実装します。  
 
> [Laravel mix vue No.6 - Api Resetting Passwords - パスワードリセット](https://www.aska-ltd.jp/jp/blog/70)

<script src="https://www.aska-ltd.jp/js/blog.js"></script>
<link href="https://www.aska-ltd.jp/css/blog.css" rel="stylesheet">
