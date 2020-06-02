import "./bootstrap";
import Vue from "vue";
// ルートコンポーネントをインポート
import App from "./App.vue";
// ルーターをインポート
import router from "./router";

new Vue({
    // マウントする要素に「index.blade.php」の「<div id="app"></div>」のidを登録
    el: "#app",
    // ルーターの使用を宣言
    router,
    // 使用するコンポーネントにルートコンポーネントの登録
    components: { App },
    // 描画するテンプレート
    template: "<App />"
});
