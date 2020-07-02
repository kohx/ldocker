<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

今回はパスワードを忘れた場合にメールでパスワードリセットできるようにします。  
こちらも最善の方法がわからないので自分なりに作っていきます。  
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

# Api Resetting Passwords - パスワードリセット

パスワードを忘れた場合にメールでパスワードリセット

## サンプル
- このセクションを始める前  
[github ldocker 06](https://github.com/kohx/ldocker/tree/06)  
  
- 完成  
[github ldocker 07](https://github.com/kohx/ldocker/tree/07)

---

## フォルダ構成

```text

└─ server
   ├─ app
   |  ├─ Http
   |  |  └─ Controllers
   |  |     └─ Auth
   |  |        ├─ ForgotPasswordController.php
   |  |        └─ ResetPasswordController.php
   |  ├─ Mail
+  |  |  └─ ResetPasswordMail.php
   |  └─ Models
+  |     └─ ResetPassword.php
   ├─ resources
   |  ├─ views
+  |  |  └─ mails
+  |  |     └─ reset_password_mail.blade.php
   |  └─ js
   |     └─ pages
   |        ├─ Login.vue
+  |        └─ Reset.vue
   └─ routes
      ├─ api.php
      └─ web.php

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


## ResetPasswordモデル

laravelのartisanでmodelを作成

テーブル名は`Reset_password`なのでクラス名は`ResetPassword`として`Models`フォルダに作成

```bash:terminal

php artisan make:model Models/ResetPassword

```

`server\app\Models\ResetPassword.php`が作られるので以下のように編集

```php:server\app\Models\ResetPassword.php

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResetPassword extends Model
{
    // テーブル名を指定
+   protected $table = 'password_resets';

    // プライマリキーを「email」に変更
    // デフォルトは「id」
+   protected $primaryKey = 'email';
    // プライマリキーのタイプを指定
+   protected $keyType = 'string';
    // タイプがストリングの場合はインクリメントを「false」にしないといけない
+   public $incrementing = false;

    // モデルが以下のフィールド以外を持たないようにする
+   protected $fillable = [
+       'email',
+       'token',
+   ];

    // タイムスタンプは「created_at」のフィールドだけにしたいので、「false」を指定
+   public $timestamps = false;
    // 自前で用意する
+   public static function boot()
+   {
+       parent::boot();
+       static::creating(function ($model) {
+           $model->created_at = $model->freshTimestamp();
+       });
+   }
}

```

------------------------------------------------------------------------------------------

## パスワードを忘れた場合の処理をしてパスワード変更メールを送る

### ForgotPasswordControllerの修正

パスワードを忘れた場合用にまるっと修正
`server\app\Http\Controllers\Auth\ForgotPasswordController.php`

```php:server\app\Http\Controllers\Auth\ForgotPasswordController.php

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\ResetPassword;
use App\Mail\ResetPasswordMail;

class ForgotPasswordController extends Controller
{
    /**
     * send mail
     * 送られてきた内容をテーブルに保存してパスワード変更メールを送信
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return ResetPassword
     */
    public function forgot(Request $request)
    {
        // validation
        // 送られてきた内容のバリデーション
        $this->validator($request->all())->validate();

        // create token
        // トークンを作成
        $token = $this->createToken();

        // delete old data
        // 古いデータが有れば削除
        ResetPassword::destroy($request->email);

        // insert
        // 送られてきた内容をテーブルに保存
        $resetPassword = new ResetPassword($request->all());
        $resetPassword->token = $token;
        $resetPassword->save();

        // send email
        // メールクラスでメールを送信
        $this->sendResetPasswordMail($resetPassword->email, $token);

        return $resetPassword;
    }

    /**
     * Get a validator
     * バリデーション
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'email' => [
                'required',
                'email',
                'exists:users,email',
            ],
        ]);
    }

    /**
     * create token
     * トークンを作成する
     * @return stirng
     */
    private function createToken()
    {
        return hash_hmac('sha256', Str::random(40), config('app.key'));
    }

    /**
     * send reset password mail
     * メールクラスでメールを送信
     * 
     * @param string $email
     * @param string $token
     * @return void
     */
    private function sendResetPasswordMail($email, $token)
    {
        Mail::to($email)
            ->send(new ResetPasswordMail($token));
    }
}

```

### パスワードを忘れた場合で送信するメールクラスを作成

laravelのartisanでクラス名を`Mail`としてメールクラスを作成

```bash:terminal

php artisan make:mail ResetPasswordMail

```

`server\app\Mail\ResetPasswordMail.php`が作成されるので編集

```php:server\app\Mail\ResetPasswordMail.php

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

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
        $subject = 'Reset password mail';
        
        // コールバックURLをルート名で取得
        // TODO: これだとホットリロードでホストがおかしくなる
        // $url = route('reset-password', ['token' => $this->token]);

        // TODO: とりあえずこれで対応
        // .envの「APP_URL」に設定したurlを取得
        $baseUrl = config('app.url');
        $token = $this->token;
        $url = "{$baseUrl}/reset-password/{$token}";

        // 送信元のアドレス
        // .envの「MAIL_FROM_ADDRESS」に設定したアドレスを取得
        $from = config('mail.from.address');

        return $this->from($from)
            ->subject($subject)
            // 送信メールのビュー
            ->view('mails.reset_password_mail')
            // ビューで使う変数を渡す
            ->with('url', $url);
    }
}

