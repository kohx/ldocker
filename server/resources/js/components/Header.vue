<template>
    <header>
        <!-- リンクを設定 -->
        <RouterLink to="/">home</RouterLink>
        <RouterLink v-if="!isLogin" to="/login">login</RouterLink>

        <!-- クリックイベントにlogoutメソッドを登録 -->
        <span v-if="isLogin" @click="logout">logout</span>
    </header>
</template>

<script>
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
    methods: {
        // ログアウトメソッド
        async logout() {
            // authストアのlogoutアクションを呼び出す
            await this.$store.dispatch("auth/logout");
            // ログインに移動
            if (this.apiStatus) {
                this.$router.push("/login");
            }
        }
    }
};
</script>
