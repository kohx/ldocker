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
     * 写真一覧
     */
    public function index()
    {
        // get の代わりに paginate を使うことで、
        // JSON レスポンスでも示した total（総ページ数）や current_page（現在のページ）といった情報が自動的に追加される
        $photos = Photo::with(['user'])
            ->orderBy(Photo::CREATED_AT, 'desc')
            ->paginate();

        return $photos;
    }

    /**
     * 写真詳細
     * @param string $id
     * @return Photo
     */
    public function show(string $id)
    {
        // get photo
        // 'comments.author'でcommentsと、そのbelongsToに設定されている'author'も取得
        $photo = Photo::with(['user'])->findOrFail($id);
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
