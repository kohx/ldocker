<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResetPassword extends Model
{
    // テーブル名を指定
    protected $table = 'password_resets';

    // プライマリキーを「email」に変更
    // デフォルトは「id」
    protected $primaryKey = 'email';
    // プライマリキーのタイプを指定
    protected $keyType = 'string';
    // タイプがストリングの場合はインクリメントを「false」にしないといけない
    public $incrementing = false;

    // モデルが以下のフィールド以外を持たないようにする
    protected $fillable = [
        'email',
        'token',
    ];

    // タイムスタンプは「created_at」のフィールドだけにしたいので、「false」を指定
    public $timestamps = false;
    // 自前で用意する
    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }
}
