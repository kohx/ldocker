
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
   └─ resources
      └─ js
         ├─ components
         |  └─ Header.vue
         ├─ pages
         |  ├─ Home.vue
         |  ├─ Login.vue
         |  └─ Reset.vue
+        ├─ lang
+        |  ├─ index.js
+        |  ├─ En.js
+        |  └─ Js.js
         └─ app.js

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

### インストール

`vue-i18n`をインストール

```bash:terminal

    npm i --save @fortawesome/fontawesome-svg-core
    npm i --save @fortawesome/free-solid-svg-icons
    npm i --save @fortawesome/free-regular-svg-icons
    npm i --save @fortawesome/free-brands-svg-icons
    npm i --save @fortawesome/vue-fontawesome

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

```javascript:example

// messages word
<p>{{ $t('word.hello') }}</p>

// messages sentence
<p>{{ $t('sentence.{msg} world!', {msg: 'hello'}) }}</p>

// datetime full
<p>{{ $d(new Date(), 'full') }}</p>

// datetime day
<p>{{ $d(new Date(), 'full') }}</p>

// number currency 
<p>{{ $n(100, 'currency') }}</p>

// vueの中で取得する場合
this.$i18n.tc('sentence.sentence.{msg} world!')；

```



### コンポネントに埋め込み

`server\resources\js\components\Header.vue`を修正

```javascript:server\resources\js\components\Header.vue

    <template>
        <header>
            <!-- リンクを設定 -->
-           <RouterLink to="/">home</RouterLink>
+           <RouterLink to="/">{{ $t('word.home') }}</RouterLink>
-           <RouterLink v-if="!isLogin" to="/login">login</RouterLink>
+           <RouterLink v-if="!isLogin" to="/login">{{ $t('word.login') }}</RouterLink>
            <!-- ログインしている場合はusernameを表示 -->
            <span v-if="isLogin">{{username}}</span>
            <!-- クリックイベントにlogoutメソッドを登録 -->
-           <span v-if="isLogin" @click="logout">logout</span>
+           <span v-if="isLogin" @click="logout">{{ $t('word.logout') }}</span>
            <!-- 言語切替 -->
            <select v-model="selectedLang" @change="changeLang">
-               <option v-for="lang in langList" :value="lang.value" :key="lang.value">{{ lang.text }}</option>\
+               <option v-for="lang in langList" :value="lang.value" :key="lang.value">{{ $t(`word.${lang.text}`) }}</option>
            </select>
        </header>
    </template>

    <script>
    import Cookies from "js-cookie";
    import Helper from "../helper";

    export default {
+       data() {
+           return {
+               // 言語選択オプション
+               langList: [
+                   { value: "en", text: "english" },
+                   { value: "ja", text: "japanese" }
+               ],
+               // 選択された言語
+               selectedLang: "en"
+           };
+       },
        computed: {
            ...
        },
        storage: {
            ...
        },
        methods: {
            // ログアウトメソッド
            async logout() {
                ...
            },
            // 言語切替
            changeLang() {
                // ローカルストレージに「language」をセット
                this.$storage.set("language", this.selectedLang);
                // Apiリクエスト 言語を設定
                axios.get(`/api/set-lang/${this.selectedLang}`);
                // Vue i18n の言語を設定
+               this.$i18n.locale = this.selectedLang;
            }
        },
        created() {
           ...
        }
    };
    </script>

```

### ページに埋め込み

`server\resources\js\pages\Home.vue`を修正

```javascript:server\resources\js\pages\Home.vue

    <template>
      <div class="container">

-       <h1>home</h1>
+       <h1>{{ $t('word.home') }}</h1>

      </div>
    </template>

```

`server\resources\js\pages\Login.vue`を修正

```javascript:server\resources\js\pages\Login.vue

    <template>
        <div class="container">
            <!-- tabs -->
            <ul class="tab">
-               <li class="tab__item" :class="{'tab__item--active': tab === 1 }" @click="tab = 1">Login</li>
+               <li class="tab__item" :class="{'tab__item--active': tab === 1 }" @click="tab = 1">{{ $t('word.login') }}</li>
                <li
                    class="tab__item"
                    :class="{'tab__item--active': tab === 2 }"
                    @click="tab = 2"
