<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

今回はLaravel側を多言語化します。

前回の記事は[Laravel mix vue No.7 - Socialite](https://www.aska-ltd.jp/jp/blog/72)

# Laravel mix vue No.8 - Laravel Internationalization 多言語化

## サンプル
- このセクションを始める前  
[github ldocker 08](https://github.com/kohx/ldocker/tree/08)  
  
- 完成  
[github ldocker 09](https://github.com/kohx/ldocker/tree/09)

------------------------------------------------------------------------------------------

## フォルダ構成

```text

└─ server
   ├─ app
   |  └─ Http
   |     ├─ Middleware
+  |     |  └─ Language.php
   |     └─ Kernel.php
   ├─ resources
   |  ├─ lang
+  |  |  └─ ja
+  |  |     ├─ auth.php
+  |  |     ├─ pagination.php
+  |  |     ├─ passwords.php
+  |  |     └─ validation.php
   |  ├─ js
   |  |  └─ components
   |  |     └─ Header.vue
+  |  └─ helper.js
   └─ routes
      ├─ api.php
      └─ web.php

```

------------------------------------------------------------------------------------------

## dockerスタート

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

## 言語のコントロール

### 言語の切り替えるApiを作成

`server\routes\api.php`に言語切替用のルートを作成して、セッションに言語をセットするようにする

```php:server\routes\api.php

    ...
    // トークンリフレッシュ
    Route::get('/refresh-token', function (Illuminate\Http\Request $request) {
        $request->session()->regenerateToken();
        return response()->json();
    })->name('refresh-token');

+   // set lang
+   Route::get('/set-lang/{lang}', function (Illuminate\Http\Request $request, $lang) {
+       $request->session()->put('language', $lang);
+       return response()->json();
+   })->name('set-lang');

```

### 言語切替用にミドルウェアを作成

artisanコマンドを使ってい`Language`という名前でミドルウェアを作成

```bash:terminal

php artisan make:middleware Language

```

`server\app\Http\Middleware\Language.php`が作られるので編集

```php:server\app\Http\Middleware\Language.php

<?php

namespace App\Http\Middleware;

    use Closure;
+   use App;

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
+           // 設定されているロケールを取得
+           $locale = App::getLocale();
+
+           // セッションのロケールを取得ないときはデフォルトロケール
+           $leng = $request->session()->get('language');
+
+           // ロケールがあって、言語が変わる場合
+           if ($leng && $locale !== $leng) {
+               // 言語をセット
+               // ない場合は「config/app.php」に設定した「fallback_locale」の値になる
+               App::setLocale($leng);
+           }

            return $next($request);
        }
    }

```

### 作成したミドルウェアを登録

ルートでミドルウェアを使うので`server\app\Http\Kernel.php`の`routeMiddleware`に登録

```php:server\app\Http\Kernel.php

    ...

    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
+       'language' => \App\Http\Middleware\Language::class,
    ];

    ...

```

### 作成したミドルウェアをルートにセット

`server\routes\api.php`を編集

```php:server\routes\api.php

    ...

+   Route::middleware(['language'])->group(function () {
        // register
        Route::post('/register', 'Auth\RegisterController@sendMail')->name('register');
        // login
        Route::post('/login', 'Auth\LoginController@login')->name('login');
        // logout
        Route::post('/logout', 'Auth\LoginController@logout')->name('logout');
        // forgot
        Route::post('/forgot', 'Auth\ForgotPasswordController@forgot')->name('reset');
        // reset
        Route::post('/reset', 'Auth\ResetPasswordController@reset')
            ->name('reset');
        // user
        Route::get('/user', function () {
            return Auth::user();
        })->name('user');
        // トークンリフレッシュ
        Route::get('/refresh-token', function (Illuminate\Http\Request $request) {
            $request->session()->regenerateToken();
            return response()->json();
        })->name('refresh-token');
+   });

    // set lang
    Route::get('/set-lang/{lang}', function (Illuminate\Http\Request $request, $lang) {
        $request->session()->put('language', $lang);
        return response()->json();
    })->name('set-lang');

```

`server\routes\web.php`を編集

```php:server\routes\web.php

    ...

+   Route::middleware(['language'])->group(function () {
        // verification callback
        Route::get('/verification/{token}', 'Auth\VerificationController@register')
            ->name('verification');

        // reset password callback
        Route::get('/reset-password/{token}', 'Auth\ResetPasswordController@resetPassword')
            ->name('reset-password');

        // socialite 各プロバイダにリダイレクトするルート
        Route::get('/login/{provider}', 'Auth\LoginController@redirectToProvider');

        // socialite 各プロバイダからのコールバックを受けるルート
        Route::get('/login/{provider}/callback', 'Auth\LoginController@handleProviderCallback');

        // API以外はindexを返すようにして、VueRouterで制御
        Route::get('/{any?}', fn () => view('index'))->where('any', '.+');
+   });

```

------------------------------------------------------------------------------------------

## Vue側の修正

### 使用するモジュールインストール

言語をストレージに保存するので、便利なモジュールを使用

```bash:terminal

  npm i vue-localstorage

```

#### 使用例

```javascript:example

    // データ保存
    this.$localStorage.set(key,value)

    // データ削除
    this.$localStorage.remove(key)

    // データ取得
    this.$localStorage.get(key)

    //データ取得 デフォルト値を仕様
    this.$localStorage.get(key, default)

```

#### Vueで使う用意

`server\resources\js\app.js`に追加

```javascript:server\resources\js\app.js

    ...

    /**
     * vue-localstorage
     * https://www.npmjs.com/package/vue-localstorage
     */
    // vue-localstorageをインポート
+   import VueLocalStorage from "vue-localstorage";
    // VueLocalStorage宣言
    // 「$this.storage.get()」のように使えるように名前を変更
    // この名前でプロパティを宣言する
+   Vue.use(VueLocalStorage, {
+       name: 'storage'
+   });

    ...

```

### 言語取得のメソッドを作る

いろんなとこで使う関数を登録しておくヘルパー`server\resources\js\helper.js`としてを作って、言語取得の関数を作る

```javascript:server\resources\js\helper.js

export default class Helper {

    static capitalizeFirstLetter(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    /**
     * get language
     * ブラウザから言語を取得
     *
     */
    static getLanguage() {
        const language = (window.navigator.languages && window.navigator.languages[0]) ||
            window.navigator.language ||
            window.navigator.userLanguage ||
            window.navigator.browserLanguage;
        return language.slice(0, 2);
    }
}

```

### 言語を取得と設定

`server\resources\js\components\Header.vue`を編集してヘッダコンポネントで言語切り替えできるようにする

```javascript:server\resources\js\components\Header.vue

    ...

+   import Cookies from "js-cookie";
+   import Helper from "../helper";

    export default {
        computed: {
            ...
        },
        // app.jsでVueLocalStorageの名前を変更したので「storage」で宣言
+       storage: {
+           language: {
+               type: String,
+               default: null
+           }
+       },
        methods: {
            ...
              // 言語切替メソッド
+             changeLang() {
+                 // ローカルストレージに「language」をセット
+                 this.$storage.set("language", this.selectedLang);
+                 // Apiリクエスト 言語を設定
+                 axios.get(`/api/set-lang/${this.selectedLang}`);
+             }
        },
+       created() {
+           // ローカルストレージから「language」を取得
+           this.selectedLang = this.$storage.get("language");
+
+           // サーバ側をクライアント側に合わせる
+
+           // storageLangがない場合
+           if (!this.selectedLang) {
+               // ブラウザーの言語を取得
+               const defaultLang = Helper.getLanguage();
+               // ローカルストレージに「language」をセット
+               this.$storage.set("language", defaultLang);
+               // Apiリクエスト 言語を設定
+               axios.get(`/api/set-lang/${defaultLang}`);
+           } 
+           // ある場合はサーバ側をクライアント側に合わせる
+           else {
+               axios.get(`/api/set-lang/${this.selectedLang}`);
+           }
+       }
    };

```

------------------------------------------------------------------------------------------

## laravelに準備してある言語ファイル

laravelに準備してある言語ファイルの日本語版を作るために
`server\resources\lang`に`ja`フォルダを作成して以下を作成する

* auth.php
* pagination.php
* passwords.php
* validation.php

### auth.php 

`server\resources\lang\ja\auth.php`を作成

```php:server\resources\lang\ja\auth.php

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed'   => '認証に失敗しました。',
    'throttle' => 'ログイン試行回数が制限に達しました。 :seconds 秒以上開けて再度お試しください。',

];

```

### pagination.php

`server\resources\lang\ja\pagination.php`を作成

```php:server\resources\lang\ja\pagination.php

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pagination Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the paginator library to build
    | the simple pagination links. You are free to change them to anything
    | you want to customize your views to better match your application.
    |
    */

    'previous' => '&laquo; 前へ',
    'next'     => '次へ &raquo;',

];

```

### passwords

`server\resources\lang\ja\passwords.php`を作成

```php:server\resources\lang\ja\passwords.php

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password Reset Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are the default lines which match reasons
    | that are given by the password broker for a password update attempt
    | has failed, such as for an invalid token or invalid new password.
    |
    */

    'reset'     => 'パスワードがリセットされました！',
    'sent'      => 'パスワードリセットリンクを電子メールで送信しました！',
    'throttled' => '再試行する前にお待ちください。',
    'token'     => 'このパスワードリセットトークンは無効です。',
    'user'      => "この電子メールアドレスを持つユーザーが見つかりません。",

];

```

### validation

`server\resources\lang\ja\validation.php`を作成

```php:server\resources\lang\ja\validation.php

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | バリデーション言語行
    |--------------------------------------------------------------------------
    |
    | 以下の言語行はバリデタークラスにより使用されるデフォルトのエラー
    | メッセージです。サイズルールのようにいくつかのバリデーションを
    | 持っているものもあります。メッセージはご自由に調整してください。
    |
    */

    'accepted'             => ':attributeを承認してください。',
    'active_url'           => ':attributeが有効なURLではありません。',
    'after'                => ':attributeには、:dateより後の日付を指定してください。',
    'after_or_equal'       => ':attributeには、:date以降の日付を指定してください。',
    'alpha'                => ':attributeはアルファベットのみがご利用できます。',
    'alpha_dash'           => ':attributeはアルファベットとダッシュ(-)及び下線(_)がご利用できます。',
    'alpha_num'            => ':attributeはアルファベット数字がご利用できます。',
    'array'                => ':attributeは配列でなくてはなりません。',
    'before'               => ':attributeには、:dateより前の日付をご利用ください。',
    'before_or_equal'      => ':attributeには、:date以前の日付をご利用ください。',
    'between'              => [
        'numeric' => ':attributeは、:minから:maxの間で指定してください。',
        'file'    => ':attributeは、:min kBから、:max kBの間で指定してください。',
        'string'  => ':attributeは、:min文字から、:max文字の間で指定してください。',
        'array'   => ':attributeは、:min個から:max個の間で指定してください。',
    ],
    'boolean'              => ':attributeは、trueかfalseを指定してください。',
    'confirmed'            => ':attributeと、確認フィールドとが、一致していません。',
    'date'                 => ':attributeには有効な日付を指定してください。',
    'date_equals'          => ':attributeには、:dateと同じ日付けを指定してください。',
    'date_format'          => ':attributeは:format形式で指定してください。',
    'different'            => ':attributeと:otherには、異なった内容を指定してください。',
    'digits'               => ':attributeは:digits桁で指定してください。',
    'digits_between'       => ':attributeは:min桁から:max桁の間で指定してください。',
    'dimensions'           => ':attributeの図形サイズが正しくありません。',
    'distinct'             => ':attributeには異なった値を指定してください。',
    'email'                => ':attributeには、有効なメールアドレスを指定してください。',
    'ends_with'            => ':attributeには、:valuesのどれかで終わる値を指定してください。',
    'exists'               => '選択された:attributeは正しくありません。',
    'file'                 => ':attributeにはファイルを指定してください。',
    'filled'               => ':attributeに値を指定してください。',
    'gt'                   => [
        'numeric' => ':attributeには、:valueより大きな値を指定してください。',
        'file'    => ':attributeには、:value kBより大きなファイルを指定してください。',
        'string'  => ':attributeは、:value文字より長く指定してください。',
        'array'   => ':attributeには、:value個より多くのアイテムを指定してください。',
    ],
    'gte'                  => [
        'numeric' => ':attributeには、:value以上の値を指定してください。',
        'file'    => ':attributeには、:value kB以上のファイルを指定してください。',
        'string'  => ':attributeは、:value文字以上で指定してください。',
        'array'   => ':attributeには、:value個以上のアイテムを指定してください。',
    ],
    'image'                => ':attributeには画像ファイルを指定してください。',
    'in'                   => '選択された:attributeは正しくありません。',
    'in_array'             => ':attributeには:otherの値を指定してください。',
    'integer'              => ':attributeは整数で指定してください。',
    'ip'                   => ':attributeには、有効なIPアドレスを指定してください。',
    'ipv4'                 => ':attributeには、有効なIPv4アドレスを指定してください。',
    'ipv6'                 => ':attributeには、有効なIPv6アドレスを指定してください。',
    'json'                 => ':attributeには、有効なJSON文字列を指定してください。',
    'lt'                   => [
        'numeric' => ':attributeには、:valueより小さな値を指定してください。',
        'file'    => ':attributeには、:value kBより小さなファイルを指定してください。',
        'string'  => ':attributeは、:value文字より短く指定してください。',
        'array'   => ':attributeには、:value個より少ないアイテムを指定してください。',
    ],
    'lte'                  => [
        'numeric' => ':attributeには、:value以下の値を指定してください。',
        'file'    => ':attributeには、:value kB以下のファイルを指定してください。',
        'string'  => ':attributeは、:value文字以下で指定してください。',
        'array'   => ':attributeには、:value個以下のアイテムを指定してください。',
    ],
    'max'                  => [
        'numeric' => ':attributeには、:max以下の数字を指定してください。',
        'file'    => ':attributeには、:max kB以下のファイルを指定してください。',
        'string'  => ':attributeは、:max文字以下で指定してください。',
        'array'   => ':attributeは:max個以下指定してください。',
    ],
    'mimes'                => ':attributeには:valuesタイプのファイルを指定してください。',
    'mimetypes'            => ':attributeには:valuesタイプのファイルを指定してください。',
    'min'                  => [
        'numeric' => ':attributeには、:min以上の数字を指定してください。',
        'file'    => ':attributeには、:min kB以上のファイルを指定してください。',
        'string'  => ':attributeは、:min文字以上で指定してください。',
        'array'   => ':attributeは:min個以上指定してください。',
    ],
    'not_in'               => '選択された:attributeは正しくありません。',
    'not_regex'            => ':attributeの形式が正しくありません。',
    'numeric'              => ':attributeには、数字を指定してください。',
    'password'             => 'パスワードが正しくありません｡',
    'present'              => ':attributeが存在していません。',
    'regex'                => ':attributeに正しい形式を指定してください。',
    'required'             => ':attributeは必ず指定してください。',
    'required_if'          => ':otherが:valueの場合、:attributeも指定してください。',
    'required_unless'      => ':otherが:valuesでない場合、:attributeを指定してください。',
    'required_with'        => ':valuesを指定する場合は、:attributeも指定してください。',
    'required_with_all'    => ':valuesを指定する場合は、:attributeも指定してください。',
    'required_without'     => ':valuesを指定しない場合は、:attributeを指定してください。',
    'required_without_all' => ':valuesのどれも指定しない場合は、:attributeを指定してください。',
    'same'                 => ':attributeと:otherには同じ値を指定してください。',
    'size'                 => [
        'numeric' => ':attributeは:sizeを指定してください。',
        'file'    => ':attributeのファイルは、:sizeキロバイトでなくてはなりません。',
        'string'  => ':attributeは:size文字で指定してください。',
        'array'   => ':attributeは:size個指定してください。',
    ],
    'starts_with'          => ':attributeには、:valuesのどれかで始まる値を指定してください。',
    'string'               => ':attributeは文字列を指定してください。',
    'timezone'             => ':attributeには、有効なゾーンを指定してください。',
    'unique'               => ':attributeの値は既に存在しています。',
    'uploaded'             => ':attributeのアップロードに失敗しました。',
    'url'                  => ':attributeに正しい形式を指定してください。',
    'uuid'                 => ':attributeに有効なUUIDを指定してください。',

    /*
    |--------------------------------------------------------------------------
    | Custom バリデーション言語行
    |--------------------------------------------------------------------------
    |
    | "属性.ルール"の規約でキーを指定することでカスタムバリデーション
    | メッセージを定義できます。指定した属性ルールに対する特定の
    | カスタム言語行を手早く指定できます。
    |
    */

    'custom' => [
        '属性名' => [
            'ルール名' => 'カスタムメッセージ',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | カスタムバリデーション属性名
    |--------------------------------------------------------------------------
    |
    | 以下の言語行は、例えば"email"の代わりに「メールアドレス」のように、
    | 読み手にフレンドリーな表現でプレースホルダーを置き換えるために指定する
    | 言語行です。これはメッセージをよりきれいに表示するために役に立ちます。
    |
    */

    'attributes' => [],

];

```

------------------------------------------------------------------------------------------

## 独自に設定したメッセージの言語ファイル

### 翻訳メッセージの取得箇所を調べる

ここまでで`Lang::get()`と`__()`のどちらもつかっていいるので、この2つで検索  
関数をヘルパ関数`__()`に統一する。  

`server\app\Http\Controllers\Auth\LoginController.php`を確認

```php:server\app\Http\Controllers\Auth\LoginController.php

    ...

    // メッセージをつけてリダイレクト
-   $message = Lang::get('socialite login success.');
+   $message = __('socialite login success.');

    ...

    // メッセージをつけてリダイレクト
-   $message = Lang::get('authentication failed.');
+   $message = __('authentication failed.');

    ...

```

`server\app\Http\Controllers\Auth\ResetPasswordController.php`を確認

```php:server\app\Http\Controllers\Auth\ResetPasswordController.php

    ...

    // メッセージをクッキーにつけてリダイレクト
-   $message = Lang::get('password reset email has not been sent.');
+   $message = __('password reset email has not been sent.');

    ...

    $validator->errors()->add('token', __('invalid token.'));

    ...

    $validator->errors()->add('token', __('expired token.'));

    ...

    $validator->errors()->add('token', __('is not user.'));

    ...

```

`server\app\Http\Controllers\Auth\VerificationController.php`を確認

```php:server\app\Http\Controllers\Auth\VerificationController.php

    ...
    $message = __('not provisionally registered.');
    ...
    $message = __('completion of registration.');
    ...

```

多言語ヘルパを忘れていたのでつける

`server\app\Mail\VerificationMail.php`を修正

```php:server\app\Mail\VerificationMail.php

    ...
    public function build()
    {
        // 件名
-       $subject = 'Verification mail';
+       $subject = __('verification mail');
    ...

```

`server\app\Mail\ResetPasswordMail.php`を修正

```php:server\app\Mail\ResetPasswordMail.php

    ...
    public function build()
    {
        // 件名
-       $subject = 'Reset password mail';
+       $subject = __('reset password mail');
    ...

```

### 言語ファイルの作成

Laravel側で多言語化するのはメールテンプレートとメッセージぐらいなので、今回はJsonファイルを使用。  

Jsonファイルを使用する場合は翻訳文字列をキーとして利用  

英語の場合は`server\resources\lang\en.json`  
日本語の場合は`server\resources\lang\ja.json`  
のように`resources/lang`ディレクトリ下に「言語コード + .json」として保存  
  
`"--- auth ---": "",` と `"--- mail template ---": "",`はコメントとして使用

```json:server\resources\lang\en.json

    {
        "--- auth ---": "",
        "authentication failed.": "authentication failed.",
        "socialite login success.": "socialite login success.",
        "password reset email has not been sent.": "password reset email has not been sent.",
        "invalid token.": "invalid token.",
        "expired token.": "expired token.",
        "is not user.": "is not user.",
        "not provisionally registered.": "not provisionally registered.",
        "completion of registration.": "completion of registration.",
        "--- mail template ---": "",
        "verification mail": "Verification mail",
        "registration certification": "registration certification.",
        "please click this link to verify your email.": "please click this link to verify your email.",
        "reset password mail": "Reset password mail",
        "password reset": "password reset",
        "click this link to go to password reset.": "click this link to go to password reset."
    }

```

```json:server\resources\lang\ja.json

    {
        "--- auth ---": "",
        "authentication failed.": "認証に失敗しました。",
        "socialite login success.": "ソーシャルログインに成功しました。",
        "password reset email has not been sent.": "パスワード再設定メールが送信されていません。",
        "invalid token.": "無効なトークンです。",
        "expired token.": "期限切れのトークンです。",
        "is not user.": "ユーザーではありません。",
        "not provisionally registered.": "仮登録されていません。",
        "completion of registration.": "登録の完了しました。",
        "--- mail template ---": "",
        "verification mail": "登録認証メール",
        "registration certification": "登録認証",
        "please click this link to verify your email.": "こちらのリンクをクリックして、登録認証してください。",
        "reset password mail": "パスワードリセットメール",
        "password reset": "パスワードリセット",
        "click this link to go to password reset.": "こちらのリンクをクリックして、パスワードリセットへ移動してください。"
    }

```

## apiで言語が変更されるかテスト

1. ログイン画面で間違ってみると、英語でバリデーションメッセージが出る  

![キャプチャ1](http://www.aska-ltd.jp/uploads/blogs/200702121708-1.png)

2. Apiで言語を日本語に変えてみる  

ブラウザで`http://localhost:3000/api/set-lang/ja`にアクセス

3. もう一度ログイン画面で間違ってみると、日本語でバリデーションメッセージが出る  

![キャプチャ4](http://www.aska-ltd.jp/uploads/blogs/200702123908-2.png)  


これでLaravelから送られてくるメッセージは多言語化できました。
次はVueを多言語化してみます。

[Laravel mix vue No.9 - Vue多言語化](https://www.aska-ltd.jp/jp/blog/73)




















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
  .container {
    width: 60em;
  }
  .blog div#share {
    margin: 0;
  }
  .blog img {
    width: 100%;
    max-height: none;
    border-radius: 0;
  }
  /* for main image */
  .blog img:nth-child(2) {
    border: none;
  }
  .blog ul, .blog ol {
    padding: 0;
    margin: 0;
  }
  .blog ul li, .blog ol li {
    display: list-item;
    vertical-align: top;
    padding: 0;
    margin: 0;
  }
  .blog ul li {
    display: list-item;
    list-style: circle;
  }
  .blog li + li {
    margin-top: 0;
  }
  .blog li:nth-child(2){
    color: inherit;
  }
  .blog img {
    border: 3px solid #ccc;
    padding: 1rem;
    box-sizing: border-box;
  }
  .blog .user {
    border: none;
    padding: 0;
}
</style>