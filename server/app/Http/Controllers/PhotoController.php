<?php

namespace App\Http\Controllers;

// models
use App\Models\Photo;
use App\Models\Comment;

// requests
use Illuminate\Http\Request;
use App\Http\Requests\StorePhoto;
use App\Http\Requests\StoreComment;
use Error;
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
     * 写真一覧
     * get /api/photos photo.list
     */
    public function index()
    {
        // user情報も一緒に取得
        $photos = Photo::with('user')
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
        // 'comments.user'でcommentsと、そのbelongsToに設定されている'user'も取得
        $photo = Photo::with(['user', 'comments.user', 'likes'])
            // 「findOrFail」でIDが存在しない場合は404が自動的に返される
            ->findOrFail($id);

        return $photo;
    }

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
        } catch (\Exception $exception) {

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

    /**
     * 写真ダウンロード
     * get /photos/{photo}/download download
     *
     * 引数にモデルを指定したらモデルバインディングでモデルを取得できる
     * https://laravel.com/docs/7.x/routing#route-model-binding
     *
     * @param Photo $photo
     * @return \Illuminate\Http\Response
     */
    public function download(Photo $photo)
    {
        // 写真なければ404
        if (!Storage::cloud()->exists($photo->path)) {
            abort(404);
        }

        // 拡張子を取得
        $extension =  pathinfo($photo->path, PATHINFO_EXTENSION);

        // コンテンツタイプにapplication/octet-streamを指定すればダウンロードできます。
        // Content-Dispositionヘッダーにattachmentを指定すれば、コンテンツタイプが"application/octet-stream"でなくても、ダウンロードできます。
        // filenameに指定した値が、ダウンロード時のデフォルトのファイル名になります。
        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename={$photo->name}.{$extension}",
        ];

        // ファイルを取得
        $file = Storage::cloud()->get($photo->path);

        return response($file, 200, $headers);
    }

    /**
     * ライク
     * @return array
     */
    public function like(Request $request)
    {
        // パラメータを取得
        $photoId = $request->input('photo_id');
        $liked = $request->input('liked');

        // ない場合、または方がおかしい場合には500エラー
        if (!$photoId || !is_bool($liked)) {

            abort(500);
        }

        // likes付きで写真を取得
        $photo = Photo::with('likes')->findOrFail($photoId);

        // ライクが送られてきた場合
        if ($liked) {

            // 多対多リレーションなのでattachヘルパメソッドをつかって追加
            $photo->likes()->attach(Auth::user()->id);
        }
        // ライクの削除が送られてきた場合
        else {

            // 多対多リレーションなのでattachヘルパメソッドをつかって削除
            $photo->likes()->detach(Auth::user()->id);
        }

        // 現在のis_likedとtotal_likeを返す
        return ["is_liked" => $liked, "total_like" => $photo->likes()->count()];
    }

    /**
     * コメント投稿
     * post /api/photos/{photo}/comments photo.store_comment
     *
     * モデルバインディングで取得
     * @param Photo $photo
     * @param StoreComment $request
     * @return \Illuminate\Http\Response
     */
    public function storeComment(Photo $photo, StoreComment $request)
    {
        // コメントモデルを作成
        $comment = new Comment();

        // 値をセット
        $comment->content = $request->input('content');
        $comment->user_id = Auth::user()->id;

        // データベースに反映
        $photo->comments()->save($comment);

        // userリレーションをロードするためにコメントを取得しなおす
        $new_comment = Comment::with('user')->find($comment->id);

        return response($new_comment, 201);
    }

    /**
     * edit comment
     * put /api/photos/comments/{comment} photo.edit_comment
     *
     * 引数にモデルを指定したらモデルバインディングでモデルを取得できる
     */
    public function editComment(Comment $comment, StoreComment $request)
    {
        // 値をセット
        $comment->content = $request->input('content');

        // データベースに反映
        $comment->save();

        // userリレーションをロードするためにコメントを取得しなおす
        $new_comment = Comment::with('user')->find($comment->id);

        return response($new_comment, 200);
    }

    /**
     * delete comment
     */
}