```

### パスワードを忘れた場合で使用するメールテンプレートの作成

`server\resources\views\mails\reset_password_mail.blade.php`を作成

```php:server\resources\views\mails\reset_password_mail.blade.php

    @extends('layouts.mail')

    @section('title', 'パスワードリセット')

    @section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">パスワードリセット</div>

                    <div class="card-body">
                        <a href='{{$url}}'>こちらのリンク</a>をクリックして、パスワードリセットしてください。
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection

```

### パスワードを忘れた場合で使用するルートの追加

`server\routes\api.php`を修正する

```php:server\routes\api.php

    ...
    // logout
    Route::post('/logout', 'Auth\LoginController@logout')->name('logout');

    // forgot
+   Route::post('/forgot', 'Auth\ForgotPasswordController@forgot')->name('forgot');

    // reset
+   Route::post('/reset', 'Auth\ResetPasswordController@reset')->name('reset');
    ...

```

### ストアの修正

AUTHストアにパスワードを忘れた場合の処理をいれるので`server\resources\js\store\auth.js`を修正

```javascript:server\resources\js\store\auth.js

    ...
    /*
    * ステート（ データの入れ物）
    */
    const state = {
        // ログイン済みユーザーを保持
        user: null,
        // Api通信の成功、失敗の保持
        apiStatus: null,
        // ログインのエラーメッセージの保持
        loginErrorMessages: null,
        // 登録のエラーメッセージの保持
        registerErrorMessages: null,
        // リセットパスワードエラーメッセージの保持
+       resetErrorMessages: null,
        // パスワード変更エラーメッセージの保持
+       forgotErrorMessages: null,
    };

    ...

    /*
     * ミューテーション（同期処理）
     */
    const mutations = {
        ...
        // パスワード変更エラーメッセージの更新
+       setForgotErrorMessages(state, messages) {
+           state.forgotErrorMessages = messages;
+       },
        // リセットパスワードエラーメッセージの更新
+       setResetErrorMessages(state, messages) {
+           state.resetErrorMessages = messages;
+       }
    };

    /*
    * アクション（非同期処理）
    */
    const actions = {
      ...
          /*
          * registerのアクション
          */
          async register(context, data) {
            ...
          }
          /*
           * logoutのアクション
           */
          async logout(context) {
            ...
          },
          /*
           * forgotのアクション
           */
+         async forgot(context, data) {
+             // apiStatusのクリア
+             context.commit("setApiStatus", null);
+
+             // Apiリクエスト
+             const response = await axios.post("/api/forgot", data);
+
+             // 通信成功の場合 201
+             if (response.status === CREATED) {
+               // apiStatus を true に更新
+                 context.commit("setApiStatus", true);
+                 // ここで終了
+                 return false;
+             }
+
+             // 通信失敗のステータスが 422（バリデーションエラー）の場合
+             // apiStatus を false に更新
+             context.commit("setApiStatus", false);
+
+             // 通信失敗のステータスが 422（バリデーションエラー）の場合
+             if (response.status === UNPROCESSABLE_ENTITY) {
+                 // registerErrorMessages にエラーメッセージを登録
+                 context.commit("setForgotErrorMessages", response.data.errors);
+             }
+             // 通信失敗のステータスがその他の場合
+             else {
+                 // エラーストアの code にステータスコードを登録
+                 // 別ストアのミューテーションする場合は第三引数に { root: true } を追加
+                 context.commit("error/setCode", response.status, { root: true });
+             }
+         },
          /*
           * resetのアクション
           */
+          async reset(context, data) {
+              // apiStatusのクリア
+              context.commit("setApiStatus", null);
+
+             // Apiリクエスト
+             const response = await axios.post("/api/reset", data);
+
+             // 通信成功の場合 200
+             if (response.status === OK) {
+                 // apiStatus を true に更新
+                 context.commit("setApiStatus", true);
+                 // user にデータを登録
+                 context.commit("setUser", response.data);
+                 // ここで終了
+                 return false;
+             }
+
+             // apiStatus を false に更新
+             context.commit("setApiStatus", false);
+
+             // 通信失敗のステータスが 422（バリデーションエラー）の場合
+             if (response.status === UNPROCESSABLE_ENTITY) {
+                 // validation error then set message
+                 context.commit("setResetErrorMessages", response.data.errors);
+             }
+             // 通信失敗のステータスがその他の場合
+             else {
+                 // エラーストアの code にステータスコードを登録
+                 // 別ストアのミューテーションする場合は第三引数に { root: true } を追加
+                 context.commit("error/setCode", response.status, { root: true });
+             }
+         },
          /*
           * カレントユーザのアクション
           */
          async currentUser(context) {
            ...
          }
    };
