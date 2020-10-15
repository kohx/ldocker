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
