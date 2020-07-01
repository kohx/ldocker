<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

今回は前回までに作成したものを、メール認証に対応するように変更します。  
最善の方法がわからないので自分なりに作っていきます。  
もっと良い方法を知ってる方はコメントで教えてくれたら嬉しいです！  

前回の記事は[Laravel mix vue No.4 - Vuex](https://www.aska-ltd.jp/jp/blog/66)

# メール認証に変更

<!-- ## サンプル
- このセクションを始める前  
[github ldocker 05](https://github.com/kohx/ldocker/tree/05)  
  
- 完成  
[github ldocker 06](https://github.com/kohx/ldocker/tree/06) -->

---

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

---

## dockerスタートとnpmモジュール追加

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

---

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

---

## 仮登録を管理するテーブルを作成

### マイグレーションファイルを作る

laravelのartisanでマイグレーションファイルを作成
テーブル名は`register_users`とする

```bash

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

```bash

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

---

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
    use App\User;

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

```bash

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

メッセージを変更

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

---

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
use Illuminate\Support\Facades\Lang;
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
 
[Laravel mix vue No.6 - パスワードリセット](https://www.aska-ltd.jp/jp/blog/70)

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