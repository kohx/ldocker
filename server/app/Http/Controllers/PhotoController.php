<?php

namespace App\Http\Controllers;

// models
use App\Models\Photo;
use App\Models\Comment;

// requests
use App\Http\Requests\StorePhoto;
use App\Http\Requests\StoreComment;
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
     * photo index
     */
    public function index()
    {
        // get の代わりに paginate を使うことで、
        // JSON レスポンスでも示した total（総ページ数）や current_page（現在のページ）といった情報が自動的に追加される
        $photos = Photo::with(['owner', 'likes'])
            ->orderBy(Photo::CREATED_AT, 'desc')
            ->paginate();

        return $photos;
    }

    /**
     * 写真詳細
     * photo show
     *
     * @param string $id
     * @return Photo
     */
    public function show(string $id)
    {
        // get photo
        // 'comments.author'でcommentsと、そのbelongsToに設定されている'author'も取得
        // findOrFailでなければ404
        $photo = Photo::with(['owner', 'comments.author', 'likes'])->findOrFail($id);

        return $photo;
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
        // extension()メソッドで投稿写真の拡張子を取得する
        $extension = $request->photo->extension();

        // モデルインスタンス作成
        $photo = new Photo();

        // インスタンス生成時に割り振られたランダムなID値と
        // 本来の拡張子を組み合わせてファイル名とする
        $photo->filename = "{$photo->id}.{$extension}";

        // S3にファイルを保存する
        // putFileAsの引数は( ディレクトリ, ファイルデータ, ファイルネーム, 公開 )
        // 第三引数の'public'はファイルを公開状態で保存するため
        Storage::cloud()->putFileAs('', $request->photo, $photo->filename, 'public');

        // データベースエラー時にファイル削除を行うためトランザクションを利用する
        // Transaction Begin
        DB::beginTransaction();

        try {
throw new \Exception('test!');

            // ユーザのフォトにインサート
            Auth::user()->photos()->save($photo);

            // Transaction commit
            DB::commit();
        } catch (\Exception $exception) {

            // Transaction Rollback
            DB::rollBack();

            // DBとの不整合を避けるためアップロードしたファイルを削除
            Storage::cloud()->delete($photo->filename);

            // エラーレスポンス
            return response()->json(['errors' => [__('upload failed.')]], 500);
        }

        // リソースの新規作成なので
        // レスポンスコードは201(CREATED)を返却する
        return response($photo, 201);
    }
}
