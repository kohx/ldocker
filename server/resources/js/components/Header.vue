<template>
    <header>
        <!-- リンクを設定 -->
        <RouterLink to="/">home</RouterLink>
        <RouterLink v-if="!isLogin" to="/login">login</RouterLink>
        <!-- ログインしている場合はusernameを表示 -->
        <span v-if="isLogin">{{username}}</span>
        <!-- クリックイベントにlogoutメソッドを登録 -->
        <span v-if="isLogin" @click="logout">logout</span>
    </header>
</template>

<script>
import Cookies from "js-cookie";
import Helper from "../helper";

export default {
    // 算出プロパティでストアのステートを参照
    computed: {
        // authストアのステートUserを参照
        isLogin() {
            return this.$store.getters["auth/check"];
        },
        // authストアのステートUserをusername
        username() {
            return this.$store.getters["auth/username"];
        }
    },
    // app.jsでVueLocalStorageの名前を変更したので「storage」で宣言
    storage: {
        language: {
            type: String,
            default: null
        }
    },
    methods: {
        // ログアウトメソッド
        async logout() {
            // authストアのlogoutアクションを呼び出す
            await this.$store.dispatch("auth/logout");
            // ログインに移動
            if (this.apiStatus) {
                this.$router.push("/login");
            }
        },
        // 言語切替メソッド
        changeLang() {
            // ローカルストレージに「language」をセット
            this.$storage.set("language", this.selectedLang);
            // Apiリクエスト 言語を設定
            axios.get(`/api/set-lang/${this.selectedLang}`);
        }
    },
    created() {
        // ローカルストレージから「language」を取得
        this.selectedLang = this.$storage.get("language");

        // サーバ側をクライアント側に合わせる

        // storageLangがない場合
        if (!this.selectedLang) {
            // ブラウザーの言語を取得
            const defaultLang = Helper.getLanguage();
            // ローカルストレージに「language」をセット
            this.$storage.set("language", defaultLang);
            // Apiリクエスト 言語を設定
            axios.get(`/api/set-lang/${defaultLang}`);
        }
        // ある場合はサーバ側をクライアント側に合わせる
        else {
            axios.get(`/api/set-lang/${this.selectedLang}`);
        }
    }
};
</script>
