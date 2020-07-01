const contents = {
    messages: {
        // 単語
        word: {
            'hell o$o_o/o!o#o@o01&^*': 'hello@',
            home: 'Home',
            login: 'Login',
            logout: 'logout',
            english: 'English',
            japanese: 'Japanese',
            register: 'Register',
            forgot_password: 'Forgot Password ?',
            email: 'Email',
            password: 'Password',
            socialate: 'Socialate',
            name: 'Name',
            password_confirmation: 'Password Confirmation',
            send: 'Send',
            password_reset: 'Password Reset',
            reset: 'Reset',
        },
        // メッセージ
        sentence: {
            '{msg} world!': '{msg} world！',
            sent_verification_email: 'Sent verification email.',
            sent_password_reset_email: 'Sent password reset email.',
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
            minute: "numeric"
        },
        day: {
            year: "numeric",
            month: "short",
            day: "numeric"
        },
        time: {
            hour: "numeric",
            minute: "numeric"
        },
        week: {
            weekday: "long"
        }
    },
    // ナンバーフォーマット
    numberFormats: {
        currency: {
            style: 'currency',
            currency: 'USD'
        }
    }
};

export {
    contents
};
