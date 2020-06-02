<template>
  <div>

    <!-- Headerコンポーネント -->
    <Header />

    <main>
      <div class="container">
        <!-- ルートビューコンポーネント -->
        <RouterView />
      </div>
    </main>

  </div>
</template>

<script>
// Headerコンポーネントをインポート
import Header from "./components/Header.vue";

export default {
  // 使用するコンポーネントを宣言
  components: {
    Header
  }
};
</script>
