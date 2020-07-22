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
        'user',
        'url',
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
            // 1. 外部キーの名前を帰る場合は
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
