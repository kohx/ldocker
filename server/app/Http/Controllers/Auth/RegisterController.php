<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// 作成したものを使う
use App\Models\RegisterUser;
use App\Mail\VerificationMail;

class RegisterController extends Controller
{
    /**
     * Send Register Link Email
     * 送られてきた内容をテーブルに保存して認証メールを送信
     *
     * @param Request $request
     * @return RegisterUser
     */
    public function sendMail(Request $request)
    {
        // validation
        // 送られてきた内容のバリデーション
        $this->validator($request->all())->validate();

        // create token
        // トークンを作成
        $token = self::createToken();

        // delete old data
        // 同じメールアドレスが残っていればテーブルから削除
        RegisterUser::destroy($request->email);

        // insert
        // 送られてきた内容をテーブルに保存
        $passwordReset = new RegisterUser($request->all());
        $passwordReset->token = $token;
        // パスワードはハッシュ
        $passwordReset->password = Hash::make($request->password);
        $passwordReset->save();

        // send email
        // メールクラスでメールを送信
        self::sendVerificationMail($passwordReset->email, $token);

        // モデルを返す
        return $passwordReset;
    }

    /**
     * validator
     * バリデーション
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * create activation token
     * 認証メールのトークンを作成する
     * @return string
     */
    private function createToken()
    {
        return hash_hmac('sha256', Str::random(40), config('app.key'));
    }

    /**
     * send verification mail
     * メールクラスでメールを送信
     *
     * @param string $email
     * @param string $token
     * @return void
     */
    private function sendVerificationMail($email, $token)
    {
        Mail::to($email)
            ->send(new VerificationMail($token));
    }
}
