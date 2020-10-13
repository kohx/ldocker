<p style="padding-top: 3rem;">こんにちは、あすかのkoheiです。</p>

今回はAWS S3に画像をアップできるようにします。
ついでにバグも直していきます。

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
	<li><a href="https://www.aska-ltd.jp/jp/blog/89">Laravel mix vue No.12 - AWS S3-2 - 画像ファイルの一覧</a></li>
</ul>

# AWS S3 - AWS S3 に写真をアップロード

## サンプル
- このセクションを始める前  
[github ldocker 11](https://github.com/kohx/ldocker/tree/11)  
  
- 完成  
[github ldocker 12](https://github.com/kohx/ldocker/tree/12)

------------------------------------------------------------------------------------------

## フォルダ構成

```text

└─ server
   ├─ app
   |  ├─ Models
+  |  |  ├─ Photo.php
   |  |  └─ User.php
   |  └─ Http
   |       ├─ Requests
+  |       |  ├─ StorePhoto.php
+  |       |  └─ StoreComment.php
+  |       └─ Controllers
+  |          └─ PhotoController.php
   ├─ database
   |  └─ migrations
+  |     ├─ xxxx_xx_xx_xxxx_create_photos_table.php
+  |     ├─ xxxx_xx_xx_xxxx_create_likes_table.php
+  |     └─ xxxx_xx_xx_xxxx_create_comments_table.php
   ├─ routes
   |  ├─ api.php
   |  └─ web.php
   ├─ resources 
   |  ├─ lang
   |  |  ├─ en.json
   |  |  └─ ja.json
   |  └─ js
   |     ├─ components
   |     |  └─ Header.vue
   |     ├─ store
+  |     |  ├─ loading.js
   |     |  ├─ auth.js
   |     |  └─ index.js
   |     ├─ pages
+  |     |  ├─ PhotoUpload.vue
   |     |  ├─ Home.vue
   |     |  ├─ Login.vue
   |     |  └─ Reset.vue
   |     ├─ lang
   |     |  ├─ En.js
   |     |  └─ Js.js
   |     ├─ router.js
   |     └─ app.js 
   └─ .env

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

## S3の設定

### AWS

1. S3 -> 「バケットを作成する」

    パケット名: [パケットネーム]  
    リージョン: アジアパシフィック（東京）  

2. オプションの設定 -> 「次のステップ」

3. アクセス許可の設定 -> 「パブリックアクセスをすべてブロック」 のチェックを外す  
    「現在の設定により、このバケットと中のオブジェクトがパブリックになる可能性があることを了承します。」にチェック  

4. 確認 -> 「パケットを作成」  

5. IAM ユーザー -> 「ユーザーを追加」  
  
6. リソースグループ -> 「プログラムによるアクセス」にチェック -> 次のステップ  

7. アクセス許可の設定 -> 「既存のポリシーを直接アタッチ」をクリック  
                   -> 「AmazonS3FullAccess」ポリシーにチェック   
                    -> 次のステップ

8. タグの追加 -> 次のステップ -> ユーザーの作成   
9. 「アクセスキーID」と「シークレットアクセスキー」を取得  



### ライブラリをインストール

```bash:terminal

    composer require league/flysystem-aws-s3-v3

```

### envに情報追加

`server\.env`

```text:server\.env

    ...

    AWS_ACCESS_KEY_ID=XXXXXXXXXXXXXXXXXXXX
    AWS_SECRET_ACCESS_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
    AWS_DEFAULT_REGION=xxxxxxxxx
    AWS_BUCKET=xxxxxxxxx
    AWS_URL=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

    ...

```

------------------------------------------------------------------------------------------

## データベースの準備

### マイグレーションファイルを作成

```bash:terminal

    # フォトテーブル
    php artisan make:migration create_photos_table --create=photos

    # ライクテーブル
    php artisan make:migration create_likes_table --create=likes

    # コメントテーブル
    php artisan make:migration create_comments_table --create=comments

```

`server\database\migrations\xxxx_xx_xx_xxxx_create_photos_table.php`が作成されるので編集

```php:server\database\migrations\xxxx_xx_xx_xxxx_create_photos_table.php

    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    class CreatePhotosTable extends Migration
    {
        /**
        * Run the migrations.
        *
        * @return void
        */
        public function up()
        {
            Schema::create('photos', function (Blueprint $table) {
                // uuid
                $table->uuid('id')->primary();
                // user id と合わせる
                // 「$table->id()」の場合は「bigIncrements」 または 「unsignedBigInteger」
                $table->unsignedBigInteger('user_id');
                $table->string('name');
                $table->string('description')->nullable();
                $table->string('group_id');
                $table->string('path');
                $table->timestamps();

                // 外部キーの設定
                $table->foreign('user_id')->references('id')->on('users');
            });
        }

        /**
        * Reverse the migrations.
        *
        * @return void
        */
        public function down()
        {
            Schema::dropIfExists('photos');
        }
    }

```

`server\database\migrations\xxxx_xx_xx_xxxx_create_likes_table.php`が作成されるので編集

```php:server\database\migrations\xxxx_xx_xx_xxxx_create_likes_table.php

    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    class CreateLikesTable extends Migration
    {
        /**
        * Run the migrations.
        *
        * @return void
        */
        public function up()
        {
            Schema::create('likes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('photo_id');
                // user id と合わせる
                // 「$table->id()」の場合は「bigIncrements」 または 「unsignedBigInteger」
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                // 外部キーの設定
                $table->foreign('photo_id')->references('id')->on('photos');
                $table->foreign('user_id')->references('id')->on('users');
            });
        }

        /**
        * Reverse the migrations.
        *
        * @return void
        */
        public function down()
        {
            Schema::dropIfExists('likes');
        }
    }

```

`server\database\migrations\xxxx_xx_xx_xxxx_create_comments_table.php`が作成されるので編集

```php:server\database\migrations\xxxx_xx_xx_xxxx_create_comments_table.php

    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    class CreateCommentsTable extends Migration
    {
        /**
        * Run the migrations.
        *
        * @return void
        */
        public function up()
        {
            Schema::create('comments', function (Blueprint $table) {
                $table->increments('id');
                // photo id と合わせるので「uuid」
                $table->uuid('photo_id');
                // user id と合わせる
                // 「$table->id()」の場合は「bigIncrements」 または 「unsignedBigInteger」
                $table->unsignedBigInteger('user_id');
                $table->text('content');
                $table->timestamps();

                // 外部キーの設定
                $table->foreign('photo_id')->references('id')->on('photos');
                $table->foreign('user_id')->references('id')->on('users');
            });
        }

        /**
        * Reverse the migrations.
        *
        * @return void
        */
        public function down()
        {
            Schema::dropIfExists('comments');
        }
    }

```

### マイグレーション実行

```bash:terminal

    php artisan migrate
		# or 
    php artisan migrate:fresh

```

------------------------------------------------------------------------------------------

## モデルの作成

```bash:terminal

    php artisan make:model Models/Photo

```

`server\app\Models\Photo.php`が作成されるので編集

```php:server\app\Models\Photo.php

    <?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Str;

    class Photo extends Model
    {
        /**
         * paginate par page
         * ページネーションのデフォルトパーページ
         *
         * @var integer
         */
        protected $perPage = 10;

        /**
         * プライマリキーの型
         *
         * 初期設定（int）から変更したい場合は $keyType を上書
         */

        // ストリングに変更
        protected $keyType = 'string';

        // important! タイプがストリングの場合はインクリメントをfalse！
        public $incrementing = false;

        // モデルが以下のフィールド以外を持たないようにする
        protected $fillable = [
            'id',
            'name',
            'description',
            'path',
            'group_id',
        ];

        /**
         * constructor
         *
         * コンストラクタで自動的に setId を呼び出し
         *
         * @param array $attributes
         */
        public function __construct(array $attributes = [])
        {
            parent::__construct($attributes);

            // idがあった場合
            if (!Arr::get($this->attributes, 'id')) {

                // uuidをセットする
                $this->setId();
            }
        }

        /**
         * ランダムなID値をid属性に代入する
         */
        private function setId()
        {
            // idにuuidをセット
            $this->attributes['id'] = (string) Str::uuid();
        }

        /**
         * JSONに含める属性
         */
        protected $visible = [
            'id',
            'name',
            'description',
            'url',
            'user',
            'comments',
            'total_like',
            'is_liked',
        ];

        /**
         *  JSONに追加する属性
         */
        protected $appends = [
            'url',
            'total_like',
            'is_liked',
        ];

        /**
         * リレーションシップ - usersテーブル
         *
         * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
         */
        public function user()
        {
            // 「リレーションメソッド名 ＋ _id」をデフォルトの外部キーにしている
            return $this->belongsTo('App\User')
                // 1. 外部キーの名前を変える場合は
                // $this->belongsTo('App\User', 'foreign_key')
                // 2. リレーション先で「id」じゃないキーと紐付ける場合
                // $this->belongsTo('App\User', 'foreign_id', 'relation_id')
                // 3. デフォルトモデルを設定する場合は以下を追加
                // ->withDefault(function ($user, $post) {
                //     $user->name = 'Guest Author';
                // })
            ;
        }

        /**
         * リレーションシップ - commentsテーブル
         *
         * @return \Illuminate\Database\Eloquent\Relations\HasMany
         */
        public function comments()
        {
            return $this->hasMany('App\Models\Comment')
                ->orderBy('id', 'desc');
        }

        /**
         * リレーションシップ - likesテーブル
         *
         * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
         */
        public function likes()
        {
            return $this->belongsToMany(
                'App\User', // related model
                'likes'    // middle table
                // 'user_id', // foreign pivotKey
                // 'photo_id', // related pivotKey
            )
                // likes テーブルにデータを挿入したとき、created_at および updated_at カラムを更新させるための指定
                ->withTimestamps();
        }

        /**
         * アクセサ - url
         *
         * アクセサは「get + 呼び出し名 + Attribute」の形式で記述
         * 利用するには、getとAttributeを取り除いたスネークケースで記述
         * 例）getTestNameAttribute -> test_name
         *
         * ファイルのURLを取得して「url」で呼び出せるようにする
         *
         * @return string
         */
        public function getUrlAttribute()
        {
            // server\config\filesystems.phpで
            // 「'cloud' => env('FILESYSTEM_CLOUD', 's3')」 になっているのでS3が使用される
            return Storage::cloud()->url($this->attributes['path']);
        }

        /**
         * アクセサ - total_like
         * ライクの数を取得して「likes」で呼び出せるようにする
         *
         * @return int
         */
        public function getTotalLikeAttribute()
        {
            // 写真に付いたいいねの総数
            return $this->likes->count();
        }

        /**
         * アクセサ - is_liked
         * ログインユーザがその写真にいいねしているかを取得して「liked_by_user」で呼び出せるようにする
         *
         * @return boolean
         */
        public function getIsLikedAttribute()
        {
            // 「Auth::guest()」でユーザーがログインしていない状態かどうかを確認
            // 「Auth::check()」はユーザーがログインしているかどうかを確認
            if (Auth::guest()) {
                return false;
            }

            // ログインユーザがその写真にいいねしているか
            // containsでコレクションに含まれているかどうかを判定
            return $this->likes->contains(function ($user) {

                return $user->id === Auth::user()->id;
            });
        }
    }

```

ユーザモデル`server\app\User.php`にフォトのリレーションを追加

```php:server\app\User.php

    ...

+       /**
+        * リレーションシップ - photosテーブル
+        *
+        * @return \Illuminate\Database\Eloquent\Relations\HasMany
+        */
+       public function photos()
+       {
+           return $this->hasMany('App\Models\Photo');
+       }
    }
