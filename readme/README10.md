
<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

今回はUIを「Vue Font Awesome」、「Vue Formulate」を使ってもっと使いやすくします。

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
  <li><a href="https://www.aska-ltd.jp/jp/blog/77">Laravel mix vue No.10 - Vue Font Awesome, Vue Formulate, etc - UIの作り込み</a></li>
</ul>

# Vue Font Awesome, Vue Formulate, etc - UIの作り込み

## サンプル
- このセクションを始める前  
[github ldocker 10](https://github.com/kohx/ldocker/tree/10)  
  
- 完成  
[github ldocker 11](https://github.com/kohx/ldocker/tree/11)

------------------------------------------------------------------------------------------

## フォルダ構成

```text

└─ server
   ├─ resources
   |  ├─ js
   |  |  ├─ components
   |  |  |  └─ Header.vue
   |  |  ├─ store
+  |  |  |  ├─ loading.js
   |  |  |  ├─ auth.js
   |  |  |  └─ index.js
   |  |  ├─ pages
   |  |  |  ├─ Home.vue
   |  |  |  ├─ Login.vue
   |  |  |  └─ Reset.vue
+  |  |  ├─ css
+  |  |  |  ├─ reset.css
+  |  |  |  └─ style.css
+  |  |  ├─ sass
+  |  |  |  ├─ snow
+  |  |  |  |   ├─ _inputs.scss
+  |  |  |  |   └─ _variables.scss
+  |  |  |  └─ main.scss
   |  |  ├─ bootstrap.js
   |  |  └─ app.js
   |  └─ views
   |      └─ index.blade.php
   └─ webpack.mix.js

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

## フォントアイコンを使う

今回は`vue-i18n`を使用する  

[vue-fontawesome](https://github.com/FortAwesome/vue-fontawesome)

### インストール

`vue-i18n`をインストール

```bash:terminal

    npm i @fortawesome/fontawesome-svg-core
    npm i @fortawesome/free-solid-svg-icons
    npm i @fortawesome/free-regular-svg-icons
    npm i @fortawesome/free-brands-svg-icons
    npm i @fortawesome/vue-fontawesome

```

### Vueで使う準備

```javascript:server\resources\js\app.js

    ...

+   /**
+    * fontawesome
+    * https://github.com/FortAwesome/vue-fontawesome
+    * http://l-lin.github.io/font-awesome-animation/
+    */
+
+   // コアのインポート
+   import { library } from "@fortawesome/fontawesome-svg-core";
+
+   // 無料で使えるフォントをインポート
+   import { fab } from "@fortawesome/free-brands-svg-icons";
+   import { far } from "@fortawesome/free-regular-svg-icons";
+   import { fas } from "@fortawesome/free-solid-svg-icons";
+
+   // コンポネントをインポート
+   import { FontAwesomeIcon, FontAwesomeLayers, FontAwesomeLayersText } from "@fortawesome/vue-fontawesome";
+
+   // ライブラリに追加
+   library.add(fas, far, fab);
+
+   // コンポーネントを名前を指定して追加
+   // 名前は自由にきめてOK
+   Vue.component("FAIcon", FontAwesomeIcon);
+   Vue.component('FALayers', FontAwesomeLayers);
+   Vue.component('FAText', FontAwesomeLayersText);
+   ...

```

### 使用例

```HTML:example

    <!-- アイコンの指定 -->
    <FAIcon icon="coffee" />
    <FAIcon :icon="['fas', 'coffee']" />

    <!-- サイズ -->
    <!--  xs, sm, lg, 2x, 3x ... 10x  -->
    <FAIcon :icon="['fas', 'coffee']" size="xs" />
    <FAIcon :icon="['fas', 'coffee']" size="6x" />

    <!-- 等幅フォント -->
    <FAIcon :icon="['fas', 'coffee']" fixed-width />

    <!-- 回転 -->
    <FAIcon :icon="['fas', 'coffee']" rotation="90" />

    <!-- フリップ -->
    <FAIcon :icon="['fas', 'coffee']" flip="horizontal" />
    <FAIcon :icon="['fas', 'coffee']" flip="vertical" />
    <FAIcon :icon="['fas', 'coffee']" flip="both" />

    <!-- アニメーション -->
    <FAIcon icon="spinner" spin />
    <FAIcon icon="spinner" pulse />

    <!-- 枠線 -->
    <FAIcon :icon="['fas', 'coffee']" border />

    <!-- プル -->
    <FAIcon :icon="['fas', 'coffee']" pull="left" />
    <FAIcon :icon="['fas', 'coffee']" pull="right" />

    <!-- 色の反転 -->
    <FAIcon :icon="['fas', 'coffee']" inverse :style="{'background-color': 'black'}" />

    <!-- トランスフォーム Scaling -->
    <FAIcon :icon="['fas', 'coffee']" transform="shrink-8" />
    <FAIcon :icon="['fas', 'coffee']" />
    <FAIcon :icon="['fas', 'coffee']" transform="grow-8" />

    <!-- トランスフォーム Positioning -->
    <FAIcon :icon="['fas', 'coffee']" transform="shrink-8" :style="{'background-color': 'rgb(0 0 0 / 25%)'}" />
    <FAIcon :icon="['fas', 'coffee']" transform="shrink-8 up-3 left-3" :style="{'background-color': 'rgb(0 0 0 / 25%)'}" />
    <FAIcon :icon="['fas', 'coffee']" transform="shrink-8 down-3 right-3" :style="{'background-color': 'rgb(0 0 0 / 25%)'}" />

    <!-- トランスフォーム Positioning Flipping -->
    <FAIcon :icon="['fas', 'coffee']" transform="rotate-90" />
    <FAIcon :icon="['fas', 'coffee']" transform="rotate-180" />
    <FAIcon :icon="['fas', 'coffee']" :transform="{rotate: '270'}" />

    <!-- トランスフォーム Positioning Flipping -->
    <FAIcon :icon="['fas', 'coffee']" transform="flip-v" />
    <FAIcon :icon="['fas', 'coffee']" transform="flip-h" />
    <FAIcon :icon="['fas', 'coffee']" transform="flip-v flip-h" />

    <!-- マスキング -->
    <FAIcon :icon="['fas', 'coffee']" :mask="['fas', 'circle']" transform="shrink-8" size="2x" />

    <!-- レイヤリング -->
    <FALayers class="fa-2x">
        <FAIcon :icon="['fas', 'circle']" />
        <FAIcon :icon="['fas', 'times']" transform="shrink-3" inverse />
    </FALayers>

    <FALayers class="fa-2x">
        <FAIcon :icon="['fas', 'play']" transform="rotate--90 grow-2" />
        <FAIcon :icon="['fas', 'sun']" inverse transform="shrink-10 up-2" />
        <FAIcon :icon="['fas', 'moon']" inverse transform="shrink-11 down-4.2 left-4" />
        <FAIcon :icon="['fas', 'star']" inverse transform="shrink-11 down-4.2 right-3" />
    </FALayers>

    <FALayers class="fa-2x">
        <FAIcon icon="calendar"/>
        <FAText class="fa-inverse" transform="shrink-8 down-3.5" :value="30" />
    </FALayers>

    <FALayers class="fa-2x">
        <FAIcon icon="certificate"/>
        <FAText transform="shrink-11.5 rotate--30" value="NEW" :style="{'color': 'white'}" />
    </FALayers>

    <FALayers class="fa-4x">
        <FAIcon icon="envelope"/>
        <FAText counter value="21" />
    </FALayers>
    <FALayers class="fa-4x">
        <FAIcon icon="envelope"/>
        <FAText counter value="22" position="bottom-left" style="background:Tomato" />
    </FALayers>

```

## 複雑なアニメーションに対応

### インストール

「font-awesome-animation」をインストール  

[font-awesome-animation](http://l-lin.github.io/font-awesome-animation/)

```bash:terminal

    npm i font-awesome-animation

```

### Vueで使う準備

`server\webpack.mix.js`に追加して`public/css/app.css`を作成させる

```javascript:server\webpack.mix.js

    const mix = require('laravel-mix');

        mix.js("resources/js/app.js", "public/js")
        // 配列で渡したcssを「public/css/app.css」にまとめる
+       .styles([
+           'node_modules/font-awesome-animation/dist/font-awesome-animation.min.css'
+       ], 'public/css/app.css');

        ...

```

`server\resources\views\index.blade.php`にスタイルシートを追加

```javascript:server\resources\views\index.blade.php

    <!doctype html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <script src="{{ mix('js/app.js') }}" defer></script>
+   <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    </head>
    <body>
    <div id="app"></div>
    </body>
    </html>

```

「watch」で監視できないのでもう一度

```bash:terminal

    npm run watch

```

### 使用例

モーション
* faa-wrench
* faa-ring
* faa-horizontal
* faa-vertical
* faa-flash
* faa-bounce
* faa-spin
* faa-float
* faa-pulse
* faa-shake
* faa-tada
* faa-passing
* faa-passing
* faa-burst
* faa-falling
* faa-rising

モード
* animated
* animated-hover

スピード
* faa-fast
* faa-slow

親要素でホバー
* faa-parent

```HTML:example

    <!-- 基本 -->
    <FAIcon icon="coffee" class="faa-wrench animated" />

    <!-- 速度 -->
    <FAIcon icon="coffee" class="faa-wrench animated faa-slow" />
    <FAIcon icon="coffee" class="faa-wrench animated faa-fast" />

    <!-- ホバー -->
    <FAIcon icon="coffee" class="faa-wrench animated-hover" />

    <!-- 親要素でホバー -->
    <div class="faa-parent animated-hover">
        <FAIcon icon="coffee" class="faa-wrench" />coffee
    </div>

```

## フォームを使いやすくする

### インストール

`Vue Formulate`をインストール

```bash:terminal

    npm i @braid/vue-formulate

```

### Vueで使う準備

```javascript:server\resources\js\app.js

    ...

+   /*
+    * VueFormulate
+    * https://vueformulate.com/guide/
+    * https://vueformulate.com/guide/internationalization/#registering-a-locale
+    * https://vueformulate.com/guide/custom-inputs/#custom-types
+    */
+
+   // コアをインポート
+   import VueFormulate from "@braid/vue-formulate";
+   // 言語をインポート
+   import { en, ja } from "@braid/vue-formulate-i18n";
+
+   // 宣言
+   Vue.use(VueFormulate, {
+       // 使用するプラグイン
+       plugins: [en, ja],
+       // グローバルに使う独自ルール
+       rules: {
+           // ex
+           foobar: ({ value }) => ["foo", "bar"].includes(value)
+       }
+   });

    ...

```

### テーマを入れる

デフォルトテーマを入れる
[https://vueformulate.com/guide/theming/#default-theme](https://vueformulate.com/guide/theming/#default-theme)

あとでテーマをカスタマイズしたいので`server\node_modules\@braid\vue-formulate\themes\snow`フォルダをコピーして
`server\resources\sass`の中に入れる

`server\resources\sass\main.scss`を作成してインポートする

```css:server\resources\sass\main.scss

    @import '../../node_modules/@braid/vue-formulate/themes/snow/snow.scss';

```

`server\webpack.mix.js`を編集

```javascript:server\webpack.mix.js

    const mix = require('laravel-mix');

        mix.js("resources/js/app.js", "public/js")
+       .sass('resources/sass/main.scss', 'public/js')
        // 配列で渡したcssを「public/css/app.css」にまとめる
        .styles([
            'node_modules/font-awesome-animation/dist/font-awesome-animation.min.css'
        ], 'public/css/app.css');

    ...

```

------------------------------------------------------------------------------------------

## Api通信中のステータスをvuexで管理

### ストアを作成する

`server\resources\js\store\loading.js`としてローディングストアを作成

```javascript:server\resources\js\store\loading.js

    /*
     * ステート（データの入れ物）
     */
    const state = {
        status: false
    };

    /*
     * ミューテーション（同期処理）
     */
    const mutations = {
        setStatus(state, status) {
            state.status = status;
        }
    };

    /*
     * エクスポート
     */
    export default {
        namespaced: true,
        state,
        mutations
    };

```

ストアインデックス`server\resources\js\store\index.js`に追加

```javascript:server\resources\js\store\index.js

    import Vue from "vue";
    import Vuex from "vuex";

    // 各ストアのインポート
    import auth from "./auth";
    import error from "./error";
    import message from "./message";
+   import loading from './loading'

    Vue.use(Vuex);

    const store = new Vuex.Store({
        modules: {
            // 各ストアと登録
            auth,
            error,
            message,
+           loading,
        }
    });

    export default store;

```

------------------------------------------------------------------------------------------

### 通信を状態をわかるようにする

Api通信中のみステータスを`true`にするため`server\resources\js\bootstrap.js`を編集

ついでにLaravelのApiはすべて「/api/xxx」となるのでaxiosに`baseURL`を設定しておく

```javascript:server\resources\js\bootstrap.js

    // クッキーを簡単に扱えるモジュールをインポート
    import Cookies from "js-cookie";
    // ストアをインポート
+   import store from "./store";

    /*
    * lodash
    * あると便利のなのでそのままおいておく
    */
    window._ = require("lodash");
    
    /*
    * axios
    * Ajax通信にはこれを使う
    */
    window.axios = require("axios");

    // Ajaxリクエストであることを示すヘッダーを付与する
    window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

    // ベースURLの設定
+   window.axios.defaults.baseURL = 'api/';

    // requestの設定
    window.axios.interceptors.request.use(config => {

        // ローディングストアのステータスをTRUE
+       store.commit("loading/setStatus", true);

        // クッキーからトークンを取り出す
        const xsrfToken = Cookies.get("XSRF-TOKEN");
        // ヘッダーに添付する
        config.headers["X-XSRF-TOKEN"] = xsrfToken;
        return config;
    });

    // responseの設定
    // API通信の成功、失敗でresponseの形が変わるので、どちらとも response にレスポンスオブジェクトを代入
    window.axios.interceptors.response.use(
        // 成功時の処理
        response => {
            // ローディングストアのステータスをFALSE
+           store.commit("loading/setStatus", false);
            return response;
        },
        // 失敗時の処理
        error => {
            // ローディングストアのステータスをFALSE
+           store.commit("loading/setStatus", false);
            return error.response || error;
        }
    );

    ...

```

ベースURLを設定したのでaxiosのURLを修正

`server\resources\js\components\Header.vue`を修正

```javascript:server\resources\js\components\Header.vue

    ...

    export default {
        data() {
            ...
        },
        computed: {
            ...
        },
        storage: {
            ...
        },
        methods: {
            async logout() {
                ...
            },
            // 言語切替メソッド
            changeLang() {
                // ローカルストレージに「language」をセット
                this.$storage.set("language", this.selectedLang);
                // Apiリクエスト 言語を設定
-               axios.get(`/api/set-lang/${this.selectedLang}`);
+               axios.get(`set-lang/${this.selectedLang}`);
                // Vue i18n の言語を設定
                this.$i18n.locale = this.selectedLang;
            }
        },
        created() {
            // ローカルストレージから「language」を取得
            this.selectedLang = this.$storage.get("language");

            // サーバ側をクライアント側に合わせる

            // storageLangがない場合
            if (!this.selectedLang) {
                // ブラウザーの言語を取得
                const defaultLang = Helper.getLanguage();
                // ローカルストレージに「language」をセット
                this.$storage.set("language", defaultLang);
                // Apiリクエスト 言語を設定
-               axios.get(`/api/set-lang/${defaultLang}`);
+               axios.get(`set-lang/${defaultLang}`);
            }
            // ある場合はサーバ側をクライアント側に合わせる
            else {
-               axios.get(`/api/set-lang/${this.selectedLang}`);
+               axios.get(`set-lang/${this.selectedLang}`);
            }
        }
    };

```

`server\resources\js\store\auth.js`を修正

```javascript:server\resources\js\store\auth.js

    ...

    /*
     * アクション（非同期処理）
     */
    const actions = {
        /*
        * loginのアクション
        */
        async login(context, data) {
            // apiStatusのクリア
            context.commit("setApiStatus", null);

            // Apiリクエスト
-           const response = await axios.post("/api/login", data);
+           const response = await axios.post("login", data);

            ...
        },
        /*
        * registerのアクション
        */
        async register(context, data) {
            // apiStatusのクリア
            context.commit("setApiStatus", null);

            // Apiリクエスト
-           const response = await axios.post("/api/register", data);
+           const response = await axios.post("register", data);

            ...
        },
        /*
        * logoutのアクション
        */
        async logout(context) {
            // apiStatusのクリア
            context.commit("setApiStatus", null);

            // Apiリクエスト
-           const response = await axios.post("/api/logout", data);
+           const response = await axios.post("logout");

            ...
        },
        /*
        * forgotのアクション
        */
        async forgot(context, data) {
            // apiStatusのクリア
            context.commit("setApiStatus", null);

            // Apiリクエスト
-           const response = await axios.post("/api/forgot", data);
+           const response = await axios.post("forgot", data);

            ...
        },
        /*
        * resetのアクション
        */
        async reset(context, data) {
            // apiStatusのクリア
            context.commit("setApiStatus", null);

            // Apiリクエスト
-           const response = await axios.post("/api/reset", data);
+           const response = await axios.post("reset", data);

            ...
        },
        /*
        * カレントユーザのアクション
        */
        async currentUser(context) {
            // apiStatusのクリア
            context.commit("setApiStatus", null);

            // Apiリクエスト
-           const response = await axios.post("/api/user", data);
+           const response = await axios.get("user");

            ...
        }
    };
    ...

```

------------------------------------------------------------------------------------------

## 上記で作成したものを実装

### コンポネントに埋め込み

`server\resources\js\pages\Login.vue`に埋め込んでいく

```javascript:server\resources\js\pages\Login.vue

    <template>
-       <div class="container">
+       <div class="page">

+           <h1>{{ $t('word.login') }}</h1>

            <!-- tabs -->
            ...
            <!-- /tabs -->

            <!-- login -->
-           <section class="login" v-show="tab === 1">
+           <section class="login panel" v-show="tab === 1">
-               <h2>{{ $t('word.login') }}</h2>

                <!-- errors -->
                ...
                <!--/ errors -->

                <!-- @submitで login method を呼び出し -->
-               <form @submit.prevent="login">
-                   <div>{{ $t('word.email') }}</div>
-                   <div>
-                       <!-- v-modelでdataをバインド -->
-                       <input type="email" v-model="loginForm.email" />
-                   </div>
-                   <div>{{ $t('word.password') }}</div>
-                   <div>
-                       <input type="password" v-model="loginForm.password" />
-                   </div>
-                   <div>
-                       <button type="submit">{{ $t('word.login') }}</button>
-                   </div>
-               </form>

+               <FormulateForm v-model="loginForm" @submit="login">
+                   <FormulateInput
+                       name="email"
+                       type="email"
+                       :label="$t('word.email')"
+                       :validation-name="$t('word.email')"
+                       validation="required|email"
+                       :placeholder="$t('word.email')"
+                   />
+                   <FormulateInput
+                       name="password"
+                       type="password"
+                       :label="$t('word.password')"
+                       :validation-name="$t('word.password')"
+                       validation="required|min:8"
+                       "placeholder="$t('word.password')"
+                   />
+                   <FormulateInput type="submit" :disabled="loadingStatus">
+                       {{ $t('word.login') }}
+                       <FAIcon v-if="loadingStatus" :icon="['fas', 'spinner']" pulse fixed-width/>
+                   </FormulateInput>
+               </FormulateForm>

                <h2>{{ $t('word.Socialite') }}</h2>
-               <a class="button" href="/login/twitter" title="twitter">twitter</a>
+               <a class="button" href="/login/twitter" title="twitter">
+                       <FAIcon :icon="['fab', 'twitter']" size="2x" :class="['faa-tada', 'animated-hover']"/>
+               </a>
                
            </section>
            <!-- /login -->

            <!-- register -->
-           <section class="register" v-show="tab === 2">
+           <section class="register panel" v-show="tab === 2">
-               <h2>{{ $t('word.register') }}</h2>
                <!-- errors -->
                <div v-if="registerErrors" class="errors">
                    <ul v-if="registerErrors.name">
                        <li v-for="msg in registerErrors.name" :key="msg">{{ msg }}</li>
                    </ul>
                    <ul v-if="registerErrors.email">
                        <li v-for="msg in registerErrors.email" :key="msg">{{ msg }}</li>
                    </ul>
                    <ul v-if="registerErrors.password">
                        <li v-for="msg in registerErrors.password" :key="msg">{{ msg }}</li>
                    </ul>
                </div>
                <!--/ errors -->
-               <form @submit.prevent="register">
-                   <div>{{ $t('word.name') }}</div>
-                   <div>
-                       <input type="text" v-model="registerForm.name" />
-                   </div>
-                   <div>{{ $t('word.email') }}</div>
-                   <div>
-                       <input type="email" v-model="registerForm.email" />
-                   </div>
-                   <div>{{ $t('word.password') }}</div>
-                   <div>
-                       <input type="password" v-model="registerForm.password" />
-                   </div>
-                   <div>{{ $t('word.password_confirmation') }}</div>
-                   <div>
-                       <input type="password" v-model="registerForm.password_confirmation" />
-                   </div>
-                   <div>
-                       <button type="submit">{{ $t('word.register') }}</button>
-                   </div>
-               </form>

+               <FormulateForm v-model="registerForm" @submit="register">
+                   <FormulateInput
+                       name="name"
+                       type="name"
+                       :label="$t('word.name')"
+                       :validation-name="$t('word.name')"
+                       validation="required|max:50"
+                       :placeholder="$t('word.name')"
+                   />
+                   <FormulateInput
+                       name="email"
+                       type="email"
+                       :label="$t('word.email')"
+                       :validation-name="$t('word.email')"
+                       validation="required|email"
+                       :placeholder="$t('word.email')"
+                   />
+                   <FormulateInput
+                       name="password"
+                       type="password"
+                       :label="$t('word.password')"
+                       :validation-name="$t('word.password')"
+                       validation="required|min:8"
+                       :placeholder="$t('word.password')"
+                   />
+                   <FormulateInput
+                       name="password_confirmation"
+                       type="password"
+                       :label="$t('word.password_confirmation')"
+                       :validation-name="$t('word.password_confirmation')"
+                       validation="required|min:8"
+                       :placeholder="$t('word.password_confirmation')"
+                   />
+                   <FormulateInput type="submit" :disabled="loadingStatus">
+                       {{ $t('word.register') }}
+                       <FAIcon v-if="loadingStatus" :icon="['fas', 'spinner']" pulse fixed-width/>
+                   </FormulateInput>
+               </FormulateForm>
            </section>
            <!-- /register -->

            <!-- forgot -->
-           <section class="forgot" v-show="tab === 3">
+           <section class="forgot panel" v-show="tab === 3">
-               <h2>forgot</h2>
                <!-- errors -->
                <div v-if="forgotErrors" class="errors">
                    <ul v-if="forgotErrors.email">
                        <li v-for="msg in forgotErrors.email" :key="msg">{{ msg }}</li>
                    </ul>
                </div>
                <!--/ errors -->
-               <form @submit.prevent="forgot">
-                   <div>{{ $t('word.email') }}</div>
-                   <div>
-                       <input type="email" v-model="forgotForm.email" />
-                   </div>
-                   <div>
-                       <button type="submit">{{ $t('word.send') }}</button>
-                   </div>
-               </form>

+               <FormulateForm v-model="forgotForm" @submit="forgot">
+                   <FormulateInput
+                       name="email"
+                       type="email"
+                       :label="$t('word.email')"
+                       :validation-name="$t('word.email')"
+                       validation="required|email"
+                       :placeholder="$t('word.email')"
+                   />
+                   <FormulateInput type="submit" :disabled="loadingStatus">
+                       {{ $t('word.send') }}
+                       <FAIcon v-if="loadingStatus" :icon="['fas', 'spinner']" pulse fixed-width/>
+                   </FormulateInput>
+               </FormulateForm>
            </section>
            <!-- /forgot -->
        </div>
    </template>

    <script>
    export default {
        data() {
            ...
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
            forgotErrors() {
                return this.$store.state.auth.forgotErrorMessages;
            },
+           // loadingストアのstatus
+           loadingStatus() {
+               return this.$store.state.loading.status;
+           }
        },

        ...

    };
    </script>

```

```javascript:server\resources\js\pages\Reset.vue

    <template>
        <div class="container--small">
-           <h2>{{ $t('word.password_reset') }}</h2>
+           <h1>{{ $t('word.password_reset') }}</h1>

-           <div class="panel">
+           <section class="page" >
-               <form class="form" @submit.prevent="reset">
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

-                   <div>
-                       <input type="password" v-model="resetForm.password" />
-                   </div>
-                   <div>
-                       <input type="password" v-model="resetForm.password_confirmation" />
-                   </div>
-                   <div>
-                       <button type="submit">{{ $t('word.reset') }}</button>
-                   </div>
-               </form>

-           <FormulateForm v-model="resetForm" @submit="reset">
-               <FormulateInput
-                   name="password"
-                   type="password"
-                   :label="$t('word.password')"
-                   :validation-name="$t('word.password')"
-                   validation="required|min:8"
-                   :placeholder="$t('word.password')"
-               />
-               <FormulateInput
-                   name="password_confirmation"
-                   type="password"
-                   :label="$t('word.password_confirmation')"
-                   :validation-name="$t('word.password_confirmation')"
-                   validation="required|min:8"
-                   :placeholder="$t('word.password_confirmation')"
-               />
-               <FormulateInput type="submit" :disabled="loadingStatus">
-                   {{ $t('word.reset') }}
-                   <FAIcon v-if="loadingStatus" :icon="['fas', 'spinner']" pulse fixed-width />
-               </FormulateInput>
-           </FormulateForm>
-           </div>
+           </section>
        </div>
    </template>

    <script>
    import Cookies from "js-cookie";

    export default {
        // vueで使うデータ
        data() {
            ...
        },
        computed: {
            // authストアのapiStatus
            apiStatus() {
                return this.$store.state.auth.apiStatus;
            },
            // authストアのresetErrorMessages
            resetErrors() {
                return this.$store.state.auth.resetErrorMessages;
            },
+           // loadingストアのstatus
+           loadingStatus() {
+               return this.$store.state.loading.status;
+           }
        },

        ...
    };
    </script>

```

`server\resources\js\components\Header.vue`を修正

```javascript:server\resources\js\components\Header.vue

    <template>
-       <header>
-           <!-- リンクを設定 -->
-           <RouterLink to="/">{{ $t('word.home') }}</RouterLink>
-           <RouterLink v-if="!isLogin" to="/login">{{ $t('word.login') }}</RouterLink>
-           <!-- ログインしている場合はusernameを表示 -->
-           <span v-if="isLogin">{{username}}</span>
-           <!-- クリックイベントにlogoutメソッドを登録 -->
-           <span v-if="isLogin" @click="logout">logout</span>
-           <!-- 言語切替 -->
-           <select v-model="selectedLang" @change="changeLang">
-               <option
-                   v-for="lang in langList"
-                   :value="lang.value"
-                   :key="lang.value"
-               >{{ $t(`word.${lang.text}`) }}</option>
-           </select>
-       </header>

+       <header class="header">
+           <RouterLink to="/">
+               <FAIcon :icon="['fas', 'home']" size="lg" />
+               {{ $t('word.home') }}
+           </RouterLink>
+   
+           <RouterLink v-if="!isLogin" to="/login">
+               <FAIcon :icon="['fas', 'sign-in-alt']" size="lg" />
+               {{ $t('word.login') }}
+           </RouterLink>
+   
+           <span v-if="isLogin">
+               <FAIcon :icon="['fas', 'child']" size="lg" />
+               {{username}}
+           </span>
+   
+           <span v-if="isLogin" @click="logout" class="button">
+               <FAIcon :icon="['fas', 'sign-out-alt']" size="lg" />
+               {{ $t('word.logout') }}
+           </span>
+
+           <FormulateInput @input="changeLang" v-model="selectedLang" :options="langList" type="select" class="header-lang" />
        </header>
    </template>

    ...

```
`server\resources\js\pages\Home.vue`を修正

```
    <template>
-   <div class="container">
+   <div class="page">

        <h1>{{ $t('word.home') }}</h1>

    </div>
    </template>

```

### スタイルを追加

リセットスタイル`server\resources\css\reset.css`を作成する

```css:server\resources\css\reset.css

    html,
    body {
        height: 100%;

    }

    /* Box sizingの定義 */
    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    /* デフォルトのpaddingを削除 */
    ul[class],
    ol[class] {
        padding: 0;
    }

    /* デフォルトのmarginを削除 */
    body,
    h1,
    h2,
    h3,
    h4,
    p,
    ul[class],
    ol[class],
    li,
    figure,
    figcaption,
    blockquote,
    dl,
    dd {
        margin: 0;
        font-size: unset;
        font-weight: normal;
    }

    /* bodyのデフォルトを定義 */

    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
            "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji",
            "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
        min-height: 100vh;
        scroll-behavior: smooth;
        text-rendering: optimizeSpeed;
        line-height: 1.5;
    }

    /* class属性を持つul、ol要素のリストスタイルを削除 */
    ul[class],
    ol[class] {
        list-style: none;
    }

    /* classを持たない要素はデフォルトのスタイルを取得 */
    a:not([class]) {
        text-decoration-skip-ink: auto;
    }

    /* img要素の扱いを簡単にする */
    img {
        max-width: 100%;
        display: block;
    }

    /* article要素内の要素に自然な流れとリズムを定義 */
    article > * + * {
        margin-top: 1em;
    }

    /* inputやbuttonなどのフォントは継承を定義 */
    input,
    button,
    textarea,
    select {
        font: inherit;
    }

    button {
        border: 0;
        outline: none;
    }

    /* 見たくない人用に、すべてのアニメーションとトランジションを削除 */

    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
    }