...

```

### ログインページを修正

`server\resources\js\pages\Login.vue`内の`forgot`メソッドがまだ実装されていないので修正

```javascript:server\resources\js\pages\Login.vue

    <template>
        <div class="container">
            <!-- tabs -->
            ...
            <!-- /tabs -->

            <!-- login -->
            ...
            <!-- /login -->

            <!-- register -->
            ...
            <!-- /register -->

            <!-- forgot -->
            <section class="forgot" v-show="tab === 3">
                <h2>forgot</h2>
+               <!-- errors -->
+               <div v-if="forgotErrors" class="errors">
+                   <ul v-if="forgotErrors.email">
+                       <li v-for="msg in forgotErrors.email" :key="msg">{{ msg }}</li>
+                   </ul>
+               </div>
+               <!--/ errors -->
                <form @submit.prevent="forgot">
                    <div>Email</div>
                    <div>
                        <input type="email" v-model="forgotForm.email" />
                    </div>
                    <div>
                        <button type="submit">send</button>
                    </div>
                </form>
            </section>
            <!-- /forgot -->
        </div>
    </template>

    <script>
    export default {
        // vueで使うデータ
        data() {
            return {
                tab: 1,
                loginForm: {
                    email: "",
                    password: "",
                    remember: true
                },
                registerForm: {
                    name: "",
                    email: "",
                    password: "",
                    password_confirmation: ""
                },
                forgotForm: {
                    email: ""
                }
            };
        },
        // 算出プロパティでストアのステートを参照
        computed: {
            // authストアのapiStatus
            apiStatus() {
                return this.$store.state.auth.apiStatus;
            },
            // authストアのloginErrorMessages
            loginErrors() {
                return this.$store.state.auth.loginErrorMessages;
            },
            // authストアのregisterErrorMessages
            registerErrors() {
                return this.$store.state.auth.registerErrorMessages;
            },
            // authストアのforgotErrorMessages
+           forgotErrors(){
+             return this.$store.state.auth.forgotErrorMessages;
+           }
        },
        methods: {
            /*
             * login
             */
            async login() {
                ...
            },
            /*
             * register
             */
            async register() {
                ...
            },
            /*
             * forgot
             */
            async forgot() {
-               alert("forgot");
-               this.clearForm();
                // authストアのforgotアクションを呼び出す
+               await this.$store.dispatch("auth/forgot", this.forgotForm);
+               if (this.apiStatus) {
                    // show message
+                   this.$store.commit("message/setContent", {
+                       content: "パスワードリセットメールを送りました。",
+                       timeout: 10000
+                   });
+                   // AUTHストアのエラーメッセージをクリア
+                   this.clearError();
                    // フォームをクリア
+                   this.clearForm();
+               }
            },
            /*
             * clear error messages
             */
            clearError() {
                // AUTHストアのすべてのエラーメッセージをクリア
                this.$store.commit("auth/setLoginErrorMessages", null);
                this.$store.commit("auth/setRegisterErrorMessages", null);
+               this.$store.commit("auth/setForgotErrorMessages", null);
            },
            /*
            * clear form
            */
            clearForm() {
                ...
            }
        }
    };
    </script>
    ...