```

------------------------------------------------------------------------------------------

## リクエストクラスの作成

ソーシンされたデータをチェックするためにフォトのチェック用と、コメントのチェック用のフォームリクエストクラスを作成

```bash:terminal

    php artisan make:request StorePhoto
    php artisan make:request StoreComment

```

`server\app\Http\Requests\StorePhoto.php`が作成されるので編集

```php:

    <?php

    namespace App\Http\Requests;

    use Illuminate\Foundation\Http\FormRequest;

    class StorePhoto extends FormRequest
    {
        /**
         * Determine if the user is authorized to make this request.
         * ユーザーにこのリクエストを行う権限があるかどうかをチェックする
         *
         * @return bool
         */
        public function authorize()
        {
            return true;
        }

        /**
         * Prepare the data for validation.
         *
         * @return void
         */
        protected function prepareForValidation()
        {
            //
        }

        /**
         * Get the validation rules that apply to the request.
         * バリデーションをここに書く
         *
         * @return array
         */
        public function rules()
        {
            return [
                'photo_name' => 'required|max:255',
                'photo_description' => 'max:255',
                // 必須入力、ファイル、ファイルタイプが jpg,jpeg,png,gif であることをルールとして定義
                // photo_filesが配列なので「.*」ですべてをチェック
                'photo_files.*' => 'image|mimes:jpeg,bmp,png',
            ];
        }

        /**
         * エラーメッセージのカスタマイズ
         * エラーメッセージのカスタマイズをする場合は以下のように書く
         * @return array
         */
        public function messages()
        {
            return [
                // 'photo.required' => __('Please enter your name.'),
            ];
        }

        /**
         * 独自処理を追加する
         * 独自処理を追加する場合は以下のように書く
         * @param $validator
         */
        public function withValidator($validator)
        {
            // $validator->after(function ($validator) {
            // if ($this->somethingElseIsInvalid()) {
            //     $validator->errors()->add('field', __('Something is wrong with this field!'));
            // }
            // });
        }
    }

