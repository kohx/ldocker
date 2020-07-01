<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Lang;
use Socialite;
use App\Traits\Vueable;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers, Vueable;

    /**
     * ログイン制限が設定
     *
     * laravel標準のLoginControllerを使う場合、ログイン失敗の数によってログイン制限が設定されている
     * 場所は上の「AuthenticatesUsersトレイト」でuseされている「ThrottlesLoginsトレイト」にある「maxAttemptsメソッド」と「decayMinutesメソッド」
     * $this->maxAttemptsがない場合は5がデフォルト
     * $this->decayMinutesがない場合1がデフォルト
     * よって、試行回数：5回 / ロック時間：1分
     */
    // ログイン試行回数（回）
    protected $maxAttempts = 5;
    // ログインロックタイム（分）
    protected $decayMinutes = 1;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');

        // ログイン制限 試行回数（回）
        $this->maxAttempts = config('auth.throttles_logins.maxAttempts', $this->maxAttempts);

        // ログイン制限 ロックタイム（分）
        $this->decayMinutes = config('auth.throttles_logins.decayMinutes', $this->decayMinutes);
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function authenticated(Request $request, $user)
    {
        return $user;
    }

    /**
     * The user has logged out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function loggedOut(Request $request)
    {
        // セッションの再生成
        $request->session()->regenerate();

        return response()->json();
    }

    /**
     * 認証ページヘユーザーをリダイレクト
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)
            // オプションパラメータを含めるには、withメソッドを呼び出し、連想配列を渡す
            // ->with(['hd' => 'example.com'])
            // scopesメソッドを使用し、リクエストへ「スコープ」を追加することもでる
            // ->scopes(['read:user', 'public_repo'])
            ->redirect();
    }

    /**
     * プロバイダからユーザー情報を取得
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback($provider)
    {
        try {
            // プロバイダからのレスのの中からユーザ情報を取得
            $providerUser = Socialite::with($provider)->user();
            // メールを取得
            $email = $providerUser->getEmail() ?? null;
            // プロバイダID
            $providerId = $providerUser->getId();
            // メールがあるとき
            if ($email) {
                // ユーザを作成または更新
                $user = User::firstOrCreate([
                    'email' => $email,
                ], [
                    'provider_id' => $providerId,
                    'provider_name' => $provider,
                    'email' => $email,
                    'name' => $providerUser->getName(),
                    'nickname' => $providerUser->getNickname() ?? null,
                    'avatar' => $providerUser->getAvatar() ?? '',
                ]);
            }
            // プロバイダIDがあるとき
            elseif ($providerId) {
                // ユーザを作成または更新
                $user = User::firstOrCreate([
                    'provider_id' => $providerId,
                    'provider_name' => $provider,
                ], [
                    'provider_id' => $providerUser->getId(),
                    'provider_name' => $provider,
                    'email' => $email,
                    'name' => $providerUser->getName(),
                    'nickname' => $providerUser->getNickname() ?? null,
                    'avatar' => $providerUser->getAvatar() ?? '',
                ]);
            } else {
                throw new \Exception();
            }
            // login with remember
            Auth::login($user, true);
            // メッセージをつけてリダイレクト
            $message = Lang::get('socialite login success.');
            return $this->redirectVue('', 'MESSAGE', $message);
        } catch (\Exception $e) {
            // メッセージをつけてリダイレクト
            $message = $message = Lang::get('authentication failed.');
            return $this->redirectVue('login', 'MESSAGE', $message);
        }
    }
}
