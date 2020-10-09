<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    /**
     * JSONに含める属性
     *
     * @var array
     */
    protected $visible = [
        'id','user', 'content', 'updated_at'
    ];

    /**
     * リレーションシップ - usersテーブル
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        // 「リレーションメソッド名 ＋ _id」をデフォルトの外部キーにしている
        return $this->belongsTo('App\User');
        // 1. 外部キーの名前を帰る場合は
        // $this->belongsTo('App\User', 'foreign_key')
        // 2. リレーション先で「id」じゃないキーと紐付ける場合
        // $this->belongsTo('App\User', 'foreign_id', 'relation_id')
        // 3. デフォルトモデルを設定する場合は以下を追加
        // ->withDefault(function ($user, $post) {
        //     $user->name = 'Guest Author';
        // })
    }
}