```

`server\app\Http\Requests\StoreComment.php`が作成されるので編集

```php:server\app\Http\Requests\StoreComment.php

<?php

    namespace App\Http\Requests;

    use Illuminate\Foundation\Http\FormRequest;

    class StoreComment extends FormRequest
    {
        /**
         * Determine if the user is authorized to make this request.
         *
         * @return bool
         */
        public function authorize()
        {
            return true;
        }

        /**
         * Get the validation rules that apply to the request.
         *
         * @return array
         */
        public function rules()
        {
            return [
                'content' => 'required|max:500',
            ];
        }
    }

```

------------------------------------------------------------------------------------------

## フォトコントローラの作成

```bash:terminal

    php artisan make:controller PhotoController

```

`server\app\Http\Controllers\PhotoController.php`が作成されるので編集

```php:server\app\Http\Controllers\PhotoController.php

    <?php

    namespace App\Http\Controllers;

    // models
    use App\Models\Photo;

    // requests
    use App\Http\Requests\StorePhoto;

    // facades
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Storage;

    class PhotoController extends Controller
    {
        public function __construct()
        {
            // ミドルウェアをクラスに追加
            // 認証が必要
            $this->middleware('auth')
                // 認証を除外するアクション
                ->except(['index', 'download', 'show']);
        }

        /**
         * photo store
         * 写真投稿
         * リクエストは「StorePhoto」を使う
         *
         * @param StorePhoto $request
         * @return \Illuminate\Http\Response
         */
        public function store(StorePhoto $request)
        {
            // アップデートした画像のS3のパス
            $updatePhotos = [];

            // 名前と説明を取得
            $photoName = $request->input('photo_name');
            $photoDescription = $request->input('photo_description');

            // データベースエラー時にファイル削除を行うためトランザクションを利用する
            // Transaction Begin
            DB::beginTransaction();
            try {

                // グループID
                $groupId = null;

                foreach ($request->file('photo_files') as $key => $photoFile) {
                    // extension()メソッドでファイルの拡張子を取得する
                    $extension = $photoFile->extension();

                    // モデルインスタンス作成
                    $photo = new Photo([
                        'name' => $photoName,
                        'description' => $photoDescription,
                    ]);

                    // グループキーを保持
                    if ($key === 0) {
                        $groupId = $photo->id;
                    }

                    // グループキーをセット
                    $photo->group_id = $groupId;
                    
                    // インスタンス生成時に割り振られたランダムなID値と
                    // 本来の拡張子を組み合わせてファイル名とする
                    $filename = "{$photo->id}.{$extension}";

                    // パスをセット
                    $photo->path = "photos/{$filename}";

                    // S3にファイルを保存する
                    // putFileAsの引数は( ディレクトリ, ファイルデータ, ファイルネーム, 公開 )
                    // 第三引数の'public'はファイルを公開状態で保存するため
                    // 返り値はS3のパス
                    $updatePhotos[] = Storage::cloud()->putFileAs('photos', $photoFile, $filename, 'public');
                    // ユーザのフォトにインサート
                    Auth::user()->photos()->save($photo);
                }

                // Transaction commit
                DB::commit();
            } catch (\Throwable $exception) {

                // Transaction Rollback
                DB::rollBack();

                // DBとの不整合を避けるためアップロードしたファイルを削除
                foreach ($updatePhotos as $updatePhoto) {
                    Storage::cloud()->delete($updatePhoto);
                }

                // log
                \Log::info($exception);

                // エラーレスポンス
                return response()->json(['errors' => [__('upload failed.')]], 500);
            }

            // リソースの新規作成なので
            // レスポンスコードは201(CREATED)を返却する
            return response()->json(['message' => __('upload success.')], 201);
        }
    }