```

### リセットページを作成

新しいパスワードを入力するページを`server\resources\js\pages\Reset.vue`として作成する

```javascript:server\resources\js\pages\Reset.vue

    <template>
        <div class="container--small">
            <h2>password reset</h2>

            <div class="panel">
                <!-- @submitで login method を呼び出し -->
                <!-- @submitイベントリスナに reset をつけるとsubmitイベントによってページがリロードさない -->
                <form class="form" @submit.prevent="reset">
                    <!-- errors -->
                    <div v-if="resetErrors" class="errors">
                        <ul v-if="resetErrors.password">
                            <li v-for="msg in resetErrors.password" :key="msg">{{ msg }}</li>
                        </ul>
                        <ul v-if="resetErrors.token">
                            <li v-for="msg in resetErrors.token" :key="msg">{{ msg }}</li>
                        </ul>
                    </div>
                    <!--/ errors -->

                    <div>
                        <input type="password" v-model="resetForm.password" />
                    </div>
                    <div>
                        <input type="password" v-model="resetForm.password_confirmation" />
                    </div>
                    <div>
                        <button type="submit">reset</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <script>
    import Cookies from "js-cookie";

    export default {
        // vueで使うデータ
        data() {
            return {
                resetForm: {
                    password: "",
                    password_confirmation: "",
                    token: ""
                }
            };
        },
        computed: {
            // authストアのapiStatus
            apiStatus() {
                return this.$store.state.auth.apiStatus;
            },
            // authストアのresetErrorMessages
            resetErrors() {
                return this.$store.state.auth.resetErrorMessages;
            }
        },
        methods: {
            /*
            * reset
            */
            async reset() {
                // authストアのresetアクションを呼び出す
                await this.$store.dispatch("auth/reset", this.resetForm);
                // 通信成功
                if (this.apiStatus) {
                    // メッセージストアで表示
                    this.$store.commit("message/setContent", {
                        content: "パスワードをリセットしました。",
                        timeout: 10000
                    });
                    // AUTHストアのエラーメッセージをクリア
                    this.clearError();
                    // フォームをクリア
                    this.clearForm();
                    // トップページに移動
                    this.$router.push("/");
                }
            },

            /*
            * clear error messages
            */
            clearError() {
                // AUTHストアのエラーメッセージをクリア
                this.$store.commit("auth/setResetErrorMessages", null);
            },

            /*
            * clear reset Form
            */
            clearForm() {
                // reset
                this.resetForm.password = "";
                this.resetForm.password_confirmation = "";
                this.resetForm.token = "";
            }
        },
        created() {
            // クッキーからリセットトークンを取得
            const token = Cookies.get("RESETTOKEN");

            // リセットトークンがない場合はルートページへ移動させる
            if (this.resetForm.token == null) {
                // move to home
                this.$router.push("/");
            }

            // フォームにリセットトークンをセット
            this.resetForm.token = token;

            // リセットトークンをクッキーから削除
            if (token) {
                Cookies.remove("RESETTOKEN");
            }
        }
    };
    </script>

