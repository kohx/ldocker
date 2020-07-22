<template>
    <div class="wrapper">
        <!-- Headerコンポーネント -->
        <Header />

        <main>
            <div class="container">
                <!-- message -->
                <Message />

                <!-- ルートビューコンポーネント -->
                <RouterView />
            </div>
        </main>
    </div>
</template>

<script>
// Headerコンポーネントをインポート
import Header from "./components/Header.vue";
import Message from "./components/Message.vue";
import { NOT_FOUND, UNAUTHORIZED, INTERNAL_SERVER_ERROR } from "./const";

// js-cookieをインポート
import Cookies from "js-cookie";

export default {
    // 使用するコンポーネントを宣言
    components: {
        Header,
        Message
    },
    computed: {
        // ERRORストアのcodeステートを取得
        errorCode() {
            return this.$store.state.error.code;
        }
    },
    watch: {
        // errorCodeを監視
        errorCode: {
            async handler(val) {
                // 500
                if (val === INTERNAL_SERVER_ERROR) {
                    // 500に移動
                    this.$router.push({ name: "system-error" });
                }
                // 419
                else if (val === UNAUTHORIZED) {
                    // トークンをリフレッシュ
                    await axios.get("/api/refresh-token");
                    // ストアのuserをクリア
                    this.$store.commit("auth/setUser", null);
                    // ログイン画面へ移動
                    this.$router.push({ name: "login" });
                }
                // 404
                else if (val === NOT_FOUND) {
                    // 404へ移動
                    this.$router.push({ name: "not-found" });
                }
            },
            // createdでも呼び出すときはこれだけでOK
            immediate: true
        },
        // 同じrouteの異なるパラメータで画面遷移しても、vue-routerは画面を再描画しないのでwatchで監視
        $route() {
            this.$store.commit("error/setCode", null);
        }
    },
    created() {
        // cookiesにMESSAGEがある場合
        const message = Cookies.get("MESSAGE");
        if (message) {
            // cookieをクリア
            Cookies.remove("MESSAGE");
            // MESSAGEストアでメッセージを表示
            this.$store.commit("message/setContent", {
                content: message,
                timeout: 6000
            });
        }
    }
};
</script>