-               >Register</li>
+               >{{ $t('word.register') }}</li>
                <li
                    class="tab__item"
                    :class="{'tab__item--active': tab === 3 }"
                    @click="tab = 3"
-               >Forgot password ?</li>
+               >{{ $t('word.forgot_password') }}</li>
            </ul>
            <!-- /tabs -->

            <!-- login -->
            <section class="login" v-show="tab === 1">
-               <h2>Login</h2>
+               <h2>{{ $t('word.login') }}</h2>

                <!-- errors -->
                <div v-if="loginErrors" class="errors">
                    <ul v-if="loginErrors.email">
                        <li v-for="msg in loginErrors.email" :key="msg">{{ msg }}</li>
                    </ul>
                    <ul v-if="loginErrors.password">
                        <li v-for="msg in loginErrors.password" :key="msg">{{ msg }}</li>
                    </ul>
                </div>
                <!--/ errors -->

                <!-- @submitで login method を呼び出し -->
                <!-- @submitイベントリスナに prevent をつけるとsubmitイベントによってページがリロードさない -->
                <form @submit.prevent="login">
-                   <div>Email</div>
+                   <div>{{ $t('word.email') }}</div>
                    <div>
                        <!-- v-modelでdataをバインド -->
                        <input type="email" v-model="loginForm.email" />
                    </div>
-                   <div>Password</div>
+                   <div>{{ $t('word.password') }}</div>
                    <div>
                        <input type="password" v-model="loginForm.password" />
                    </div>
                    <div>
-                       <button type="submit">login</button>
+                       <button type="submit">{{ $t('word.login') }}</button>
                    </div>
                </form>

-               <h2>Socialite</h2>
+               <h2>{{ $t('word.Socialite') }}</h2>
                <a class="button" href="/login/twitter" title="twitter">
                    twitter
                </a>
                <a class="button" href="/login/facebook" title="facebookfacebook">
                    facebook
                </a>
                <a class="button" href="/login/google" title="google">
                    google
                </a>
                <a class="button" href="/login/github" title="github">
                    github
                </a>
            </section>
            <!-- /login -->

            <!-- register -->
            <section class="register" v-show="tab === 2">
-               <h2>register</h2>
+               <h2>{{ $t('word.register') }}</h2>
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
                <form @submit.prevent="register">
-                   <div>Name</div>
+                   <div>{{ $t('word.name') }}</div>
                    <div>
                        <input type="text" v-model="registerForm.name" />
                    </div>
-                   <div>Email</div>
+                   <div>{{ $t('word.email') }}</div>
                    <div>
                        <input type="email" v-model="registerForm.email" />
                    </div>
-                   <div>Password</div>
+                   <div>{{ $t('word.password') }}</div>
                    <div>
                        <input type="password" v-model="registerForm.password" />
                    </div>
-                   <div>Password confirmation</div>
+                   <div>{{ $t('word.password_confirmation') }}</div>
                    <div>
                        <input type="password" v-model="registerForm.password_confirmation" />
                    </div>
                    <div>
-                       <button type="submit">register</button>
+                       <button type="submit">{{ $t('word.register') }}</button>
                    </div>
                </form>
            </section>
            <!-- /register -->

            <!-- forgot -->
            <section class="forgot" v-show="tab === 3">
-               <h2>forgot</h2>
+               <h2>{{ $t('word.forgot_password') }}</h2>
                <!-- errors -->
                <div v-if="forgotErrors" class="errors">
                    <ul v-if="forgotErrors.email">
                        <li v-for="msg in forgotErrors.email" :key="msg">{{ msg }}</li>
                    </ul>
                </div>
                <!--/ errors -->
                <form @submit.prevent="forgot">
-                   <div>Email</div>
+                   <div>{{ $t('word.email') }}</div>
                    <div>
                        <input type="email" v-model="forgotForm.email" />
                    </div>
                    <div>