```

### Vueルーターにあたらしいルートを追加

`server\resources\js\router.js`を編集

```javascript:server\resources\js\router.js

    ...
    // ページをインポート
    import Home from "./pages/Home.vue";
    import Login from "./pages/Login.vue";
+   import Reset from "./pages/Reset.vue";
    import SystemError from "./pages/errors/SystemError.vue";
    import NotFound from "./pages/errors/NotFound.vue";

    Vue.use(VueRouter);

    // パスとページの設定
    const routes = [
        // home
        {
            ...
        },
        // login
        {
            ...
        },
        // password reset
+       {
+           // urlのパス
+           path: "/reset",
+           // インポートしたページ
+           component: Reset,
+           // ページコンポーネントが切り替わる直前に呼び出される関数
+           // to はアクセスされようとしているルートのルートオブジェクト
+           // from はアクセス元のルート
+           // next はページの移動先
+           beforeEnter(to, from, next) {
+               if (store.getters["auth/check"]) {
+                   next("/");
+               } else {
+                   next();
+               }
+           }
+       },
        // システムエラー
        {
            ...
        },
        // not found
        {
            ...
        }
    ];
    ...

```

------------------------------------------------------------------------------------------

## パスワード変更メールからのコールバックを処理してパスワード変更を完了する

### ResetPasswordControllerの修正

`server\app\Http\Controllers\Auth\ResetPasswordController.php`をパスワード変更用にまるっと修正

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

    class ResetPasswordController extends Controller
    {
        use ResetsPasswords;

        // vueでアクセスするログインへのルート
        protected $vueRouteLogin = 'login';
        // vueでアクセスするリセットへのルート
        protected $vueRouteReset = 'reset';
        // server\config\auth.phpで設定していない場合のデフォルト
        protected $expires = 600 * 5;

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

            // server\config\auth.phpで設定した値を取得、ない場合はもとの値
            $this->expires = config('auth.reset_password_expires', $this->expires);
        }

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
                return $this->redirectWithMessage($this->vueRouteLogin, $message);
            }

            // トークンをクッキーにつけてリセットページにリダイレクト
            return $this->redirectWithToken($this->vueRouteReset, $token);
        }

        /**
         * reset
         * パスワードリセットApi
         *
         * @param Request $request
         * @return void
         */
        public function reset(Request $request)
        {
            // バリデーション
            $validator = $this->validator($request->all());

            // 送られてきたトークンを復号
            $token = Crypt::decryptString($request->token);

            // リセットパスワードモデルを取得
            $resetPassword = ResetPassword::where('token', $token)->first();

            // ユーザの宣言
            $user = null;

            // 追加のバリデーション
            $validator->after(function ($validator) use ($resetPassword, &$user) {

                // リセットパスワードがない場合
                if (!$resetPassword) {

                    $validator->errors()->add('token', __('invalid token.'));
                }

                // トークン期限切れチェック
                $isExpired = $this->tokenExpired($resetPassword->created_at);
                if ($isExpired) {

                    $validator->errors()->add('token', __('Expired token.'));
                }

                // ユーザの取得
                $user = User::where('email', $resetPassword->email)->first();

                // ユーザの存在チェック
                if (!$user) {

                    $validator->errors()->add('token', __('is not user.'));
                }
            });

            // これで、バリデーションがある場合に、jsonレスポンスを返す
            $validator->validate();

            // トランザクション、アップデート後のユーザを返す
            $user = DB::transaction(function () use ($request, $resetPassword, $user) {

                // リセットパスワードテーブルからデータを削除
                ResetPassword::destroy($resetPassword->email);

                // パスワードを変更
                $user->password = Hash::make($request->password);

                // リメンバートークンを変更
                $user->setRememberToken(Str::random(60));

                // データを保存
                $user->save();

                // ユーザを返却
                return $user;
            });

            // イベントを発行
            event(new PasswordReset($user));

            // ユーザをログインさせる
            Auth::login($user, true);

            // ユーザを返却
            return $user;
        }

        /**
         * validator
         *
         * @param  array  $data
         * @return \Illuminate\Support\Facades\Validator;
         */
        protected function validator(array $data)
        {
            return Validator::make($data, [
                'token' => ['required'],
                'password' => ['required', 'min:8', 'confirmed'],
            ]);
        }

        /**
         * Determine if the token has expired.
         *
         * @param  string  $createdAt
         * @return bool
         */
        protected function tokenExpired($createdAt)
        {
            return Carbon::parse($createdAt)->addSeconds($this->expires)->isPast();
        }

        /**
         * redirect with message
         *
         * @param  string  $route
         * @param  string  $message
         * @return Redirect
         */
        protected function redirectWithMessage($vueRoute, $message)
        {
            // vueでアクセスするルートを作る
            // コールバックURLをルート名で取得
            // TODO: これだとホットリロードでポートがとれない
            // $route = url($vueRoute);

            // TODO: とりあえずこれで対応
            // .envの「APP_URL」に設定したurlを取得
            $baseUrl = config('app.url');
            $route = "{$baseUrl}/{$vueRoute}";

            return redirect($route)
                // PHPネイティブのsetcookieメソッドに指定する引数同じ
                // ->cookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly)
                ->cookie('MESSAGE', $message, 0, '', '', false, false);
        }

        /**
         * redirect with token
         *
         * @param  string  $route
         * @param  string  $message
         * @return Redirect
         */
        protected function redirectWithToken($vueRoute, $token)
        {
            // vueでアクセスするルートを作る
            // コールバックURLをルート名で取得
            // TODO: これだとホットリロードでポートがとれない
            // $route = url($vueRoute);

            // TODO: とりあえずこれで対応
            // .envの「APP_URL」に設定したurlを取得
            $baseUrl = config('app.url');
            $route = "{$baseUrl}/{$vueRoute}";
            return redirect($route)->cookie('RESETTOKEN', $token, 0, '', '', false, false);
        }
    }

```

