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
  <li><a href="https://www.aska-ltd.jp/jp/blog/90">Laravel mix vue No.13 - Web Application2 - ライクボタンとダウンロードボタンの実装</a></li>
</ul>

# Application2 - ライクボタンとダウンロードボタンの実装

## サンプル
- このセクションを始める前
[github ldocker 13](https://github.com/kohx/ldocker/tree/13)

- 完成
[github ldocker 14](https://github.com/kohx/ldocker/tree/14)

------------------------------------------------------------------------------------------

## フォルダ構成

```text

└─ server
   ├─ app
   |  ├─ Http
   |  |    ├─ Controllers
   |  |    |  └─ PhotoController.php
   |  |    └─ Requests
   |  |       └─ StoreComment.php
   |  └─ Models
   |     ├─ Photo.php
+  |     └─ Comment.php
   ├─ routes
   |  ├─ api.php
   |  └─ web.php
   └─ resources
      └─ js
         ├─ components
         |  └─ Photo.vue
         ├─ pages
         |  └─ PhotoDetail.vue
         ├─ lang
         |  └─ En.js
         |  └─ Ja.js
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

## Apiルートの追加

`server\routes\api.php`にコメントとライクのルートを追加

```php:server\routes\api.php

    // 写真詳細
    Route::get('/photos/{id}', 'PhotoController@show')->name('photo.show');

+   // コメント
+   Route::post('/photos/{photo}/comments', 'PhotoController@storeComment')->name('photo.comment');

+   // ライク
+   Route::put('/photos/{id}/like', 'PhotoController@like')->name('photo.like');

+   // ライク解除
+   Route::delete('/photos/{id}/like', 'PhotoController@unlike')->name('photo.unlike');

```

`server\routes\web.php`にダウンロードのリンクを追加

```php:server\routes\web.php

    // socialite 各プロバイダからのコールバックを受けるルート
    Route::get('/login/{provider}/callback', 'Auth\LoginController@handleProviderCallback');

+   // 写真ダウンロード
+   Route::get('/photos/{photo}/download', 'PhotoController@download');

```

## モデルの作成と修正

### コメントモデルの作成
テーブル名は`comments`なのでクラス名は`Comment`として`Models`フォルダに作成

```bash:terminal

php artisan make:model Models/Comment

```

`server\app\Models\Comment.php`が作られるので以下のように編集

```php:server\app\Models\Comment.php

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    /**
     * JSONに含める属性
     *
     * @var array
     */
    protected $visible = [
        'user', 'content',
    ];

    /**
     * リレーションシップ - usersテーブル
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\User');
        // or
        // return $this->belongsTo(
        //     'App\User', // related model
        //     'user_id',  // foreignKey
        //     'id',       // ownerKey
        //     'users'     // relation table
        // );
    }
}

```

### フォトモデルのコメント修正

`server\app\Models\Photo.php`のコメントが間違っていたので修正

```php:server\app\Models\Photo.php

-    * ライクの数を取得して「likes」で呼び出せるようにする
+    * ライクの数を取得して「total_like」で呼び出せるようにする

-    * ログインユーザがその写真にいいねしているかを取得して「liked_by_user」で呼び出せるようにする
+    * ログインユーザがその写真にいいねしているかを取得して「is_liked」で呼び出せるようにする

```

## コントローラーの修正

`server\app\Http\Controllers\PhotoController.php`を編集

```php:server\app\Http\Controllers\PhotoController.php

    ...

+   use App\Models\Comment;
+   use App\Http\Requests\StoreComment;
+   use Illuminate\Http\Request;
+   use Throwable;

    ...

    /**
     * 写真一覧
     * get /api/photos photo.list
     */
    public function index()
    {
        // user情報も一緒に取得
-       $photos = Photo::with(['user'])
+       $photos = Photo::with(['user', 'likes'])
            // 新しいもの順に取得
            ->orderBy(Photo::CREATED_AT, 'desc')
            // get()の代わりにpaginateを使うことで、JSON レスポンスに
            // total（総ページ数）や current_page（現在のページ）といった情報が自動的に追加される
            ->paginate();

        return $photos;
    }

    /**
     * 写真詳細
     * get /api/photos/{id} photo.show
     *
     * モデルバインディングで取得しないで、withつきで取得する
     * @param string $id
     * @return Photo
     */
    public function show(string $id)
    {
        // get photo
        // 'comments.author'でcommentsと、そのbelongsToに設定されている'user'も取得
-       $photo = Photo::with(['user'])->findOrFail($id);
        // ライクとコメントの所有者を追加
+       $photo = Photo::with(['user', 'comments.user', 'likes'])
+           // 「findOrFail」でIDが存在しない場合は404が自動的に返される
+           ->findOrFail($id);

        return $photo;
    }

    /**
     * 写真投稿
     * post /api/photos photo.store
     *
     * リクエストは「StorePhoto」を使う
     *
     * @param StorePhoto $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePhoto $request)
    {
        ...

-       } catch (\Throwable $exception) {
+       } catch (Throwable $exception) {

        ...
    }

+   /**
+    * 写真ダウンロード
+    * get /photos/{photo}/download download
+    *
+    * 引数にモデルを指定したらモデルバインディングでモデルを取得できる
+    * https://laravel.com/docs/7.x/routing#route-model-binding
+    *
+    * @param Photo $photo
+    * @return \Illuminate\Http\Response
+    */
+   public function download(Photo $photo)
+   {
+       // 写真の存在チェック
+       if (!Storage::cloud()->exists($photo->path)) {
+           // 写真なければ404
+           abort(404);
+       }
+
+       // 拡張子を取得
+       $extension =  pathinfo($photo->path, PATHINFO_EXTENSION);
+
+       // コンテンツタイプにapplication/octet-streamを指定すればダウンロードできる
+       // Content-Dispositionヘッダーにattachmentを指定すれば、コンテンツタイプが"application/octet-stream"でなくても、ダウンロードできる
+       // filenameに指定した値が、ダウンロード時のデフォルトのファイル名になる
+       $headers = [
+           'Content-Type' => 'application/octet-stream',
+           'Content-Disposition' => "attachment; filename={$photo->name}.{$extension}",
+       ];
+
+       // ファイルを取得
+       $file = Storage::cloud()->get($photo->path);
+
+       return response($file, 200, $headers);
+   }

+   /**
+    * ライク
+    * put /photos/{id}/like
+    *
+    * @param String $photoId
+    * @return array
+    */
+   public function like($photoId)
+   {
+       // ない場合、または方がおかしい場合には500エラー
+       if (!$photoId ) {
+           abort(500);
+       }
+
+       // likes付きで写真を取得
+       $photo = Photo::with('likes')->findOrFail($photoId);
+
+       // 多対多リレーションなのでattachヘルパメソッドをつかって追加
+       $photo->likes()->attach(Auth::user()->id);
+
+       // 現在のis_likedとtotal_likeを返す
+       return ["is_liked" => true, "total_like" => $photo->likes()->count()];
+   }

+   /**
+    * ライク解除
+    * delete /photos/{id}/like
+    *
+    * @param String $photoId
+    * @return array
+    */
+   public function unlike($photoId)
+   {
+       // ない場合、または方がおかしい場合には500エラー
+       if (!$photoId ) {
+           abort(500);
+       }
+
+       // likes付きで写真を取得
+       $photo = Photo::with('likes')->findOrFail($photoId);
+
+       // 多対多リレーションなのでattachヘルパメソッドをつかって削除
+       $photo->likes()->detach(Auth::user()->id);
+
+       // 現在のis_likedとtotal_likeを返す
+       return ["is_liked" => false, "total_like" => $photo->likes()->count()];
+   }

+   /**
+    * コメント投稿
+    * post /api/photos/{photo}/comments photo.store_comment
+    *
+    * モデルバインディングで取得
+    * @param Photo $photo
+    * @param StoreComment $request
+    * @return \Illuminate\Http\Response
+    */
+   public function storeComment(Photo $photo, StoreComment $request)
+   {
+       // コメントモデルを作成
+       $comment = new Comment();
+
+       // 値をセット
+       $comment->content = $request->input('content');
+       $comment->user_id = Auth::user()->id;
+
+       // データベースに反映
+       $photo->comments()->save($comment);
+
+       // userリレーションをロードするためにコメントを取得しなおす
+       $new_comment = Comment::with('user')->find($comment->id);
+
+       return response($new_comment, 201);
+   }

+   /**
+    * edit comment
+    * put /api/photos/comments/{comment} photo.edit_comment
+    *
+    * 引数にモデルを指定したらモデルバインディングでモデルを取得できる
+    */
+   public function editComment(Comment $comment, StoreComment $request)
+   {
+       // 値をセット
+       $comment->content = $request->input('content');
+
+       // データベースに反映
+       $comment->save();
+
+       // userリレーションをロードするためにコメントを取得しなおす
+       $new_comment = Comment::with('user')->find($comment->id);
+
+       return response($new_comment, 200);
+   }

+   /**
+    * delete comment
+    * delete /api/photos/comments/{comment} photo.delete_comment
+    *
+    * 引数にモデルを指定したらモデルバインディングでモデルを取得できる
+    */
+   public function deleteComment(Comment $comment)
+   {
+       // データベースに反映
+       $comment->delete();
+
+       // TODO:: 未実装
+       return response('', 200);
+   }

    ...

```

## リクエストの修正

`server\app\Http\Requests\StoreComment.php`に`prepareForValidation`がなかったので追加する

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

+   /**
+    * Prepare the data for validation.
+    *
+    * @return void
+    */
+   protected function prepareForValidation()
+   {
+       //
+   }

    ...
}
```

## Vue側でライクボタンとダウンロードボタンを作る

### Photoコンポーネントに追加する

`server\resources\js\components\Photo.vue`を編集

```HTML:server\resources\js\components\Photo.vue

    <template>
        <div class="photo">
            <RouterLink
                class="photo__overlay"
                :to="{ name: 'photo', params: { id: item.id } }"
-               :title="`View the photo by ${item.user.name}`"
+               :title="$t('sentence.photo_by', {user: item.user.name})"
            >
                <!-- photo -->
                <figure class="image-wrap">
                    <img
                        class="image-wrap__image"
                        :src="item.url"
-                       :alt="`Photo by ${item.user.name}`"
+                       :alt="$t('sentence.photo_by', {user: item.user.name})"
                    />
                </figure>
                <!-- photo -->

+               <!-- photo controls -->
+               <div class="photo__controls">
+                   <!-- like -->
+                   <button
+                       class="photo__button photo__button--like"
+                       :class="{ 'photo__button--liked': item.is_liked }"
+                       title="Like photo"
+                       @click.prevent="like"
+                   >
+                       <FAIcon :icon="['fas', 'heart']" class="like fa-fw" />
+                   </button>
+                   <div class="photo__total-like">{{ total_like }} </div>
+                   <!-- /like -->
+
+                   <!-- download -->
+                   <!-- 「@click.stop」でリロードさせない -->
+                   <a
+                       class="photo__button"
+                       title="Download photo"
+                       @click.stop
+                       :href="`/photos/${item.id}/download`"
+                   >
+                       <FAIcon :icon="['fas', 'file-download']" class="fa-fw" />
+                   </a>
+                   <!-- /download -->
+               </div>
+               <!-- /photo controls -->

                <!-- photo username -->
-               <div class="photo__username">{{ item.user.name }}</div>
+               <div class="photo__username">{{ $t('sentence.photo_by', { user: item.user.name }) }}</div>
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
+       methods: {
+           /**
+            * like
+            */
+           async like() {
+               const isLogin = this.$store.getters["auth/check"];
+
+               if (!isLogin) {
+                   alert(this.$i18n.tc("sentence.login_before_like"));
+                   return false;
+               }
+
+               // request api ライクを追加する処理
+               let response;
+               if (this.is_liked) {
+                   response = await axios.delete(`photos/${this.item.id}/like`);
+               } else {
+                   response = await axios.put(`photos/${this.item.id}/like`);
+               }
+
+               // if error
+               if (response.status !== OK) {
+                   // set status to error store
+                   this.$store.commit("error/setCode", response.status);
+                   return false;
+               }
+
+               this.is_liked = response.data.is_liked;
+               this.total_like = response.data.total_like;
+           },
+       }
    };
    </script>

```

## 詳細ページに追加

`server\resources\js\pages\PhotoDetail.vue`を編集

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
-              <figcaption> {{ photo.user.name }}</figcaption>
+              <figcaption> {{ $t('sentence.photo_by', { user: photo.user.name }) }}</figcaption>
            </figure>
            <!-- pane image -->

+           <!-- pane -->
+           <div class="photo-detail__pane">
+               <!-- like -->
+               <button
+                   class="photo__button photo-detail__button--like"
+                   :class="{ 'photo-detail__button--liked': photo.is_liked }"
+                   title="Like photo"
+                   @click="like"
+               >
+                   <FAIcon :icon="['fas', 'heart']" class="fa-fw" />
+                   {{ photo.total_like }}
+               </button>
+               <!-- /like -->
+
+               <!-- download -->
+               <a :href="`/photos/${photo.id}/download`" class="button" title="Download photo">
+                   <FAIcon :icon="['fas', 'file-download']" class="fa-fw" />{{$t('word.download')}}
+               </a>
+               <!-- /download -->
+
+               <!-- comment -->
+               <h2 class="photo-detail__title">
+                  <FAIcon :icon="['far', 'comment-dots']" class="fa-fw" />{{$t('word.comments')}}
+               </h2>
+               <!-- comment list -->
+               <ul v-if="photo.comments.length > 0" class="photo-detail__comments">
+                   <li
+                      v-for="comment in photo.comments"
+                       :key="comment.content"
+                       class="photo-detail__comment-item"
+                   >
+                       <p class="photo-detail__comment-body">{{ comment.content }}</p>
+                       <p class="photo-detail__comment-info">{{ comment.user.name }}</p>
+                   </li>
+           </ul>
+           <p v-else>{{$t('sentence.no_comments_yet')}}</p>
+           <!-- /comment list -->
+           <form v-if="isLogin" @submit.prevent="addComment" class="form">
+               <!-- error message -->
+               <div v-if="commentErrors" class="errors">
+                   <ul v-if="commentErrors.content">
+                       <li v-for="msg in commentErrors.content" :key="msg">{{ msg }}</li>
+                   </ul>
+               </div>
+               <!-- /error message -->
+               <FormulateInput
+                   type="textarea"
+                   v-model="commentContent"
+                   label="Enter a comment in the box"
+                   validation="required|max:100,length"
+                   validation-name="comment"
+                   error-behavior="live"
+                   :help="$t('sentence.keep_it_under_100_characters_left', {length: 100 - commentContent.length})"
+               />
+               <FormulateInput type="submit" :disabled="loadingStatus">
+                   {{$t('sentence.submit_comment')}}
+                   <FAIcon
+                       v-if="loadingStatus"
+                       :icon="['fas', 'spinner']"
+                       class="fa-fw"
+                       pulse
+                   />
+               </FormulateInput>
+           </form>
+           <!-- /comment -->
+       </div>
+       <!-- /pane -->

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
+              commentContent: "",
+              commentErrors: null,
           };
       },
       computed: {
           isLogin() {
               return this.$store.getters["auth/check"];
           },
+          // loadingストアのstatus
+          loadingStatus() {
+             return this.$store.state.loading.status;
+          },
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
+          async like() {
+              const isLogin = this.$store.getters["auth/check"];
+  
+              if (!isLogin) {
+                  alert(this.$i18n.tc("sentence.login_before_like"));
+                  return false;
+              }
+  
+              // request api ライクを追加する処理
+              let response;
+              if (this.photo.is_liked) {
+                  response = await axios.delete(`photos/${this.photo.id}/like`);
+              } else {
+                  response = await axios.put(`photos/${this.photo.id}/like`);
+              }
+  
+              // if error
+              if (response.status !== OK) {
+                  // set status to error store
+                  this.$store.commit("error/setCode", response.status);
+                  return false;
+              }
+  
+              this.photo.is_liked = response.data.is_liked;
+              this.photo.total_like = response.data.total_like;
+          },
+          async addComment() {
+              /*
+               * request api
+               */
+              const response = await axios.post(
+                  `photos/${this.id}/comments`,
+                  {
+                      content: this.commentContent
+                  }
+              );
+              /*
+               * バリデーションエラー
+               */
+              if (response.status === UNPROCESSABLE_ENTITY) {
+                  // set validation error
+                  this.commentErrors = response.data.errors;
+                  return false;
+              }
+              // clear comment content
+              this.commentContent = "";
+              // エラーメッセージをクリア
+              this.commentErrors = null;
+              /*
+               * その他のエラー
+               */
+              if (response.status !== CREATED) {
+                  // set status to error store
+                  this.$store.commit("error/setCode", response.status);
+                  return false;
+              }
+              // 投稿し終わったあとに一覧に投稿したてのコメントを表示
+              this.photo.comments = [response.data, ...this.photo.comments];
+          },
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

## Vue側の翻訳ファイルに追加

`server\resources\js\lang\en.js`を編集

```javascript:server\resources\js\lang\en.js

    ...

    word: {
        ...
+       download: 'Download',
+       comments: 'Comments',
    }

    sentence: {
        ...
+       photo_by: 'Photo by {user}',
+       login_before_like: 'Please log in before you like.',
+       no_comments_yet: 'No comments yet.',
+       submit_comment: 'submit comment',
+       keep_it_under_100_characters_left: 'Keep it under 100 characters. {length} left.',
    }

    ...

```

`server\resources\js\lang\Ja.js`を編集

```javascript:server\resources\js\lang\Ja.js

    ...

    word: {
        ...
+       download: 'ダウンロード',
+       comments: 'コメント',
    }

    sentence: {
        ...
+       photo_by: '投稿者: {user}',
+       login_before_like: 'ライクする前にログインしてください。',
+       no_comments_yet: 'コメントはまだありません。',
+       submit_comment: 'コメント送信',
+       keep_it_under_100_characters_left: '100文字以下で記入 残り: {length}文字',
    }

    ...

```


次は今回作ったものにスタイルをつけていきます。

[Laravel mix vue No.14 - Web Application3 - スタイルの作成](https://www.aska-ltd.jp/jp/blog/91)

<script src="https://www.aska-ltd.jp/js/blog.js"></script>
<link href="https://www.aska-ltd.jp/css/blog.css" rel="stylesheet">