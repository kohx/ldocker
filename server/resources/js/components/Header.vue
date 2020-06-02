<template>
  <header>
    <!-- リンクを設定 -->
    <RouterLink to="/">home</RouterLink>
    <RouterLink to="/login">login</RouterLink>
    <!-- クリックイベントにlogoutメソッドを登録 -->
    <span @click="logout">logout</span>

  </header>
</template>

<script>
export default {
  methods: {
    // ログアウトメソッド
    async logout() {
      // ログアウトのAPIリクエスト（次のステップで実装）
      alert("logout");
      // ログインに移動
      this.$router.push("/login");
    }

  }
};
</script>