```

------------------------------------------------------------------------------------------

## ルートを追加

`server\routes\api.php`を編集

```php:server\routes\api.php

...

    Route::middleware(['language'])->group(function () {

        ...

        // 写真のアップロード
+       Route::post('/photos', 'PhotoController@store')->name('photo.store');
    });
		
    ...
		
```

------------------------------------------------------------------------------------------

## Vueに新しいページを追加

### その前に前回のバグフィックスと追加

修正するバグ
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

色んな所に`this.$router.push()`と`<RouterLink to="xxx">`がかかれているので、ルートネームで指定するように修正する。

`server\resources\js\App.vue`  
`server\resources\js\components\Header.vue`  
`server\resources\js\pages\Login.vue`  
`server\resources\js\pages\Reset.vue`
`server\resources\js\router.js`

```javascript:ex

# App.vue
-   this.$router.push("/500");
+   this.$router.push({ name: "system-error" });

-   this.$router.push("/login");
+   this.$router.push({ name: "login" });

-   this.$router.push("/not-found");
+   this.$router.push({ name: "not-found" });

# Header.vue
-   <RouterLink to="/">
+   <RouterLink :to="{ name: 'home'}">

-   <RouterLink v-if="!isLogin" to="/login">
+   <RouterLink v-if="!isLogin" :to="{ name: 'login'}">