### パスワード変更で使用するルートの追加

パスワード変更メールからのコールバックを受けるルートを`server\routes\web.php`に追加する

```php:server\routes\web.php

    ...
    // verification callback
    Route::get('/verification/{token}', 'Auth\VerificationController@register')
        ->name('verification');

    // reset password callback
+   Route::get('/reset-password/{token}', 'Auth\ResetPasswordController@resetPassword')
+       ->name('reset-password');
    ...

```

### パスワードを忘れた場合とパスワード変更メールで変更完了をテスト

1. パスワード変更メールを送信  

![キャプチャ1](http://www.aska-ltd.jp/uploads/blogs/2006190130laravel-mix-6-1.png)  

データベース  
![キャプチャ2](	http://www.aska-ltd.jp/uploads/blogs/2006190151laravel-mix-6-2.png)  

2. メールの確認  

![キャプチャ3](http://www.aska-ltd.jp/uploads/blogs/2006190121laravel-mix-6-3.png)  

3. 移動後  

![キャプチャ4](http://www.aska-ltd.jp/uploads/blogs/2006190126laravel-mix-6-4.png)  

4. 変更後
![キャプチャ5](http://www.aska-ltd.jp/uploads/blogs/2006190137laravel-mix-6-5.png)  


次はソーシャルログインを追加します。
 
<!-- > [Laravel mix vue No.7 - Socialite - ソーシャルログイン](https://www.aska-ltd.jp/jp/blog/72) -->

<script src="https://www.aska-ltd.jp/js/blog.js"></script>
<link href="https://www.aska-ltd.jp/css/blog.css" rel="stylesheet">
