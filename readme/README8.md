<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

今回はLaravel側を多言語化します。

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

# Laravel Internationalization - Laravel多言語化

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

    <template>
        <header>
            <!-- リンクを設定 -->
            <RouterLink to="/">{{ $t('word.home') }}</RouterLink>
            <RouterLink v-if="!isLogin" to="/login">{{ $t('word.login') }}</RouterLink>
            <!-- ログインしている場合はusernameを表示 -->
            <span v-if="isLogin">{{username}}</span>
            <!-- クリックイベントにlogoutメソッドを登録 -->
            <span v-if="isLogin" @click="logout">logout</span>
            <!-- 言語切替 -->
+           <select v-model="selectedLang" @change="changeLang">
+               <option v-for="lang in langList" :value="lang.value" :key="lang.value">{{ lang.text }}</option>\
+           </select>
        </header>
    </template>

    <script>
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
    </script>

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

セレクトボタンで言語を日本語に切り替える

3. もう一度ログイン画面で間違ってみると、日本語でバリデーションメッセージが出る  

![キャプチャ4](http://www.aska-ltd.jp/uploads/blogs/200702123908-2.png)  


これでLaravelから送られてくるメッセージは多言語化できました。
次はVueを多言語化してみます。

<!-- > [Laravel mix vue No.9 - Vue Internationalization - Vue多言語化](https://www.aska-ltd.jp/jp/blog/76) -->

<script src="https://www.aska-ltd.jp/js/blog.js"></script>
<link href="https://www.aska-ltd.jp/css/blog.css" rel="stylesheet">