```


```css:server\resources\css\style.css

    h1 {
        margin: 0;
        padding: 0;
        color: dodgerblue;
        border-bottom: solid thin dodgerblue;
        margin: 2rem 0;
        font-weight: bold;
    }

    .wrapper {
        width: clamp(600px, 80%, 1000px);
        margin: 5vh auto;
        border: solid 3px dodgerblue;
        min-height: 90vh;
    }

    .header {
        padding: 1rem 1rem 0;
        display: flex;
    }

    .header>* {
        margin: 1rem;
        text-decoration: none;
        color: gray;
    }

    .header>*:last-child {
        margin-left: auto;
    }

    .header .router-link-exact-active {
        pointer-events: none;
        color: dodgerblue;
    }

    .container {
        padding: 2rem;
    }

    .tab {
        padding: 0;
        display: flex;
        list-style: none;
        transform: translateY(1px);
    }

    .tab__item {
        background-color: lightgrey;
        border: 1px solid gray;
        border-radius: 0.3rem 0.3rem 0 0;
        padding: 0.5rem 1rem 0.4rem;
        margin-right: 0.5rem;
        cursor: pointer;
    }

    .tab__item--active {
        background-color: white;
        border-bottom: 1px solid white;
    }

    .panel {
        border: solid 1px gray;
        padding: 2rem;
    }

    .button {
        cursor: pointer;
    }

```

`server\webpack.mix.js`を編集

```javascript:server\webpack.mix.js

    const mix = require('laravel-mix');

        mix.js("resources/js/app.js", "public/js")
        .sass('resources/sass/main.scss', 'public/js')
        // 配列で渡したcssを「public/css/app.css」にまとめる
        .styles([
+           'resources/css/reset.css',
+           'resources/css/style.css',
            'node_modules/font-awesome-animation/dist/font-awesome-animation.min.css'
        ], 'public/css/app.css');

    ...


これでUIが使いやすくなったと思います。


// <!-- [Laravel mix vue No.11 - ](https://www.aska-ltd.jp/jp/blog/) -->

<script src="https://www.aska-ltd.jp/js/blog.js"></script>
<link href="https://www.aska-ltd.jp/css/blog.css" rel="stylesheet">