-                       <button type="submit">send</button>
+                       <button type="submit">{{ $t('word.send') }}</button>
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
            ...
        },
        computed: {
            ...
        },
        methods: {
            async login() {
                ...
            },
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
-                       content: "認証メールを送りました。",
                        // メッセージストアはサーバからのメッセージもあるので「i18n.tc」でコード内で翻訳しておく
+                       content: this.$i18n.tc("sentence.sent_verification_email"),
                        timeout: 10000
                    });
                    // AUTHストアのエラーメッセージをクリア
                    this.clearError();
                    // フォームをクリア
                    this.clearForm();
                }
            },
            /*
            * forgot
            */
            async forgot() {
                // authストアのforgotアクションを呼び出す
                await this.$store.dispatch("auth/forgot", this.forgotForm);
                if (this.apiStatus) {
                    // show message
                    this.$store.commit("message/setContent", {
-                       content: "パスワードリセットメールを送りました。",
                        // メッセージストアはサーバからのメッセージもあるので「i18n.tc」でコード内で翻訳しておく
+                       content: this.$i18n.tc('sentence.sent_password_reset_email'),
                        timeout: 10000
                    });
                    // AUTHストアのエラーメッセージをクリア
                    this.clearError();
                    // フォームをクリア
                    this.clearForm();
                }
            },
            clearError() {
                ...
            },
            clearForm() {
                ...
            }
        }
    }
    <script>
    ...

```

`server\resources\js\pages\Reset.vue`を修正

```javascript:server\resources\js\pages\Reset.vue

        <template>
            <div class="container--small">
-               <h2>password reset</h2>
+               <h2>{{ $t('word.password_reset') }}</h2>

                <div class="panel">
                    <!-- @submitで login method を呼び出し -->
                    <!-- @submitイベントリスナに reset をつけるとsubmitイベントによってページがリロードさない -->
                    <form class="form" @submit.prevent="reset">
                        <!-- errors -->
                        ...
                        <!--/ errors -->

                        <div>
                            <input type="password" v-model="resetForm.password" />
                        </div>
                        <div>
                            <input type="password" v-model="resetForm.password_confirmation" />
                        </div>
                        <div>
-                           <button type="submit">reset</button>
+                           <button type="submit">{{ $t('word.reset') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
        ...

```

### 翻訳ファイルに設定したものを追加

`server\resources\js\lang\En.js`を編集

```javascript:server\resources\js\lang\En.js

    const contents = {
        messages: {
            // 単語
            word: {
                hello: 'hello!',
+               home: 'Home',
+               login: 'Login',
+               logout: 'logout',
+               english: 'English',
+               japanese: 'Japanese',
+               register: 'Register',
+               forgot_password: 'Forgot Password ?',
+               email: 'Email',
+               password: 'Password',
+               Socialite: 'Socialite',
+               name: 'Name',
+               password_confirmation: 'Password Confirmation',
+               send: 'Send',
+               password_reset: 'Password Reset',
+               reset: 'Reset',
            },
            // メッセージ
            sentence: {
                '{msg} world!': '{msg} world！',
+               sent_verification_email: 'Sent verification email.',
+               sent_password_reset_email: 'Sent password reset email.',
            }
        },
        ...

```

`server\resources\js\lang\Ja.js`を編集

```javascript:server\resources\js\lang\En.js

    const contents = {
        messages: {
            // 単語
            word: {
                hello: 'こんにちは!',
+               home: 'ホーム',
+               login: 'ログイン',
+               logout: 'ログアウト',
+               english: '英語',
+               japanese: '日本語',
+               register: '登録',
+               forgot_password: 'パスワードをわすれましたか?',
+               email: 'メールアドレス',
+               password: 'パスワード',
+               Socialite: 'ソーシャルログイン',
+               name: '名前',
+               password_confirmation: 'パスワード確認',
+               send: '送信',
+               password_reset: 'パスワード リセット',
+               reset: 'リセット',
            },
            // メッセージ
            sentence: {
                '{msg} world!': '{msg} world！',
+               sent_verification_email: '確認メールを送信しました。',
+               sent_password_reset_email: 'パスワード再設定メールを送信しました。',
            }
        },
        ...

```

これでVue側でも多言語化が完了しました。

次は、UIがしょぼいのでfontawesomeでアイコンを入れていこうと思います。

<!-- [Laravel mix vue No.10 - Vue Font Awesome, Vue Formulate, etc](https://www.aska-ltd.jp/jp/blog/77) -->

<script src="https://www.aska-ltd.jp/js/blog.js"></script>
<link href="https://www.aska-ltd.jp/css/blog.css" rel="stylesheet">