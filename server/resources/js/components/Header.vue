<template>
    <header class="header">
        <RouterLink to="/">
            <FAIcon :icon="['fas', 'home']" size="lg" />
            {{ $t("word.home") }}
        </RouterLink>

        <RouterLink v-if="!isLogin" to="/login">
            <FAIcon :icon="['fas', 'sign-in-alt']" size="lg" />
            {{ $t("word.login") }}
        </RouterLink>

        <span v-if="isLogin">
            <FAIcon :icon="['fas', 'child']" size="lg" />
            {{ username }}
        </span>

        <span v-if="isLogin" @click="logout" class="button">
            <FAIcon :icon="['fas', 'sign-out-alt']" size="lg" />
            {{ $t("word.logout") }}
        </span>

        <FormulateInput
            @input="changeLang"
            v-model="selectedLang"
            :options="langList"
            type="select"
            class="header-lang"
        />
    </header>
</template>

<script>
import Cookies from "js-cookie";
import Helper from "../helper";

export default {
    data() {
        return {
            // 言語選択オプション
            langList: [
                { value: "en", label: this.$i18n.tc("word.english") },
                { value: "ja", label: this.$i18n.tc("word.japanese") },
            ],
            // 選択された言語
            selectedLang: "en",
        };
    },
    // 算出プロパティでストアのステートを参照
    computed: {
        // authストアのステートUserを参照
        isLogin() {
            return this.$store.getters["auth/check"];
        },
        // authストアのステートUserをusername
        username() {
            return this.$store.getters["auth/username"];
        },
    },
    // app.jsでVueLocalStorageの名前を変更したので「storage」で宣言
    storage: {
        language: {
            type: String,
            default: null,
        },
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
            axios.get(`set-lang/${this.selectedLang}`);

            // Vue i18n の言語を設定
            this.$i18n.locale = this.selectedLang;

            // セレクトオプションを翻訳
            this.langList = [
                { value: "en", label: this.$i18n.tc("word.english") },
                { value: "ja", label: this.$i18n.tc("word.japanese") },
            ];
        },
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
            axios.get(`set-lang/${defaultLang}`);
        }
        // ある場合はサーバ側をクライアント側に合わせる
        else {
            axios.get(`set-lang/${this.selectedLang}`);
        }
    },
};
</script>