-   this.$router.push("/login");
+   this.$router.push({ name: "login" });

# Login.vue`  

-   this.$router.push("/");
+   this.$router.push({ name: "home" });

# Reset.vue

-   this.$router.push("/");
+   this.$router.push({ name: "home" });

# router.js

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

+           // セレクトオプションを翻訳
+           // bugfix: ここで入れ直さないとセレクトの中身が変更されない
+               this.langList.en = this.$i18n.tc("word.english");
+               this.langList.ja = this.$i18n.tc("word.japanese");

+               // 現在のルートネームを取得
+               const currentRoute = this.$route.name;
+
+               // ルートネームがログインのときのみクリア
+               if(currentRoute === 'login'){
+                   // フォームをクリア
+                   this.$formulate.reset("login_form");
+                   this.$formulate.reset("registe_form");
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

フォーム全体に出すエラーをセットするために
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
-               <FormulateForm name="login_form" v-model="loginForm" @submit="login">
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
-               <FormulateForm name="register_form" v-model="registerForm" @submit="register">
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
-               <FormulateForm name="forgot_form" v-model="forgotForm" @submit="forgot">
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
+   import PhotoUpload from "./pages/PhotoUpload.vue";
    import Login from "./pages/Login.vue";
    import Reset from "./pages/Reset.vue";
    import SystemError from "./pages/errors/SystemError.vue";
    import NotFound from "./pages/errors/NotFound.vue";

    Vue.use(VueRouter);

    // パスとページの設定
    const routes = [
        // home
        {
            ...
        },
+       // photo-upload
+       {
+           // urlのパス
+           path: "/photo-upload",
+           // ルートネーム
+           name: 'photo-upload',
+           // インポートしたページ
+           component: PhotoUpload,
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

`server\resources\js\components\Header.vue`に追加

```javascript:server\resources\js\components\Header.vue

    <template>
        <header class="header">
            <!-- リンクを設定 -->
            <RouterLink :to="{ name: 'home'}">
                <FAIcon :icon="['fas', 'home']" size="lg" />
                {{ $t('word.home') }}
            </RouterLink>
+           <RouterLink v-if="isLogin" :to="{ name: 'photo-upload'}">
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

`server\resources\js\pages\PhotoUpload.vue`を作成

```javascript:server\resources\js\pages\PhotoUpload.vue

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

Vue側の翻訳ファイル
`javascript:server\resources\js\lang\en.js`と`javascript:server\resources\js\lang\Ja.js`を修正

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

Laravel側の翻訳ファイル
`server\resources\lang\en.json`と`server\resources\lang\ja.json`を修正

```php:server\resources\lang\en.json
		{
        ...
		    "click this link to go to password reset.": "こちらのリンクをクリックして、パスワードリセットへ移動してください。",
		    "upload failed.": "upload failed."
    }
```

```php:server\resources\lang\ja.json
		{
        ...
		    "click this link to go to password reset.": "こちらのリンクをクリックして、パスワードリセットへ移動してください。",
		    "upload failed.": "アップ失敗。"
    }
```

## 確認

`http://localhost:3000/photo-upload`に移動してアップロードしてみる
AWS S3を確認して写真がアップされていればOK！

## その他の修正

今回の記事とは関係ないけど修正したところ

### ベースURLを`.env`から取得するように変更

```text:.env

    APP_NAME=ldocker
    APP_ENV=local
    APP_KEY=base64:mkXsIjPTYgpGhJ4vbkGyeQt9uRe+hm02LlUkJHYxnOM=
    APP_DEBUG=true
    APP_URL=http://localhost:3000
		
+   # プレフィクスが「MIX_」でないとVue側で「process.env.MIX_URL」のように取得できない
+   MIX_URL="${APP_URL}"

    ....
		
```

```javascript:server\resources\js\bootstrap.js
    ...
    /*
     * axios
     * Ajax通信にはこれを使う
     */
    window.axios = require("axios");

    // Ajaxリクエストであることを示すヘッダーを付与する
    window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

+   // ベースURLを.envから取得
+   // MIX_URL="${APP_URL}"を.envに追加
+   // このときのプレフィックスは「MIX_」でないと取得できない
+   const baseUrl = process.env.MIX_URL;

-   window.axios.defaults.baseURL = 'api/';
+   window.axios.defaults.baseURL = `${baseUrl}/api/`;

```

### バージョン付け／キャッシュ対策

versionメソッドは自動的に全コンパイルファイルのファイル名へ一意のハッシュを追加し、キャッシュを更新できるようにする

``を編集して`npm run production`を実行するときのみ、バージョン付けするように指示

```javascript:webpack.mix.js
    mix.js("resources/js/app.js", "public/js")
        .sass('resources/sass/main.scss', 'public/css')
        .styles([
            'resources/css/reset.css',
            'resources/css/style.css',
            'node_modules/font-awesome-animation/dist/font-awesome-animation.min.css'
        ], 'public/css/app.css');

+   // 本番環境だけバージョンを自動的に付与してキャッシュを自動更新できるようにする
+   if (mix.inProduction()) {
+       mix.version();
+   }

    ...
		
```

次はアップデートした写真の一覧を表示するページを作ります。

[Laravel mix vue No.12 - AWS S3-2 - 画像ファイルの一覧](https://www.aska-ltd.jp/jp/blog/89)

<script src="https://www.aska-ltd.jp/js/blog.js"></script>
<link href="https://www.aska-ltd.jp/css/blog.css" rel="stylesheet">