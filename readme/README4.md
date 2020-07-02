<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

今回は前回作成したApiをUIからリクエストして、Login、Register、Logout機能を完成させます。  
認証状態の保持、メッセージなどにVuexを使います。

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

# Vuex - Vuexで状態管理

## サンプル
- このセクションを始める前  
[github ldocker 04](https://github.com/kohx/ldocker/tree/04)  
  
- 完成  
[github ldocker 05](https://github.com/kohx/ldocker/tree/05)

---

## フォルダ構成

```text

└─ server
   └─ resources
      └─ js
         ├─ components
         |  └─ Header.vue
         ├─ pages
         |  ├─ errors
         │  │  ├─ NotFound.veu
         │  │  └─ SystemError.vue
         │  ├─ Home.vue
         │  └─ Login.vue
+        ├─ store
+        │  ├─ index.js
+        │  ├─ auth.js
+        │  ├─ error.js
+        │  └─ message.js
         ├─ app.js
         ├─ App.vue
         ├─ bootstrap.js
+        ├─ const.js
         └─ router.js

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

# 使用するモジュールを追加
npm i js-cookie

# ホットリリード開始
npm run watch
```

### 使用するnpmモジュール

[js-cookie](https://www.npmjs.com/package/js-cookie)

---

## bootstrap.jsでaxiosApi通信の設定をする

### bootstrap.jsの修正

ApiでもCSRF対策をするためにAjax通信で使う「Axios」のヘッダーに「CSRFトークン」をクッキーから取り出して添付する

`server\resources\js\bootstrap.js`を以下と差し替える

```JS:server\resources\js\bootstrap.js
    // クッキーを簡単に扱えるモジュールをインポート
    import Cookies from "js-cookie";

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

+   // requestの設定
+   window.axios.interceptors.request.use(config => {
+       // クッキーからトークンを取り出す
+       const xsrfToken = Cookies.get("XSRF-TOKEN");
+       // ヘッダーに添付する
+       config.headers["X-XSRF-TOKEN"] = xsrfToken;
+       return config;
+   });

+   // responseの設定
+   // API通信の成功、失敗でresponseの形が変わるので、どちらとも response にレスポンスオブジェクトを代入
+   window.axios.interceptors.response.use(
+       // 成功時の処理
+       response => response,
+       // 失敗時の処理
+       error => error.response || error
+   );
```

### constの作成

`server\resources\js\const.js`を作成して使用するステータスコードを定義

```JS:server\resources\js\const.js
    /*
     * status cord
     */

    // response OK
    export const OK = 200; 
    // data created
    export const CREATED = 201; 
    // not found
    export const NOT_FOUND = 404; 
    // 認証切れの場合のレスポンスコード
    export const UNAUTHORIZED = 419; 
    // バリデーションエラー
    export const UNPROCESSABLE_ENTITY = 422; 
    // 認証回数制限
    export const TOO_MANY_REQUESTS = 429; 
    // システムエラー
    export const INTERNAL_SERVER_ERROR = 500; 

```

## ストアを作成

### auth storeの作成

`server\resources\js\store\auth.js`AUTHストアを作成

```JS:server\resources\js\store\auth.js
    /*
     * status cord
     */
    import { OK, CREATED, UNPROCESSABLE_ENTITY, TOO_MANY_REQUESTS } from "../util";

    /*
     * ステート（データの入れ物）
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
    }

    /*
     * ゲッター（ステートから算出される値）
     */
    const getters = {
        // ログイン済みかどうかのチェック
        check: state => !!state.user,
        // ユーザネームの取得
        username: state => (state.user ? state.user.name : "")
    }

    /*
     * ミューテーション（同期処理）
     */
    const mutations = {
        // userステートの更新
        setUser(state, user) {
            state.user = user
        },
        // Api通信の成功、失敗の更新
        setApiStatus(state, status) {
            state.apiStatus = status;
        },
        // ログインのエラーメッセージの更新
        setLoginErrorMessages(state, messages) {
            state.loginErrorMessages = messages;
        },
        // 登録のエラーメッセージの更新
        setRegisterErrorMessages(state, messages) {
            state.registerErrorMessages = messages;
        },
    }

    /*
     * アクション（非同期処理）
     */
    const actions = {
        /*
         * loginのアクション
         */
        async login(context, data) {

            // apiStatusのクリア
            context.commit("setApiStatus", null)

            // Apiリクエスト
            const response = await axios.post("/api/login", data)

            // 通信成功の場合 200
            if (response.status === OK) {

                // apiStatus を true に更新
                context.commit("setApiStatus", true);
                // user にデータを登録
                context.commit("setUser", response.data);
                // ここで終了
                return false;
            }

            // 通信失敗の場合
            // apiStatus を false に更新
            context.commit("setApiStatus", false);

            // 通信失敗のステータスが 422（バリデーションエラー）の場合
            // または、認証回数制限429;（認証回数制限）の場合
            if (response.status === UNPROCESSABLE_ENTITY || response.status === TOO_MANY_REQUESTS) {

                // loginErrorMessages にエラーメッセージを登録
                context.commit("setLoginErrorMessages", response.data.errors);
            }
            // 通信失敗のステータスがその他の場合
            else {

                // エラーストアの code にステータスコードを登録
                // 別ストアのミューテーションする場合は第三引数に { root: true } を追加
                context.commit("error/setCode", response.status, {
                    root: true
                });
            }
        },
        /*
         * registerのアクション
         */
        async register(context, data) {

            // apiStatusのクリア
            context.commit("setApiStatus", null)

            // Apiリクエスト
            const response = await axios.post("/api/register", data)

            // 通信成功の場合 201
            if (response.status === CREATED) {

                // apiStatus を true に更新
                context.commit("setApiStatus", true);
                // user にデータを登録
                context.commit("setUser", response.data);
                // ここで終了
                return false;
            }

            // 通信失敗の場合
            // apiStatus を false に更新
            context.commit("setApiStatus", false);

            // 通信失敗のステータスが 422（バリデーションエラー）の場合
            if (response.status === UNPROCESSABLE_ENTITY) {

                // registerErrorMessages にエラーメッセージを登録
                context.commit("setRegisterErrorMessages", response.data.errors);
            }
            // 通信失敗のステータスがその他の場合
            else {

                // エラーストアの code にステータスコードを登録
                // 別ストアのミューテーションする場合は第三引数に { root: true } を追加
                context.commit("error/setCode", response.status, {
                    root: true
                });
            }
        },
        /*
         * logoutのアクション
         */
        async logout(context) {

            // apiStatusのクリア
            context.commit("setApiStatus", null);

            // Apiリクエスト
            const response = await axios.post("/api/logout");

            // 通信成功の場合 200
            if (response.status === OK) {

                // apiStatus を true に更新
                context.commit("setApiStatus", true);
                // user にデータをクリア
                context.commit("setUser", null);
                // ここで終了
                return false;
            }

            // 通信失敗のステータスがその他の場合
            // apiStatus を false に更新
            context.commit("setApiStatus", false);

            // エラーストアの code にステータスコードを登録
            // 別ストアのミューテーションする場合は第三引数に { root: true } を追加
            context.commit("error/setCode", response.status, {
                root: true
            });
        },
        /*
         * カレントユーザのアクション
         */
        async currentUser(context) {

            // apiStatusのクリア
            context.commit("setApiStatus", null);

            // Apiリクエスト
            const response = await axios.get("/api/user");

            // ユーザをレスポンスから取得、なければnull
            const user = response.data || null;

            // 通信成功の場合 200
            if (response.status === OK) {

                // apiStatus を true に更新
                context.commit("setApiStatus", true);
                // user に取得したuserを登録
                context.commit("setUser", user);
                // ここで終了
                return false;
            }

            // 通信失敗の場合
            // apiStatus を false に更新
            context.commit("setApiStatus", false);
            // エラーストアの code にステータスコードを登録
            // 別ストアのミューテーションする場合は第三引数に { root: true } を追加
            context.commit("error/setCode", response.status, {
                root: true
            });
        }
    }

    /*
     * エクスポート
     */
    export default {
        // 名前をストア別に区別できるようにネームスペースを使う
        namespaced: true,
        state,
        getters,
        mutations,
        actions
    }
```

### error storeの作成

`server\resources\js\store\error.js`ERRORストアを作成

```JS:server\resources\js\store\error.js
    /*
     * ステート（データの入れ物）
     */
    const state = {
        // エラーコードの保持
        code: null
    };

    /*
     * ミューテーション（同期処理）
     */
    const mutations = {
        // エラーコードの更新
        setCode(state, code) {
            state.code = code;
        }
    };

    /*
     * エクスポート
     */
    export default {
        // 名前をストア別に区別できるようにネームスペースを使う
        namespaced: true,
        state,
        mutations
    };
```

### message storeの作成

`server\resources\js\store\message.js`MESSAGEストアを作成

```JS:server\resources\js\store\message.js
    /*
     * ステート（データの入れ物）
     */
    const state = {
        // メッセージの保持
        content: ""
    };

    /*
     * ミューテーション（同期処理）
     */
    const mutations = {
        // メッセージの更新
        setContent(state, { content, timeout }) {
            state.content = content;

            // タイムアウトでステートをクリア
            if (typeof timeout === "undefined") {
                timeout = 3000;
            }

            setTimeout(() => (state.content = ""), timeout);
        }
    };

    /*
     * エクスポート
     */
    export default {
        // 名前をストア別に区別できるようにネームスペースを使う
        namespaced: true,
        state,
        mutations
    };
```

### store indexの作成

`server\resources\js\store\index.js`を作成

これですべてのストアをひとつにする

```JS:server\resources\js\store\index.js
    import Vue from 'vue'
    import Vuex from 'vuex'

    // 各ストアのインポート
    import auth from './auth'
    import error from './error'
    import message from './message'

    Vue.use(Vuex)

    const store = new Vuex. Store({

        modules: {
            // 各ストアと登録
            auth,
            error,
            message
        }
    })

    export default store
```

## ストアをページへ実装

### app.jsの修正

`server\resources\js\app.js`でストアを読み込む

```JS:server\resources\js\app.js
    import "./bootstrap";
    import Vue from "vue";
    import App from "./App.vue";
    import router from "./router";
    // ストアをインポート
+   import store from './store'

// 非同期通信でAUTHストアのcurrentUserアクションを実行するので
// asyncメソッドにして、awaitで通信をまつ
+ const createApp = async () => {

    // AUTHストアのcurrentUserアクションでユーザの認証状態をチェック
+   await store.dispatch("auth/currentUser");

    new Vue({
        el: "#app",
        router,
        // ストアを登録
+       store,
        components: {App},
        template: "<App />"
    });
+ };

+ // createAppを実行
+ createApp();

```

### ログインページを修正

`server\resources\js\pages\Login.vue`を以下のように編集する

```HTML:server\resources\js\pages\Login.vue
    <template>
    ...

    <!-- login -->
    <section
      class="login"
      v-show="tab === 1"
    >
      <h2>Login</h2>

+      <!-- errors -->
+       <div v-if="loginErrors" class="errors">
+           <ul v-if="loginErrors.email">
+               <li v-for="msg in loginErrors.email" :key="msg">{{ msg }}</li>
+           </ul>
+           <ul v-if="loginErrors.password">
+               <li v-for="msg in loginErrors.password" :key="msg">{{ msg }}</li>
+           </ul>
+       </div>
+     <!--/ errors -->

      <!-- @submitで login method を呼び出し -->
      <!-- @submitイベントリスナに prevent をつけるとsubmitイベントによってページがリロードさない -->
      <form @submit.prevent="login">
        <div>Email</div>
        <div>
          <!-- v-modelでdataをバインド -->
          <input type="email" v-model="loginForm.email" />
        </div>
        <div>Password</div>
        <div>
          <input type="password" v-model="loginForm.password" />
        </div>
        <div>
          <button type="submit">login</button>
        </div>
      </form>
    </section>
    <!-- /login -->

    <!-- register -->
    <section class="register" v-show="tab === 2">
      <h2>register</h2>
+     <!-- errors -->
+     <div v-if="registerErrors" class="errors">
+         <ul v-if="registerErrors.name">
+             <li v-for="msg in registerErrors.name" :key="msg">{{ msg }}</li>
+         </ul>
+         <ul v-if="registerErrors.email">
+             <li v-for="msg in registerErrors.email" :key="msg">{{ msg }}</li>
+         </ul>
+         <ul v-if="registerErrors.password">
+             <li v-for="msg in registerErrors.password" :key="msg">{{ msg }}</li>
+         </ul>
+     </div>
+     <!--/ errors -->
      <form @submit.prevent="register">
        <div>Name</div>
        <div>
          <input type="text" v-model="registerForm.name" />
        </div>
        <div>Email</div>
        <div>
          <input type="email" v-model="registerForm.email" />
        </div>
        <div>Password</div>
        <div>
          <input type="password" v-model="registerForm.password" />
        </div>
        <div>Password confirmation</div>
        <div>
          <input type="password" v-model="registerForm.password_confirmation" />
        </div>
        <div>
          <button type="submit">register</button>
        </div>
      </form>
    </section>
    <!-- /register -->

    <!-- forgot -->
    <section class="forgot" v-show="tab === 3">
      <h2>forgot</h2>
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

    ...
    </template>

    <script>
    export default {
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
                    password_confirmation: "",
                },
                forgotForm: {
                    email: "",
                }
            };
        },
        // 算出プロパティでストアのステートを参照
+       computed: {
+           // authストアのapiStatus
+           apiStatus () {
+               return this.$store.state.auth.apiStatus
+           },
+           // authストアのloginErrorMessages
+           loginErrors () {
+               return this.$store.state.auth.loginErrorMessages
+           },
+           // authストアのregisterErrorMessages
+           registerErrors () {
+               return this.$store.state.auth.registerErrorMessages
+           },
+       },
        methods: {
            /*
            * login
            */
            async login() {
-               alert("login");
-               this.clearForm();
                // authストアのloginアクションを呼び出す
+               await this.$store.dispatch("auth/login", this.loginForm);
                // 通信成功
+               if (this.apiStatus) {
+                   // トップページに移動
+                   this.$router.push("/");
+               }
            },
            /*
             * register
             */
            async register() {
-               alert("register");
                // authストアのregisterアクションを呼び出す
+               await this.$store.dispatch(
+                   "auth/register",
+                   this.registerForm
+               );
                // 通信成功
+               if (this.apiStatus) {
+                   // メッセージストアで表示
+                   this.$store.commit("message/setContent", {
+                       content: "登録しました。",
+                       timeout: 10000
+                   });
+                   // AUTHストアのエラーメッセージをクリア
+                   this.clearError();
+                   // フォームをクリア
+                   this.clearForm();
+               }
            },
            /*
             * forgot
             */
            async forgot() {
                alert("forgot");
                this.clearForm();
            },
+           /*
+            * clear error messages
+            */
+           clearError() {
+               // AUTHストアのすべてのエラーメッセージをクリア
+               this.$store.commit("auth/setLoginErrorMessages", null);
+               this.$store.commit("auth/setRegisterErrorMessages", null);
+               this.$store.commit("auth/setForgotErrorMessages", null);
+           },

            /*
             * clearForm
             */
            clearForm() {
                // login form
                this.loginForm.email = "";
                this.loginForm.password = "";
                // register form
                this.registerForm.name = "";
                this.registerForm.email = "";
                this.registerForm.password = "";
                this.registerForm.password_confirmation = "";
                // forgot form
                this.forgot.email = "";
            }
        },
        created() {
            // clear error messages
            this.clearError();
        }
    };
    </script>

    <style scoped>
    ...
    </style>
```

### ヘッダーコンポネントを修正

* ログアウトの実装
* ログイン時に名前の表示と、ボタンの切り替えを入れる

上記のために`server\resources\js\components\Header.vue`を以下のように編集する

```HTML:server\resources\js\pages\Login.vue

    <template>
      <header>
          <!-- リンクを設定 -->
          <RouterLink to="/">home</RouterLink>
-         <RouterLink to="/login">login</RouterLink>
+         <RouterLink v-if="!isLogin" to="/login">login</RouterLink>
          <!-- ログインしている場合はusernameを表示 -->
+         <span v-if="isLogin">{{username}}</span>
          <!-- クリックイベントにlogoutメソッドを登録 -->
-         <span @click="logout">logout</span>
+         <span v-if="isLogin" @click="logout">logout</span>
      </header>
    </template>

    <script>
    export default {
    // 算出プロパティでストアのステートを参照
    computed: {
        // authストアのステートUserを参照
        isLogin() {
            return this.$store.getters["auth/check"];
        },
        // authストアのステートUserをusername
        username() {
            return this.$store.getters["auth/username"];
        }
    },
    methods: {
        // ログアウトメソッド
        async logout() {
            // authストアのlogoutアクションを呼び出す
            await this.$store.dispatch("auth/logout");

            // ログインに移動
-           this.$router.push("/login");
+           if (this.apiStatus) {
+               this.$router.push("/login");
+           }
        }
    };
    </script>

```

##＃ グローバルメッセージを表示

グローバルなメッセージを出せるメッセージコンポネントを作成

### メッセージコンポネントを作成

メッセージを表示するコンポネント`server\resources\js\components\Message.vue`を作成

```HTML:server\resources\js\Message.vue

<template>
    <div class="message" v-show="message">{{ message }}</div>
</template>

<script>
export default {
    computed: {
        // Messageストアのcontentステートを取得
        message() {
            return this.$store.state.message.content;
        },
    }
};
</script>

```

### Appコンポネントに組み込む

* INTERNAL_SERVER_ERROR（500）の場合に500にSystemErrorぺージに移動
* UNAUTHORIZED（419）の場合にトークンをリフレッシュ、ストアのuserをクリア、ログイン画面へ移動
* NOT_FOUND（404）404へNotFound移動
* エラーメッセージを表示するためにERRORストアのステートを監視

上記のために`server\resources\js\App.vue`を修正

```HTML:server\resources\js\App.vue

    <template>
      <div>

        <!-- Headerコンポーネント -->
        <Header />

        <main>
          <div class="container">
            <!-- message -->
+           <Message />

            <!-- ルートビューコンポーネント -->
            <RouterView />
          </div>
        </main>

      </div>
    </template>

    <script>
    // Headerコンポーネントをインポート
    import Header from "./components/Header.vue";
+   import Message from "./components/Message.vue";
    // ステータスコードをインポート
+   import { NOT_FOUND, UNAUTHORIZED, INTERNAL_SERVER_ERROR } from "./const";

    export default {
      // 使用するコンポーネントを宣言
      components: {
-       Header
+       Header,
+       Message
      }, 
+     computed: {
        // ERRORストアのcodeステートを取得
+       errorCode() {
+           return this.$store.state.error.code;
+       }
      },
+     watch: {
+       // errorCodeを監視
+       errorCode: {
+           async handler(val) {
+               // 500
+               if (val === INTERNAL_SERVER_ERROR) {
+                   // 500に移動
+                   this.$router.push("/500");
+               }
+               // 419
+               else if (val === UNAUTHORIZED) {
+                   // トークンをリフレッシュ
+                   await axios.get("/api/refresh-token");
+                   // ストアのuserをクリア
+                   this.$store.commit("auth/setUser", null);
+                   // ログイン画面へ移動
+                   this.$router.push("/login");
+               }
+               // 404
+               else if (val === NOT_FOUND) {
+                   // 404へ移動
+                   this.$router.push("/not-found");
+               }
+           },
+           // createdでも呼び出すときはこれだけでOK
+           immediate: true
+       },
+       // 同じrouteの異なるパラメータで画面遷移しても、vue-routerは画面を再描画しないのでwatchで監視
+       $route() {
+           this.$store.commit("error/setCode", null);
+       }
+     }
    };
    </script>

```

## ルーターを修正

ログインした状態ではログインページ、登録ページに行けないように`server\resources\js\router.js`を修正

```javascript:server\resources\js\router.js
　
  import Vue from 'vue'
  // ルーターをインポート
  import VueRouter from 'vue-router'
  // ストアをインポート
+ import store from "./store";

  // ページをインポート
  import Home from './pages/Home.vue'
  import Login from './pages/Login.vue'
  import SystemError from './pages/errors/SystemError.vue'
  import NotFound from './pages/errors/NotFound.vue'

  // VueRouterをVueで使う
  // これによって<RouterView />コンポーネントなどを使うことができる
  Vue.use(VueRouter)

  // パスとページの設定
  const routes = [
      // home
      {
          // urlのパス
          path: '/',
          // インポートしたページ
          component: Home
      },
      // login
      {
          // urlのパス
          path: '/login',
          // インポートしたページ
          component: Login,
          // ページコンポーネントが切り替わる直前に呼び出される関数
          // to はアクセスされようとしているルートのルートオブジェクト
          // from はアクセス元のルート
          // next はページの移動先
+         beforeEnter(to, from, next) {
              // AUTHストアでログインしているかチェック
+             if (store.getters["auth/check"]) {
                  // してる場合はホームへ
+                 next("/");
+             } else {
                  // してない場合はそのまま
+                 next();
+             }
+         }
      },
      // システムエラー
      {
          path: "/500",
          component: SystemError
      },
      // not found
      {
          // 定義されたルート以外のパスでのアクセスは <NotFound> が表示
          path: "*",
          component: NotFound
      }
  ]

...

```

これで、Register、Logiin、Logoutできるようになりました。  

次は今回作ったものをメール認証に対応するように変更します。  

> [Laravel mix vue No.5 - Api Email Verification - メール認証に変更](https://www.aska-ltd.jp/jp/blog/69)

<script src="https://www.aska-ltd.jp/js/blog.js"></script>
<link href="https://www.aska-ltd.jp/css/blog.css" rel="stylesheet">
