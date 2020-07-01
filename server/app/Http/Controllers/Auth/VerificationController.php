<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use App\Models\RegisterUser;

class VerificationController extends Controller
{
    // vueでアクセスするホームへのルート
    protected $vueRouteHome = '';
    // vueでアクセスするログインへのルート
    protected $vueRouteLogin = 'login';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // guestミドルウェアはRedirectIfAuthenticatedクラスを指定しているので
        // 認証済み（ログイン済み）の状態でログインページにアクセスすると、ログイン後のトップページにリダイレクトする
        $this->middleware('guest');
    }

    /**
     * Complete registration
     * 登録を完了させる
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register($token)
    {
        // 仮登録のデータをトークンで取得
        $registerUser = $this->getRegisterUser($token);

        // 取得できなかった場合
        if (!$registerUser) {

            // 失敗メッセージを作成
            $message = __('not provisionally registered.');

            // メッセージをつけてリダイレクト
            return $this->redirectWithMessage($this->vueRouteLogin, $message);
        }

        // 仮登録のデータでユーザを作成
        event(new Registered($user = $this->createUser($registerUser->toArray())));

        // 作成したユーザをログインさせる
        Auth::login($user, true);

        // 成功メッセージ
        $message = __('completion of registration.');

        // メッセージをつけてリダイレクト
        return $this->redirectWithMessage($this->vueRouteHome, $message);
    }

    /**
     * get register user and clean table
     * トークンで仮登録ユーザデータを取得して仮登録データをテーブルから削除する
     *
     * @param string $activationCode
     * @return RegisterUser|null
     */
    private function getRegisterUser($token)
    {
        // トークンで仮登録ユーザデータを取得
        $registerUser = RegisterUser::where('token', $token)->first();

        // 取得できた場合は仮登録データを削除
        if ($registerUser) {

            RegisterUser::destroy($registerUser->email);
        }

        // モデルを返す
        return $registerUser;
    }

    /**
     * Create a new user instance after a valid registration.
     * ユーザインスタンスを作成
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function createUser(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'email_verified_at' => now(),
            'password' => $data['password'],
        ]);
    }

    /**
     * redirect with message
     * メッセージをクッキーに付けてリダイレクト
     *
     * @param  string  $vueRoute
     * @param  string  $message
     * @return Redirect
     */
    protected function redirectWithMessage($vueRoute, $message)
    {
        // vueでアクセスするルートを作る
        $route = url($vueRoute);

        return redirect($route)
            // PHPネイティブのsetcookieメソッドに指定する引数同じ
            // ->cookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly)
            ->cookie('MESSAGE', $message, 0, '', '', false, false);
    }
}
