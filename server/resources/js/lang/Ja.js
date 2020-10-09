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
            Socialite: 'ソーシャルログイン',
            name: '名前',
            password_confirmation: 'パスワード確認',
            send: '送信',
            password_reset: 'パスワード リセット',
            reset: 'リセット',
            photo: '写真',
            upload: 'アップロード',
            upload_photo: '写真をアップロード',
            photo_name: '写真の名前',
            photo_description: '写真の説明',
            photo_files: '写真ファイル',
            comments: 'コメント',
            comment_content: 'コメント内容',
            submit_comment: 'コメントを送信',
            change: '変更',
        },
        // メッセージ
        sentence: {
            '{msg} world!': '{msg} 世界！',
            sent_verification_email: '確認メールを送信しました。',
            sent_password_reset_email: 'パスワード再設定メールを送信しました。',
            please_login_to_use_like_feature: 'いいね機能を使うにはログインしてください。',
            posted_by: '{username}によって投稿',
            keep_it_under_characters: '100文字以下で入力してください。 残り{length}文字',
            no_comments_yet: 'コメントはまだありません。',
            send_comment_failed: 'コメントの送信に失敗しました。',
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

export {
    contents
};
