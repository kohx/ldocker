<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

前回環境を構築したので、今回はvue router と componentを使ってみます。

前回の記事は[Laravel mix vue No.1 - enveronment - Dockerでlaravel環境](https://www.aska-ltd.jp/jp/blog/52)

<ins>2020/6/1 修正、Git追加</ins>

# Vue router, Vue component

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

次はこれ[Laravel mix vue No.3 - Authentication API](https://www.aska-ltd.jp/jp/blog/65)

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