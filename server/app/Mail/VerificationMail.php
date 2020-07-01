<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($token)
    {
        // 引数でトークンを受け取る
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // 件名
        $subject = __('verification mail');

        // コールバックURLをルート名で取得
        //TODO: これだとホットリロードでホストがおかしくなる
        // $url = route('verification', ['token' => $this->token]);

        //TODO: とりあえずこれで対応
        // .envの「APP_URL」に設定したurlを取得
        $baseUrl = config('app.url');
        $token = $this->token;
        $url = "{$baseUrl}/verification/{$token}";

        // 送信元のアドレス
        // .envの「MAIL_FROM_ADDRESS」に設定したアドレスを取得
        $from = config('mail.from.address');

        // メール送信
        return $this
            ->from($from)
            ->subject($subject)
            // 送信メールのビュー
            ->view('mails.verification_mail')
            // ビューで使う変数を渡す
            ->with('url', $url);
    }
}
