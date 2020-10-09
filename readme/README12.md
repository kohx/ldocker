
<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

今回はAWS S3に画像をアップした画像の一覧を作成します。

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
  <li><a href="https://www.aska-ltd.jp/jp/blog/80">Laravel mix vue No.11 - AWS S3 - AWS S3 に写真をアップデート</a></li>
  <!-- <li><a href="https://www.aska-ltd.jp/jp/blog/80">Laravel mix vue No.12 - Web Application 1 - ウェブアプリケーション１</a></li> -->
</ul>

# Vue Font Awesome, Vue Formulate, etc - UIの作り込み

## サンプル
- このセクションを始める前  
[github ldocker 11](https://github.com/kohx/ldocker/tree/11)  
  
- 完成  
[github ldocker 12](https://github.com/kohx/ldocker/tree/12)

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

## 修正

### .envからベースURLを取得できるようにする

`server\.env`にVueで使う項目を追加  
このときのプレフィックスは「MIX_」でないと取得できない

```yaml:.env

    # Laravel Mixで使う項目
    # このときのプレフィックスは「MIX_」でないと取得できない
    MIX_URL="${APP_URL}"

```

取得する側の`server\resources\js\bootstrap.js`を修正

```javascript:server\resources\js\bootstrap.js

    // ベースURLを.envから取得
    // MIX_URL="${APP_URL}"を.envに追加
    // このときのプレフィックスは「MIX_」でないと取得できない
    const baseUrl = process.env.MIX_URL;

    // ApiURLの設定
    window.axios.defaults.baseURL = `${baseUrl}/api/`;

```

### ブラウザキャッシュのコントロール
```javascript:server\webpack.mix.js

    // 本番環境だけバージョンを自動的に付与してキャッシュを自動更新できるようにする
    if (mix.inProduction()) {
        mix.version();
    }

```

------------------------------------------------------------------------------------------

## ルートを追加

`server\routes\api.php`を編集

```php:server\routes\api.php

    ...

    // 写真
    Route::post('/photos', 'PhotoController@store')->name('photo.store');
+   Route::get('/photos', 'PhotoController@index')->name('photo.list');
+   Route::get('/photos/{id}', 'PhotoController@show')->name('photo.show');
+   Route::put('/photos/like', 'PhotoController@like')->name('photo.like');
+   Route::put('/photos/{id}', 'PhotoController@edit')->name('photo.edit');
+   Route::delete('/photos/{id}', 'PhotoController@destroy')->name('photo.destroy');
+   // モデルバインディングで取得するので明示的に「{photo}」にしておく
+   Route::post('/photos/{photo}/comments', 'PhotoController@storeComment')->name('photo.store_comment');
+   Route::put('/photos/comments/{comment}', 'PhotoController@editComment')->name('photo.edit_comment');
+   Route::delete('/photos/comments/{comment}', 'PhotoController@deleteComment')->name('photo.delete_comment');

    ...

```

`server\routes\web.php`を編集

```php:server\routes\api.php

    ...

+   // 写真ダウンロード
+   Route::get('/photos/{photo}/download', 'PhotoController@download')
+       ->name('photo.download');
+       
    ...

```

------------------------------------------------------------------------------------------

## フォトコントローラに一覧取得のメソッドを追加

`server\app\Http\Controllers\PhotoController.php`を編集

```php:server\app\Http\Controllers\PhotoController.php

    <?php

    namespace App\Http\Controllers;

    // models
    use App\Models\Photo;
+   use App\Models\Comment;

    // requests
    use App\Http\Requests\StorePhoto;
+   use App\Http\Requests\StoreComment;

    // facades
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Storage;

    class PhotoController extends Controller
    {
        public function __construct()
        {
            ...
        }

+       /**
+        * 写真一覧
+        * get /api/photos photo.list
+        */
+       public function index()
+       {
+           // user情報も一緒に取得
+           $photos = Photo::with('user')
+               // 新しいもの順に取得
+               ->orderBy(Photo::CREATED_AT, 'desc')
+               // get()の代わりにpaginateを使うことで、
+               // JSON レスポンスでも示した total（総ページ数）や current_page（現在のページ）といった情報が自動的に追加される
+               ->paginate();
+
+           return $photos;
+       }

+       /**
+        * 写真詳細
+        * get /api/photos/{id} photo.show
+        *
+        * モデルバインディングで取得しないで、withつきで取得する
+        * @param string $id
+        * @return Photo
+        */
+       public function show(string $id)
+       {
+           // 'comments.author'でcommentsと、そのbelongsToに設定されている'author'も取得
+           $photo = Photo::with(['user', 'comments.author', 'likes'])
+               // 「findOrFail」でIDが存在しない場合は404が自動的に返される
+               ->findOrFail($id);
+
+           return $photo;
+       }

        /**
         * 写真投稿
         * post /api/photos photo.store
         *
         * リクエストは「StorePhoto」を使う
         * @param StorePhoto $request
         * @return \Illuminate\Http\Response
         */
        public function store(StorePhoto $request)
        {
            ...
        }

+       /**
+        * 写真ダウンロード
+        * get /photos/{photo}/download download
+        *
+        * 引数にモデルを指定したらモデルバインディングでphotoモデルを取得できる
+        * https://laravel.com/docs/7.x/routing#route-model-binding
+        *
+        * @param Photo $photo
+        * @return \Illuminate\Http\Response
+        */
+       public function download(Photo $photo)
+       {
+
+           // 写真なければ404
+           if (!Storage::cloud()->exists($photo->path)) {
+               abort(404);
+           }
+
+           // 拡張子を取得
+           $extension =  pathinfo($photo->path, PATHINFO_EXTENSION);
+
+           // コンテンツタイプにapplication/octet-streamを指定すればダウンロードできます。
+           // Content-Dispositionヘッダーにattachmentを指定すれば、コンテンツタイプが"application/octet-stream"でなくても、ダウンロードできます。
+           // filenameに指定した値が、ダウンロード時のデフォルトのファイル名になります。
+           $headers = [
+               'Content-Type' => 'application/octet-stream',
+               'Content-Disposition' => "attachment; filename={$photo->name}",
+           ];
+
+           // ファイルを取得
+           $file = Storage::cloud()->get($photo->path);
+
+           return response($file, 200, $headers);
+       }
+   }

+       /**
+        * ライク
+        * put /api/photos/like photo.like
+        * 
+        * @return array
+        */
+       public function like(Request $request)
+       {
+           // パラメータを取得
+           $photoId = $request->input('photo_id');
+           $liked = $request->input('liked');
+
+           // ない場合、または方がおかしい場合には500エラー
+           if (!$photoId || !is_bool($liked)) {
+
+               abort(500);
+           }
+    
+           // likes付きで写真を取得
+           $photo = Photo::with('likes')->findOrFail($photoId);
+
+           // ライクが送られてきた場合
+           if ($liked) {
+
+               // 多対多リレーションなのでattachヘルパメソッドをつかって追加
+               $photo->likes()->attach(Auth::user()->id);
+           }
+           // ライクの削除が送られてきた場合
+           else {
+
+               // 多対多リレーションなのでattachヘルパメソッドをつかって削除
+               $photo->likes()->detach(Auth::user()->id);
+           }
+
+           // 現在のis_likedとtotal_likeを返す
+           return ["is_liked" => $liked, "total_like" => $photo->likes()->count()];
+       }

+       /**
+        * コメント投稿
+        * post /api/photos/{photo}/comments photo.store_comment
+        *
+        * モデルバインディングで取得
+        * @param Photo $photo
+        * @param StoreComment $request
+        * @return \Illuminate\Http\Response
+        */
+       public function storeComment(Photo $photo, StoreComment $request)
+       {
+           // コメントモデルを作成
+           $comment = new Comment();
+
+           // 値をセット
+           $comment->content = $request->input('content');
+           $comment->user_id = Auth::user()->id;
+
+           // データベースに反映
+           $photo->comments()->save($comment);
+
+           // authorリレーションをロードするためにコメントを取得しなおす
+           $new_comment = Comment::with('author')->find($comment->id);
+
+           return response($new_comment, 201);
+   }

```

------------------------------------------------------------------------------------------


## Vueに新しいページを追加

### その前に前回のバグフィックスと追加

修正するバグ
* 送信後にVueFormulateのバリデーションが出る
* 言語切り替えしてもVueFormulateのバリデーションの言語が切り替わらない
* 言語切り替えセレクトの中の言語が切り替わらない  

追加で修正
* 「ログイン」と「パスワードをわすれましたか?」の場合は、サーバから返ってくるエラーをフォーム全体にセットする 
* 「登録」の場合は、サーバから返ってくるエラーを各フィールドのFormulateにセットする 

Vueルートに名前をつけるので`server\resources\js\router.js`を修正

```javascript:server\resources\js\router.js

    ...
    // パスとページの設定
    const routes = [
        // home
        {
            // urlのパス
            path: "/",
+           // ルートネーム
+           name: 'home',
            ...
        },
        // login
        {
            // urlのパス
            path: "/login",
+           // ルートネーム
+           name: 'login',
            ...
        },
        // password reset
        {
            // urlのパス
            path: "/reset",
+           // ルートネーム
+           name: 'reset',
            ...
        },
        // システムエラー
        {
            // urlのパス
            path: "/500",
+           // ルートネーム
+           name: 'system-error',
            ...
        },
        // not found
        {
            // 定義されたルート以外のパスでのアクセスは <NotFound> が表示
            path: "*",
+           // ルートネーム
+           name: 'not-found',
            ...
        },
        
        ...
    ];
    ...

```

色んな所に`this.$router.push()`がかかれているので、ルートネームで指定するように修正する。

`server\resources\js\App.vue`  
`server\resources\js\components\Header.vue`  
`server\resources\js\pages\Login.vue`  
`server\resources\js\pages\Reset.vue`  
`server\resources\js\pages\Reset.vue`  

```javascript:ex

-   <RouterLink to="/">
+   <RouterLink to="{ name: 'home'}">

-   this.$router.push("/home");
+   this.$router.push({ name: "home" });

-   this.$router.push("/login");
+   this.$router.push({ name: "login" });

-   this.$router.push("/500");
+   this.$router.push({ name: "system-error" });

-   this.$router.push("/not-found");
+   this.$router.push({ name: "not-found" });

-   next("/");
+   next({ name: 'home' });

```

言語の設定とフォームのクリアを追加するので`server\resources\js\components\Header.vue`を修正

```javascript:server\resources\js\components\Header.vue
    <template>
        ...
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
                // authストアのlogoutアクションを呼び出す
                await this.$store.dispatch("auth/logout");
                // ログインに移動
                if (this.apiStatus) {
                    this.$router.push("/login");
                }
            },
            // 言語切替メソッド
            changeLang() {
                // ローカルストレージに「language」をセット
                this.$storage.set("language", this.selectedLang);
                // Apiリクエスト言語を設定
                axios.get(`set-lang/${this.selectedLang}`);
                // Vue i18n の言語を設定
                this.$i18n.locale = this.selectedLang;

+               // bugfix: i18nの言語変更だけだと動的に変更しないのでformulateの言語を設定
+               this.$formulate.selectedLocale = this.selectedLang

+               // bugfix: ここで入れ直さないとセレクトの中身が変更されない
+               this.langList.en = this.$i18n.tc("word.english");
+               this.langList.ja = this.$i18n.tc("word.japanese");

+               // 現在のルートネームを取得
+               const currentRoute = this.$route.name;
+
+               // ルートネームがログインのときのみクリア
+               if(currentRoute === 'login'){
+                   // フォームをクリア
+                   this.$formulate.reset("login_form");
+                   this.$formulate.reset("register_form");
+                   this.$formulate.reset("forgot_form");
+               }
            }
        },
        created() {
            ...
        }
    };
    </script>

```

フォームに名前をつけ、フォーム
`server\resources\js\pages\Login.vue`を修正

```javascript:server\resources\js\pages\Login.vue

    <template>
        <div class="page">
            <h1>{{ $t('word.login') }}</h1>

            <!-- tabs -->
            ...
            <!-- /tabs -->

            <!-- login -->
            <section class="login panel" v-show="tab === 1">
-               <!-- errors -->
-               <div v-if="loginErrors" class="errors">
-                   <ul v-if="loginErrors.email">
-                       <li v-for="msg in loginErrors.email" :key="msg">{{ msg }}</li>
-                   </ul>
-                   <ul v-if="loginErrors.password">
-                       <li v-for="msg in loginErrors.password" :key="msg">{{ msg }}</li>
-                   </ul>
-               </div>
-               <!--/ errors -->

                <!-- @submitで login method を呼び出し -->
                <!-- 「:form-errors="loginErrors ? loginErrors.email : []"」でフォーム全体に出すエラーをセット -->
-               <FormulateForm v-model="loginForm" @submit="login">
+               <FormulateForm
+                   name="login_form"
+                   v-model="loginForm"
+                   @submit="login"
+                   :form-errors="loginErrors ? loginErrors.email : []"
+               >
                    ...
                </FormulateForm>

                ...
            </section>
            <!-- /login -->

            <!-- register -->
            <section class="register panel" v-show="tab === 2">
                <!-- errors -->
-               <div v-if="registerErrors" class="errors">
-                   <ul v-if="registerErrors.name">
-                       <li v-for="msg in registerErrors.name" :key="msg">{{ msg }}</li>
-                   </ul>
-                   <ul v-if="registerErrors.email">
-                       <li v-for="msg in registerErrors.email" :key="msg">{{ msg }}</li>
-                   </ul>
-                   <ul v-if="registerErrors.password">
-                       <li v-for="msg in registerErrors.password" :key="msg">{{ msg }}</li>
-                   </ul>
-               </div>
-               <!--/ errors -->
                <!-- 「:errors="registerErrors」これでサーバから返ってくるエラーをFormulateにセットする -->
-               <FormulateForm v-model="registerForm" @submit="register">
+               <FormulateForm name="register_form" v-model="registerForm" @submit="register" :errors="registerErrors">
                    <FormulateInput
                        name="name"
                        type="text"
                        :label="$t('word.name')"
                        :validation-name="$t('word.name')"
                        validation="required|max:50"
                        :placeholder="$t('word.name')"
                    />
                    <FormulateInput
                        name="email"
                        type="email"
                        :label="$t('word.email')"
                        :validation-name="$t('word.email')"
                        validation="required|email"
                        :placeholder="$t('word.email')"
                    />
                    <FormulateInput
                        name="password"
                        type="password"
                        :label="$t('word.password')"
                        :validation-name="$t('word.password')"
                        validation="required|min:8"
                        :placeholder="$t('word.password')"
                    />
                    <!-- バリデーション「confirm」はsurfix「_confirm」のまえのnameを探す(password_confirm の場合は password) -->
                    <!-- 違うnameで「confirm」する場合は「confirm:password」 のように一致させるフィールドのnameを渡す -->
                    <FormulateInput
                        name="password_confirmation"
                        type="password"
                        :label="$t('word.password_confirmation')"
                        :validation-name="$t('word.password_confirmation')"
-                       validation="required|min8"
+                       validation="required|confirm:password"
                        :placeholder="$t('word.password_confirmation')"
                    />
                    <FormulateInput type="submit" :disabled="loadingStatus">
                        {{ $t('word.register') }}
                        <FAIcon v-if="loadingStatus" :icon="['fas', 'spinner']" pulse fixed-width />
                    </FormulateInput>
                </FormulateForm>
            </section>
            <!-- /register -->

            <!-- forgot -->
            <section class="forgot panel" v-show="tab === 3">
-               <!-- errors -->
-               <div v-if="forgotErrors" class="errors">
-                   <ul v-if="forgotErrors.email">
-                       <li v-for="msg in forgotErrors.email" :key="msg">{{ msg }}</li>
-                   </ul>
-               </div>
-               <!--/ errors -->
-               <FormulateForm v-model="forgotForm" @submit="forgot">
+               <!-- 「:form-errors="forgotErrors ? forgotErrors.email : []"」でフォーム全体に出すエラーをセット -->
+               <FormulateForm
+                   name="forgot_form"
+                   v-model="forgotForm"
+                   @submit="forgot"
+                   :form-errors="forgotErrors ? forgotErrors.email : []"
+               >
                    ...
                </FormulateForm>
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
        // 算出プロパティでストアのステートを参照
        computed: {
            ...
        },
        methods: {
            ...
            /*
            * clear error messages
            */
            clearError() {
                // AUTHストアのすべてのエラーメッセージをクリア
                this.$store.commit("auth/setLoginErrorMessages", null);
                this.$store.commit("auth/setRegisterErrorMessages", null);
                this.$store.commit("auth/setForgotErrorMessages", null);

+               // ここでformulateもリセットしておく
+               this.$formulate.reset("login_form");
+               this.$formulate.reset("register_form");
+               this.$formulate.reset("forgot_form");
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

```

### ビュールートを追加

`server\resources\js\router.js`に新しいページのルートを追加

```javascript:server\resources\js\router.js

    ...

    // ページをインポート
    import Home from "./pages/Home.vue";
+   import Photo from "./pages/Photo.vue";
    import Login from "./pages/Login.vue";
    import Reset from "./pages/Reset.vue";
    import SystemError from "./pages/errors/SystemError.vue";
    import NotFound from "./pages/errors/NotFound.vue";

    Vue.use(VueRouter);

    // パスとページの設定
    const routes = [
        // home
        {
            // urlのパス
            path: "/",
            // ルートネーム
            name: 'home',
            // インポートしたページ
            component: Home
        },
+       // photo
+       {
+           // urlのパス
+           path: "/photo",
+           // ルートネーム
+           name: 'photo',
+           // インポートしたページ
+           component: Photo,
+           // ページコンポーネントが切り替わる直前に呼び出される関数
+           // to はアクセスされようとしているルートのルートオブジェクト
+           // from はアクセス元のルート
+           // next はページの移動先
+           beforeEnter(to, from, next) {
+               if (store.getters["auth/check"]) {
+                   next();
+               } else {
+                   next({
+                       name: 'login'
+                   });
+               }
+           }
+       },
        // login
        {
            ...
        },
        // password reset
        {
            ...
        },
        // システムエラー
        {
            path: "/500",
            // ルートネーム
            name: 'system-error',
            // ルートネーム
            component: SystemError
        },
        // not found
        {
            // 定義されたルート以外のパスでのアクセスは <NotFound> が表示
            path: "*",
            // ルートネーム
            name: 'not-found',
            component: NotFound
        }
    ];

    // VueRouterインスタンス
    const router = new VueRouter({
        // いつもどうりのURLを使うために「history」モードにする
        mode: "history",
        // 設定したルートオブジェクト
        routes
    });

    // VueRouterインスタンスをエクスポート
    export default router;

```

### ヘッダに新しいページのリンクを作成

```javascript:server\resources\js\components\Header.vue

    <template>
        <header class="header">
            <!-- リンクを設定 -->
            <RouterLink :to="{ name: 'home'}">
                <FAIcon :icon="['fas', 'home']" size="lg" />
                {{ $t('word.home') }}
            </RouterLink>
+           <RouterLink v-if="isLogin" :to="{ name: 'photo'}">
+               <FAIcon :icon="['fas', 'camera-retro']" size="lg" />
+               {{ $t('word.photo') }}
+           </RouterLink>
            <RouterLink v-if="!isLogin" to="/login">
                <FAIcon :icon="['fas', 'sign-in-alt']" size="lg" />
                {{ $t('word.login') }}
            </RouterLink>
            ...
        </header>
    </template>

    <script>
    import Cookies from "js-cookie";
    import Helper from "../helper";

    export default {
        data() {
            ...
        },
        // 算出プロパティでストアのステートを参照
        computed: {
+           // bugfix: 忘れていたので追加
+           // authストアのapiStatus
+           apiStatus() {
+               return this.$store.state.auth.apiStatus;
+           },
            // authストアのステートUserを参照
            isLogin() {
                return this.$store.getters["auth/check"];
            },
            // authストアのステートUserをusername
            username() {
                return this.$store.getters["auth/username"];
            }
        },
        // app.jsでVueLocalStorageの名前を変更したので「storage」で宣言
        storage: {
            ...
        },
        methods: {
            // ログアウトメソッド
            async logout() {
                // authストアのlogoutアクションを呼び出す
                await this.$store.dispatch("auth/logout");

+               // ログアウト成功の場合
+               if (this.apiStatus) {
+                   // 「photo」のページにいる場合ログインに移動
+                   if (["photo"].includes(this.$route.name)) {
+                       this.$router.push({ 'name': "login" });
+                   }
+               }
            },
            // 言語切替メソッド
            changeLang() {
                ...
            }
        },
        created() {
            ...
        }
    };
    </script>

```

### フォトアップデートのページを作成

```javascript:server\resources\js\pages\Photo.vue

    <template>
        <div class="page">
            <h1>{{ $t('word.upload_photo') }}</h1>

            <FormulateForm
                @submit="uploadPhoto"
                name="upload_form"
                v-model="photoForm"
                :form-errors="uploadErrors"
            >
                <FormulateInput
                    type="text"
                    name="photo_name"
                    :label="$t('word.photo_name')"
                    :validation-name="$t('word.photo_name')"
                    validation="required|max:255,length"
                />
                <FormulateInput
                    type="textarea"
                    name="photo_description"
                    :label="$t('word.photo_description')"
                    :validation-name="$t('word.photo_description')"
                    validation="max:255,length"
                />
                <FormulateInput
                    name="photo_files"
                    type="image"
                    :label="$t('word.photo_files')"
                    :validation-name="$t('word.photo_files')"
                    validation="required|mime:image/jpeg,image/png,image/gif|max_photo:3"
                    upload-behavior="delayed"
                    :uploader="uploader"
                    multiple
                />

                <FormulateInput type="submit" :disabled="loadingStatus">
                    {{ $t('word.upload') }}
                    <FAIcon v-if="loadingStatus" :icon="['fas', 'spinner']" pulse fixed-width />
                </FormulateInput>
            </FormulateForm>
        </div>
    </template>

    <script>
    import { CREATED, UNPROCESSABLE_ENTITY, INTERNAL_SERVER_ERROR } from "../const";

    export default {
        data() {
            return {
                photoForm: {
                    photo_files: null,
                    photo_name: "",
                    photo_description: ""
                },
                uploadErrors: []
            };
        },
        // 算出プロパティでストアのステートを参照
        computed: {
            // loadingストアのstatus
            loadingStatus() {
                return this.$store.state.loading.status;
            }
        },
        methods: {
            // ダミーアップローダ
            uploader: function(file, progress) {
                // ここで処理しないで、uploadPhotoメソッドで処理する
                // プログレスの進行を100%にするのみ
                progress(100);
                return Promise.resolve({});
            },
            async uploadPhoto() {
                // フォームデータオブジェクトを作成
                const formData = new FormData();

                // フォームデータにファイルオブジェクトを追加
                let c = 0;
                this.photoForm.photo_files.files.forEach(item => {
                    formData.append(`photo_files[${c++}]`, item.file);
                });

                // その他の値をセット
                formData.append("photo_name", this.photoForm.photo_name);
                formData.append(
                    "photo_description",
                    this.photoForm.photo_description
                );

                // Api リクエスト
                const response = await axios.post("photos", formData);

                // アップデート成功
                if (response.status === CREATED) {
                    // 成功時はメッセージを出す
                    this.$store.commit("message/setContent", {
                        content: response.data.message,
                        timeout: 6000
                    });

                    // フォームのクリア
                    this.photoForm = {
                        photo_files: null,
                        photo_name: "",
                        photo_description: ""
                    };
                    // バリデーションのクリア
                    this.$formulate.reset("upload_form");
                } else {
                    // エラー時はformに表示
                    this.uploadErrors = response.data.errors;
                }
            }
        }
    };
    </script>

```

### VueFomurateのバリデーションルールを作る

わかりやすいように`server\resources\js\app.js`にグローバルルールを作成

```javascript:server\resources\js\app.js

    ...

    // 宣言
    Vue.use(VueFormulate, {
        // 使用するプラグイン
        plugins: [en, ja],
        // グローバルに使う独自ルール
-       rules: {
-           // ex
-           foobar: ({
-               value
-           }) => ["foo", "bar"].includes(value)
-       },
+       rules: {
+           maxPhoto: (context, limit) => {
+               const value = context.value ? context.value.files.length : 0;
+               return value <= limit
+           }
+       },
+       locales: {
+           en: {
+               maxPhoto(args) {                
+                   return `Photo is ${args[0]} or less`;
+               }
+           },
+           ja: {
+               maxPhoto(args) {
+                   return `写真は${args[0]}ファイルまでです。`;
+               }
+           }
+       }
    });

    ...

```

### 翻訳ファイルに追加

```javascript:server\resources\js\lang\En.js

    ...
    word: {
        hello: 'hello!',
        home: 'Home',
        login: 'Login',
        logout: 'logout',
        english: 'English',
        japanese: 'Japanese',
        register: 'Register',
        forgot_password: 'Forgot Password ?',
        email: 'Email',
        password: 'Password',
        Socialite: 'Socialite',
        name: 'Name',
        password_confirmation: 'Password Confirmation',
        send: 'Send',
        password_reset: 'Password Reset',
        reset: 'Reset',
+       photo: 'Photo',
+       upload: 'Upload',
+       upload_photo: 'Upload Photo',
+       photo_name: 'Photo name',
+       photo_description: 'Photo description',
+       photo_files: 'Photo files',
    },
    ...

```

```javascript:server\resources\js\lang\Ja.js

    ...
    word: {
        hello: 'hello!',
        home: 'Home',
        login: 'Login',
        logout: 'logout',
        english: 'English',
        japanese: 'Japanese',
        register: 'Register',
        forgot_password: 'Forgot Password ?',
        email: 'Email',
        password: 'Password',
        Socialite: 'Socialite',
        name: 'Name',
        password_confirmation: 'Password Confirmation',
        send: 'Send',
        password_reset: 'Password Reset',
        reset: 'Reset',
+       photo: '写真',
+       upload: 'アップロード',
+       upload_photo: '写真をアップロード',
+       photo_name: '写真の名前',
+       photo_description: '写真の説明',
+       photo_files: '写真ファイル',
    },
    ...

```

次はアップデートした写真の一覧を表示するページを作ります。

// <!-- [Laravel mix vue No.11 - ](https://www.aska-ltd.jp/jp/blog/) -->

<script src="https://www.aska-ltd.jp/js/blog.js"></script>
<link href="https://www.aska-ltd.jp/css/blog.css" rel="stylesheet">