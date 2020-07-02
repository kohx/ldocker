<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

今回はVue側を多言語化します。

前回の記事は[Laravel mix vue No.8 - Laravel多言語化](https://www.aska-ltd.jp/jp/blog/72)

# LaravelとVueを多言語化

<!-- ## サンプル
- このセクションを始める前  
[github ldocker 08](https://github.com/kohx/ldocker/tree/08)  
  
- 完成  
[github ldocker 09](https://github.com/kohx/ldocker/tree/09) -->

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

------------------------------------------------------------------------------------------

## 多言語化モジュールをインストール

今回は`vue-i18n`を使用する

### インストール

`vue-i18n`をインストール

```bash:terminal

npm install vue-i18n --save

```

------------------------------------------------------------------------------------------

## 言語ファイルの準備

### 言語ファイルの作成

`server\resources\js\lang`を作成しその中に  
`En.js`と`Ja.js`を準備する

`server\resources\js\lang\En.js`を作成

```javascript:server\resources\js\lang\En.js

    const contents = {
        messages: {
            // 単語
            word: {
                hello: 'hello!',
            },
            // メッセージ
            sentence: {
                '{msg} world!': '{msg} world！',
            }
        },
        /*  
            日付フォーマット

            以下の定義形式で日時をローカライズ
            weekday         "narrow", "short", "long"
            era             "narrow", "short", "long"
            year            "2-digit", "numeric"
            month           "2-digit", "numeric", "narrow", "short", "long"
            day             "2-digit", "numeric"
            hour            "2-digit", "numeric"
            minute          "2-digit", "numeric"
            second          "2-digit", "numeric"
            timeZoneName    "short", "long"
        */
        dateTimeFormats: {
            full: {
                year: "numeric",
                month: "short",
                day: "numeric",
                weekday: "short",
                hour: "numeric",
                minute: "numeric"
            },
            day: {
                year: "numeric",
                month: "short",
                day: "numeric"
            },
            time: {
                hour: "numeric",
                minute: "numeric"
            },
            week: {
                weekday: "long"
            }
        },
        // ナンバーフォーマット
        numberFormats: {
            currency: {
                style: 'currency',
                currency: 'USD'
            }
        }
    };

    export {
        contents
    };

```

`server\resources\js\lang\Ja.js`を作成

```javascript:server\resources\js\lang\Ja.js

    const contents = {
        messages: {
            // 単語
            word: {
                hello: 'こんにちは!',
            },
            // メッセージ
            sentence: {
                '{msg} world!': '{msg} 世界！',
            }
        },
        /*
            日付フォーマット

            以下の定義形式で日時をローカライズ
            weekday         "narrow", "short", "long"
            era             "narrow", "short", "long"
            year            "2-digit", "numeric"
            month           "2-digit", "numeric", "narrow", "short", "long"
            day             "2-digit", "numeric"
            hour            "2-digit", "numeric"
            minute          "2-digit", "numeric"
            second          "2-digit", "numeric"
            timeZoneName    "short", "long"
        */
        dateTimeFormats: {
            full: {
                year: "numeric",
                month: "short",
                day: "numeric",
                weekday: "short",
                hour: "numeric",
                minute: "numeric",
                hour12: true
            },
            day: {
                year: "numeric",
                month: "short",
                day: "numeric"
            },
            time: {
                hour: "numeric",
                minute: "numeric",
                hour12: true
            },
            week: {
                weekday: "short"
            }
        },
        // ナンバーフォーマット
        numberFormats: {
            currency: {
                style: 'currency',
                currency: 'JPY',
                currencyDisplay: 'symbol'
            }
        }
    };

    export { contents };

```

### 言語ファイルをまとめる

各言語ファイルをまとめる`server\resources\js\lang\index.js`を作成

```javascript:server\resources\js\lang\index.js

    // 言語ファイルをインポート
    import {contents as en} from "./En";
    import {contents as ja} from "./Ja";

    // 言語アイテムをマージ
    const messages = {
        en: en.messages,
        ja: ja.messages
    };

    // 日付フォーマットをマージ
    const dateTimeFormats = {
        en: en.dateTimeFormats,
        ja: ja.dateTimeFormats
    };

    // ナンバーフォーマットをマージ
    const numberFormats = {
        en: en.numberFormats,
        ja: ja.numberFormats
    };

    // それぞれをエクスポート
    export { messages };
    export { dateTimeFormats };
    export { numberFormats };

```

------------------------------------------------------------------------------------------

## Vueで使う用意

`server\resources\js\app.js`に追加

```javascript:server\resources\js\app.js

    ...

    /*
     * vue-i18n
     * https://kazupon.github.io/vue-i18n/
     */
    // モジュールのインポート
+   import VueI18n from "vue-i18n";
    // 言語コンテンツのインポート
+   import { messages, dateTimeFormats, numberFormats } from "./lang/index";
    // 多言語化の宣言
+   Vue.use(VueI18n);
    // ローカルストレージから「language」を取得してセット、ない場合はブラウザーの言語をセット
+   const locale = Vue.storage.get("language", Helper.getLanguage());
    // VueI18nコンストラクタのオプションを指定
+   const i18n = new VueI18n({
        // 言語設定
+       locale: locale,
        // 選択中の言語に対応する文字列が存在しない場合はこの言語の文字列を使用する
+       fallbackLocale: "en",
        // インポートした言語コンテンツ
+       messages,
+       dateTimeFormats,
+       numberFormats
+   });

    // 非同期通信でAUTHストアのcurrentUserアクションを実行するので
    // asyncメソッドにして、awaitで通信をまつ
    const createApp = async () => {

        // AUTHストアのcurrentUserアクションでユーザの認証状態をチェック
        await store.dispatch("auth/currentUser");

        new Vue({
            // マウントする要素に「index.blade.php」の「<div id="app"></div>」のidを登録
            el: "#app",
            // ルーターの使用を宣言
            router,
            // ストアを登録
            store,
            // I18nを登録
+           i18n,
            // 使用するコンポーネントにルートコンポーネントの登録
            components: { App },
            // 描画するテンプレート
            template: "<App />"
        });
};

```

## 埋め込み

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
            <span v-if="isLogin" @click="logout">logout</span>
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
+               <h2>{{ $t('word.password_reset') }</h2>

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
+                           <button type="submit">{{ $t('word.reset') }</button>
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