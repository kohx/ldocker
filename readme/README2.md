<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

前回環境を構築したので、今回はvue router と componentを使ってみます。

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
  <!-- <li><a href="https://www.aska-ltd.jp/jp/blog/76">Laravel mix vue No.10 - Vue Font Awesome - フォントアイコン追加</a></li> -->
</ul>

# Vue Router, Component - Vueルータの設定とコンポネント作成

## サンプル
- このセクションを始める前  
[github ldocker 02](https://github.com/kohx/ldocker/tree/02)  
  
- 完成  
[github ldocker 03](https://github.com/kohx/ldocker/tree/03)

---

## フォルダ構成

```text

└─ server
   └─ resources
      └─ js
+        ├─ components
+        │  └─ Header.vue
+        ├─ pages
+        │  ├─ errors
+        │  │  ├─ NotFound.veu
+        │  │  └─ SystemError.vue
+        │  ├─ Home.vue
+        │  └─ Login.vue
         ├─ app.js
+        ├─ App.vue
+        └─ router.js

```

---

## 開発用にchrome拡張機能を追加

chromeブラウザで、「chrome ウェブストア」から [Vue.js devtools](https://chrome.google.com/webstore/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd)を追加

---

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

---

## vueファイルの作成

### コンポーネントの作成

```HTML:server\resources\js\components\Header.vue
<template>
  <header>
    <!-- リンクを設定 -->
    <RouterLink to="/">home</RouterLink>
    <RouterLink to="/login">login</RouterLink>
    <!-- クリックイベントにlogoutメソッドを登録 -->
    <span @click="logout">logout</span>

  </header>
</template>

<script>
export default {
  methods: {
    // ログアウトメソッド
    async logout() {
      // ログアウトのAPIリクエスト（次のステップで実装）
      alert("logout");
      // ログインに移動
      this.$router.push("/login");
    }

  }
};
</script>
```

### ルートコンポーネントの作成

このファイルは `server\\resources\\views\\index.blade.php`の `<div id="app"></div>`にこのコンポーネントが描画される
この中のルートビューコンポーネント `<RouterView />`が各ページに切り替わる
`<Header />`にはヘッダーコンポーネントが表示される

```HTML:server/resources/js/App.vue
<template>
  <div>
  
    <!-- Headerコンポーネント -->
    <Header />

    <main>
      <div class="container">
        <!-- ルートビューコンポーネント -->
        <RouterView />
      </div>
    </main>

  </div>
</template>

<script>
// Headerコンポーネントをインポート
import Header from "./components/Header.vue";

export default {
  // 使用するコンポーネントを宣言
  components: {
    Header
  }
};
</script>
```

### ページの作成

* home

```HTML:server/resources/js/pages/Home.vue
<template>
  <div class="container">

    <h1>Home</h1>

  </div>
</template>
```

* login

```HTML:server/resources/js/pages/Login.vue
<template>
  <div class="container">

    <!-- login -->
    <section class="login">
      <h2>Login</h2>
      <form class="form">
        <div>Email</div>
        <div>
          <input type="email" />
        </div>
        <div>Password</div>
        <div>
          <input type="password" />
        </div>
        <div>
          <button type="submit">login</button>
        </div>
      </form>
    </section>
    <!-- /login -->

  </div>
</template>
```

* 404 not found

```JS:server\resources\js\pages\errors\NotFound.vue

<template>
  <p>お探しのページは見つかりませんでした。</p>
</template>

```

* 500 system error

```JS:server\resources\js\pages\errors\SystemError.vue
<template>
  <p>システムエラーが発生しました。</p>
</template>
```

### ルーターの作成

```JS:server/resources/js/router.js
import Vue from 'vue'
// ルーターをインポート
import VueRouter from 'vue-router'

// ページをインポート
import Home from './pages/Home.vue'
import Login from './pages/Login.vue'
import SystemError from './pages/errors/SystemError.vue'
import NotFound from './pages/errors/NotFound.vue'

// VueRouterをVueで使う
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
        component: Login
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

// VueRouterインスタンス
const router = new VueRouter({
    // いつもどうりのURLを使うために「history」モードにする
    mode: 'history',
    // 設定したルートオブジェクト
    routes
})

// VueRouterインスタンスをエクスポート
export default router
```

### app.jsの修正

```JS:server\resources\js\app.js
　   import "./bootstrap";
　   import Vue from "vue";
+   // ルートコンポーネントをインポート
+   import App from "./App.vue";
+   // ルーターをインポート
+   import router from "./router";

　   new Vue({
+       // マウントする要素に「index.blade.php」の「<div id="app"></div>」のidを登録
　       el: "#app",
+       // ルーターの使用を宣言
+       router,
+       // 使用するコンポーネントにルートコンポーネントの登録
+       components: { App },
+       // 描画するテンプレート
-       template: "<h1>Hello world</h1>"
+       template: "<App />"
+   });

```

---

### ブラウザで確認

Home
[http://localhost:3000](http://localhost:3000)

Login
[http://localhost:3000/login](http://localhost:3000/login)

---

## UIの作成

### タブの作成

タブで切り替えれるログイン、 登録、 パスワード再設定のフォームを作ります。

```HTML:server/resources/js/pages/Login.vue
 <template>
   <div class="container">

+    <!-- tabs -->
+    <ul class="tab">
+      <li class="tab__item"
+        :class="{'tab__item--active': tab === 1 }"
+        @click="tab = 1"
+      >Login</li>
+      <li class="tab__item"
+         :class="{'tab__item--active': tab === 2 }"
+          @click="tab = 2"
+        >Register</li>
+      <li
+        class="tab__item"
+        :class="{'tab__item--active': tab === 3 }"
+        @click="tab = 3"
+      >Forgot password ?</li>
+    </ul>
+    <!-- /tabs -->

+    <!-- login -->
-    <section class="login" >
+    <section
+      class="login"
+      v-show="tab === 1"
+    >
       <h2>Login</h2>
       <form>
         <div>Email</div>
         <div>
           <input type="email" />
         </div>
         <div>Password</div>
         <div>
           <input type="password" />
         </div>
         <div>
           <button type="submit">login</button>
         </div>
       </form>
     </section>
     <!-- /login -->

+    <!-- register -->
+    <section class="register" v-show="tab === 2">
+      <h2>register</h2>
+      <form>
+        <div>Name</div>
+        <div>
+          <input type="text" />
+        </div>
+        <div>Email</div>
+        <div>
+          <input type="email" />
+        </div>
+        <div>Password</div>
+        <div>
+          <input type="password" />
+        </div>
+        <div>Password confirmation</div>
+        <div>
+          <input type="password" />
+        </div>
+        <div>
+          <button type="submit">register</button>
+        </div>
+      </form>
+    </section>
+    <!-- /register -->

+    <!-- forgot -->
+    <section class="forgot" v-show="tab === 3">
+      <h2>forgot</h2>
+      <form>
+        <div>Email</div>
+        <div>
+          <input type="email" />
+        </div>
+        <div>
+          <button type="submit">send</button>
+        </div>
+      </form>
+    </section>
+    <!-- /forgot -->

   </div>
 </template>

+<script>
+export default {
+  // vueで使うデータ
+  data() {
+    return {
+      tab: 1,
+    };
+  }
+};
+</script>
+<style>
+.tab {
+  padding: 0;
+  display: flex;
+  list-style: none;
+}
+.tab__item {
+  border: 1px solid gray;
+  padding: 0 0.5rem;
+  margin-left: 0.1rem;
+  cursor: pointer;
+}
+.tab__item--active {
+  background-color: lightgray;
+}
+</style>
```

### inputのバインディング

```HTML:server/resources/js/pages/Login.vue
...
    <!-- login -->
    <section
      class="login"
      v-show="tab === 1"
    >
    <h2>Login</h2>
-   <form>
+   <!-- @submitで login method を呼び出し -->
+   <!-- @submitイベントリスナに prevent をつけるとsubmitイベントによってページがリロードさない -->
+   <form @submit.prevent="login">
      <div>Email</div>
      <div>
-       <input type="email" />
+       <!-- v-modelでdataをバインド -->
+       <input type="email" v-model="loginForm.email" />
      </div>
      <div>Password</div>
      <div>
-       <input type="password" />
+       <input type="password" v-model="loginForm.password" />
      </div>
      <div>
        <button type="submit">login</button>
      </div>
    </form>
    </section>
    <!-- /login -->

    <!-- register -->
    <section
      class="register"
      v-show="tab === 2"
    >
      <h2>register</h2>
-     <form>
+     <form @submit.prevent="register">
        <div>Name</div>
        <div>
-         <input type="text" >
+         <input type="text" v-model="registerForm.name" />
        </div>
        <div>Email</div>
        <div>
-         <input type="email" >
+         <input type="email" v-model="registerForm.email" />
        </div>
        <div>Password</div>
        <div>
-         <input type="password" />
+         <input type="password" v-model="registerForm.password" />
        </div>
        <div>Password confirmation</div>
        <div>
-         <input type="password" />
+         <input type="password" v-model="registerForm.password_confirmation" />
        </div>
        <div>
          <button type="submit">register</button>
        </div>
      </form>
    </section>
    <!-- /register -->

   <!-- forgot -->
    <section
        class="forgot"
        v-show="tab === 3"
    >
      <h2>forgot</h2>
-     <form>
+     <form @submit.prevent="forgot">
        <div>Email</div>
        <div>
-         <input type="email" />
+         <input type="email" v-model="forgotForm.email" />
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
+           loginForm: {
+               email: "",
+               password: "",
+               remember: true
+           },
+           registerForm: {
+               name: "",
+               email: "",
+               password: "",
+               password_confirmation: ""
+           },
+           forgotForm: {
+               email: ""
+           },
        };
    },

+   methods: {
+       /*
+        * login
+        */
+       async login() {
+           alert("login");
+           this.clearForm();
+       },

+       /*
+        * register
+        */
+       async register() {
+           alert("register");
+           this.clearForm();
+       },

+       /*
+        * forgot
+        */
+       async forgot() {
+           alert("forgot");
+           this.clearForm();
+       },

+       /*
+        * clear form
+        */
+       clearForm() {
+           // login form
+           this.loginForm.email = "";
+           this.loginForm.password = "";
+           // register form
+           this.registerForm.name = "";
+           this.registerForm.email = "";
+           this.registerForm.password = "";
+           this.registerForm.password_confirmation = "";
+           // forgot form
+           this.forgot.email = "";
+       },
+   }
};
...
```

これで、UI完成、Vueだけでルーティングできるようになった！

> 次はこれ[Laravel mix vue No.3 - Authentication API - Apiで認証](https://www.aska-ltd.jp/jp/blog/65)

<script src="https://www.aska-ltd.jp/js/blog.js"></script>
<link href="https://www.aska-ltd.jp/css/blog.css" rel="stylesheet">
