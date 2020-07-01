<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

今回はパスワードを忘れた場合にメールでパスワードリセットできるようにします。  
こちらも最善の方法がわからないので自分なりに作っていきます。  
もっと良い方法を知ってる方はコメントで教えてくれたら嬉しいです！  

前回の記事は[Laravel mix vue5 - メール認証に変更](https://www.aska-ltd.jp/jp/blog/69)

# パスワードを忘れた場合にメールでパスワードリセット

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


## ResetPasswordモデル

laravelのartisanでmodelを作成

テーブル名は`Reset_password`なのでクラス名は`ResetPassword`として`Models`フォルダに作成

```bash

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

---

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

```bash

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
    ...

    // reset
    Route::post('/reset', 'Auth\ResetPasswordController@reset')
    ->name('reset');

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
           * logoutのアクション
           */
          async logout(context) {
            ...
          },
          /*
           * registerのアクション
           */
           async register(context, data) {
             ...
           }
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
+                 context.commit("setForgotErrorMessages", response.+data.errors);
+             }
+             // 通信失敗のステータスがその他の場合
+             else {
+                 // エラーストアの code にステータスコードを登録
+                 // 別ストアのミューテーションする場合は第三引数に { root: +true } を追加
+                 context.commit("error/setCode", response.status, { +root: true });
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
+             // if failed
+             context.commit("setApiStatus", false);
+
+             // validator server error
+             if (response.status === UNPROCESSABLE_ENTITY) {
+                 // validation error then set message
+                 context.commit("setResetErrorMessages", response.+data.errors);
+             } else {
+                 // [error] server error then set error code
+                 context.commit("error/setCode", response.status, { +root: true });
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

---

#### Vueルーターにあたらしいルートを追加

```javascript:server\resources\js\pages\Reset.vue

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
        {
            // urlのパス
            path: "/reset",
            // インポートしたページ
            component: Reset,
            // ページコンポーネントが切り替わる直前に呼び出される関数
            // to はアクセスされようとしているルートのルートオブジェクト
            // from はアクセス元のルート
            // next はページの移動先
            beforeEnter(to, from, next) {
                if (store.getters["auth/check"]) {
                    next("/");
                } else {
                    next();
                }
            }
        },
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

パスワード変更メールからのコールバックを受けるルートを追加する

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


次は残っているパスワード変更の部分を実装します。  
 
[Laravel mix vue No.7 - socialite](https://www.aska-ltd.jp/jp/blog/72)

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