<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

今回はAWS S3にアップしたファイルの一覧と詳細を作ります。

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
  <li><a href="https://www.aska-ltd.jp/jp/blog/80">Laravel mix vue No.11 - AWS S3 - AWS S3 に写真をアップロード</a></li>
  <li><a href="https://www.aska-ltd.jp/jp/blog/89">Laravel mix vue No.12 - Web Application - 画像ファイルの一覧と詳細</a></li>
</ul>

# AWS S3 - AWS S3 に写真をアップロード

## サンプル
- このセクションを始める前  
[github ldocker 12](https://github.com/kohx/ldocker/tree/11)  
  
- 完成  
[github ldocker 13](https://github.com/kohx/ldocker/tree/12)

------------------------------------------------------------------------------------------

## フォルダ構成

```text

└─ server
   ├─ app
   |  └─ Http
   |       └─ Controllers
   |          └─ PhotoController.php
   ├─ routes
   |  └─ api.php
   └─ resources 
      └─ js
         ├─ components
+        |  ├─ Photo.vue
+        |  └─ Pagination.vue
         ├─ pages
+        |  ├─ PhotoDetail.vue
         |  └─ Home.vue
         └─ router.js

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
## ルートを追加

### Apiのルートを追加

`server\routes\api.php`を編集

```php:server\routes\api.php

    ...

        // 写真投稿
        Route::post('/photos', 'PhotoController@create')->name('photo.create');

+       // 写真一覧
+       Route::get('/photos', 'PhotoController@index')->name('photo.index');

+       // 写真詳細
+       Route::get('/photos/{id}', 'PhotoController@show')->name('photo.show');

    ...

```

### Vueのルートを追加

`server\resources\js\router.js`を編集

```javascript:server\resources\js\router.js

    ...

    {
        // urlのパス
        path: "/",
        // ルートネーム
        name: 'home',
        // インポートしたページ
        component: Home,
        // https://router.vuejs.org/ja/guide/essentials/passing-props.html
        // クエリストリングをプロップスとして返す
        props: route => {
            const page = route.query.page;
            // 整数と解釈されない値は「1」を返却
            return {
                page: /^[1-9][0-9]*$/.test(page) ? page * 1 : 1
            };
        }
    },
+   {
+       path: "/photos/:id",
+       component: PhotoDetail,
+       // props: true は :id を props として受け取ることを意味
+       // props を true に設定しているので、この :id の値が <PhotoDetail> コンポーネントに props として渡される
+       // リンクは「{name: 'photo', params: { id: item.id }}」このように渡す
+       props: true
+   },

    ...

```

## フォトコントローラに写真一覧と写真詳細を追加

`server\app\Http\Controllers\PhotoController.php`を編集

```php:server\app\Http\Controllers\PhotoController.php

    ...
    
    class PhotoController extends Controller
    {
        public function __construct()
        {
            // 認証が必要
            $this->middleware('auth')
                ->except(['index', 'download', 'show']); // 認証を除外するアクション
        }

+       /**
+        * 写真一覧
+        */
+       public function index()
+       {
+           // get の代わりに paginate を使うことで、
+           // JSON レスポンスでも示した total（総ページ数）や current_page（現在のページ）といった情報が自動的に追加される
+           $photos = Photo::with(['user'])
+               ->orderBy(Photo::CREATED_AT, 'desc')
+               ->paginate();
+
+           return $photos;
+       }

+       /**
+        * 写真詳細
+        * @param string $id
+        * @return Photo
+        */
+       public function show(string $id)
+       {
+           // get photo
+           // 'comments.author'でcommentsと、そのbelongsToに設定されている'author'も取得
+           $photo = Photo::with(['user'])->findOrFail($id);
+           return $photo;
+       }

        ...
    }

```

## Vue側で「HOME」に一覧を作成

### Photoコンポーネントを作る

画像1つを表示させるコンポーネントを`server\resources\js\components\Photo.vue`を作成

```HTML:server\resources\js\components\Photo.vue

    <template>
        <div class="photo">

            <RouterLink
                class="photo__overlay"
                :to="{ name: 'photo', params: { id: item.id } }"
                :title="`View the photo by ${item.user.name}`"
            >
                <!-- 画像 -->
                <figure class="image-wrap">
                    <img
                        class="image-wrap__image"
                        :src="item.url"
                        :alt="`Photo by ${item.user.name}`"
                    />
                </figure>
                <!-- 画像 -->

                <!-- photo username -->
                <div class="photo__username">{{ item.user.name }}</div>
                <!-- /photo username -->
            </RouterLink>

        </div>
    </template>

    <script>
    export default {
        // プロップスとして画像オブジェクトをもらう
        props: {
            item: {
                type: Object,
                required: true
            }
        },
    };
    </script>

```

### ページネーションのコンポーネントを作成

画像を一定量表示するのでページネーションのコンポーネント`server\resources\js\components\Pagination.vue`を作成

```HTML:server\resources\js\components\Pagination.vue

    <template>
        <div class="pagination">
            <!-- 前へのリンク -->
            <!-- urlを引き継ぐので以下のように設定 -->
            <!-- 現在のページはクエリストリング「page」で指定 -->
            <RouterLink
                v-if="! isFirstPage"
                :to="`/?page=${currentPage - 1}`"
                class="button"
            >&laquo; prev</RouterLink>
            <!-- 次へのリンク -->
            <RouterLink
                v-if="! isLastPage"
                :to="`/?page=${currentPage + 1}`"
                class="button"
            >next &raquo;</RouterLink>
        </div>
    </template>

    <script>
    export default {
        // プロップスとして現在のページとラストページをもらう
        props: {
            currentPage: {
                type: Number,
                required: true
            },
            lastPage: {
                type: Number,
                required: true
            }
        },
        computed: {
            // クエリストリングで渡されたページが最初のページかチェック
            isFirstPage() {
                return this.currentPage === 1;
            },
            // クエリストリングで渡されたページが最後のページかチェック
            isLastPage() {
                return this.currentPage === this.lastPage;
            }
        }
    };
    </script>

```

### Photoコンポーネントをつかって一覧の表示

`server\resources\js\pages\Home.vue`を編集

```HTML:server\resources\js\pages\Home.vue

    <template>
        <div class="page">

          <h1>{{ $t('word.home') }}</h1>

+         <div class="photo-list">
+             <div class="grid">
+                 <Photo
+                     class="grid__item"
+                     v-for="photo in photos"
+                     :key="photo.id"
+                     :item="photo"
+                 />
+             </div>
+             <Pagination :current-page="currentPage" :last-page="lastPage" />
+           </div>
+       </div>
    </template>

+   <script>
+   import { OK } from "../const";
+   // コンポーネントをインポート
+   import Photo from "../components/Photo.vue";
+   import Pagination from "../components/Pagination.vue";
+
+   export default {
+       // コンポーネントを登録
+       components: {
+           Photo,
+           Pagination
+       },
+       // プロップスとして表示するページをもらう
+       props: {
+           page: {
+               type: Number,
+               required: false,
+               default: 1
+           }
+       },
+       data() {
+           return {
+               // 表示する画像の配列
+               photos: [],
+               // 現在のページ
+               currentPage: 0,
+               // 最後のページ
+               lastPage: 0
+           };
+       },
+       methods: {
+           /**
+            * fetch photos
+            * 表示する画像を取得
+            */
+           async fetchPhotos() {
+               // request api
+               const response = await axios.get(`photos/?page=${this.page}`);
+
+               if (response.status !== OK) {
+                   // commit error store
+                   this.$store.commit("error/setCode", response.status);
+
+                   return false;
+               }
+
+               // 画像をデータにセット
+               this.photos = response.data.data;
+               // 現在のページをセット
+               this.currentPage = response.data.current_page;
+               // 最後のページをデータにセット
+               this.lastPage = response.data.last_page;
+           },
+       },
+       watch: {
+           // ページネーションで使い回すので $route の監視ハンドラ内で fetchPhotos を実行
+           $route: {
+               async handler() {
+                   await this.fetchPhotos();
+               },
+               // コンポーネントが生成されたタイミングでも実行
+               immediate: true
+           }
+       }
+   };
+   </script>

```

## 詳細ページの追加

`server\resources\js\pages\PhotoDetail.vue`を作成

```HTML:server\resources\js\pages\PhotoDetail.vue

<template>
    <!-- photo-detail -->
    <div
        v-if="photo"
        class="photo-detail"
        :class="{ 'photo-detail--column': fullWidth }"
    >
        <!-- pane image -->
        <figure
            class="photo-detail__pane photo-detail__image"
            @click="fullWidth = !fullWidth"
        >
            <img :src="photo.url" alt />
            <figcaption>Posted by {{ photo.user.name }}</figcaption>
        </figure>
        <!-- /pane image -->
    </div>
    <!-- /photo-detail -->
</template>

<script>
import { OK, CREATED, UNPROCESSABLE_ENTITY } from "../const";

export default {
    props: {
        // routeで設定された :id の値が入る
        id: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            loading: false,
            photo: null,
            fullWidth: false,
        };
    },
    computed: {
        isLogin() {
            return this.$store.getters["auth/check"];
        },
    },
    methods: {
        async fetchPhoto() {
            // api request
            const response = await axios.get(`photos/${this.id}`);

            // error
            if (response.status !== OK) {
                // commit errer store
                this.$store.commit("error/setCode", response.status);
                return false;
            }

            // set photo data
            this.photo = response.data;
        },
    },
    watch: {
        // 詳細ページで使い回すので $route の監視ハンドラ内で fetchPhoto を実行
        $route: {
            async handler() {
                await this.fetchPhoto();
            },
            // コンポーネントが生成されたタイミングでも実行
            immediate: true,
        },
    },
};
</script>

```

## その他気になったところ修正

### クラスつけわすれ

```HTML:server\resources\js\App.vue

    <template>
-      <div>
+      <div class="wrapper">

```

### Illuminate\Http\Requestをuse

```php:api.php    
-   Route::get('/refresh-token', function (Illuminate\Http\Request $request) {
+   Route::get('/refresh-token', function (Request $request) {
            $request->session()->regenerateToken();
            return response()->json();
        })->name('refresh-token');

        // 写真のアップロード
        Route::post('/photos', 'PhotoController@store')->name('photo.store');
    });

    // set lang
-   Route::get('/set-lang/{lang}', function (Illuminate\Http\Request $request, $lang) {
+   Route::get('/set-lang/{lang}', function (Request $request, $lang) {
        $request->session()->put('language', $lang);
        return response()->json();
    })->name('set-lang');

```

次は今回作ったものにライクボタンとダウンロードボタンを追加していこうと思います。

<!-- [Laravel mix vue No.12 - Web Application2 - ライクボタンとダウンロードボタンの実装](https://www.aska-ltd.jp/jp/blog/89) -->

<script src="https://www.aska-ltd.jp/js/blog.js"></script>
<link href="https://www.aska-ltd.jp/css/blog.css" rel="stylesheet">