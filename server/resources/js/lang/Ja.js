const contents = {
    messages: {
        // 単語
        word: {
            hello: 'こんにちは!',
            home: 'ホーム',
            login: 'ログイン',
            logout: 'ログアウト',
            english: '英語',
            japanese: '日本語',
            register: '登録',
            forgot_password: 'パスワードをわすれましたか?',
            email: 'メールアドレス',
            password: 'パスワード',
            socialate: 'ソーシャルログイン',
            name: '名前',
            password_confirmation: 'パスワード確認',
            send: '送信',
            password_reset: 'パスワード リセット',
            reset: 'リセット',
        },
        // メッセージ
        sentence: {
            '{msg} world!': '{msg} 世界！',
            sent_verification_email: '確認メールを送信しました。',
            sent_password_reset_email: 'パスワード再設定メールを送信しました。',
        }
    },
    /*
        日付フォーマット

        以下の定義形式で日時をローカライズ
        weekday         "narrow", "short", "long"
        era             "narrow", "short", "long"
        year            "2-digit", "numeric"
        month           "2-digit", "numeric", "narrow", "short", "long"
        day             "2-digit", "numeric"
        hour            "2-digit", "numeric"
        minute          "2-digit", "numeric"
        second          "2-digit", "numeric"
        timeZoneName    "short", "long"
     */
    dateTimeFormats: {
        full: {
            year: "numeric",
            month: "short",
            day: "numeric",
            weekday: "short",
            hour: "numeric",
            minute: "numeric",
            hour12: true
        },
        day: {
            year: "numeric",
            month: "short",
            day: "numeric"
        },
        time: {
            hour: "numeric",
            minute: "numeric",
            hour12: true
        },
        week: {
            weekday: "short"
        }
    },
    // ナンバーフォーマット
    numberFormats: {
        currency: {
            style: 'currency',
            currency: 'JPY',
            currencyDisplay: 'symbol'
        }
    }
};

export { contents };
