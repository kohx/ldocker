import "./bootstrap";
import Vue from "vue";
// ルートコンポーネントをインポート
import App from "./App.vue";
// ルーターをインポート
import router from "./router";
// ストアをインポート
import store from "./store";

/**
 * vue-localstorage
 * https://www.npmjs.com/package/vue-localstorage
 */

// vue-localstorageをインポート
import VueLocalStorage from "vue-localstorage";
// VueLocalStorage宣言
// 「$this.storage.get()」のように使えるように名前を変更
// この名前でプロパティを宣言する
Vue.use(VueLocalStorage, {
    name: 'storage'
});

// 非同期通信でAUTHストアのcurrentUserアクションを実行するので
// asyncメソッドにして、awaitで通信をまつ
const createApp = async () => {

    // AUTHストアのcurrentUserアクションでユーザの認証状態をチェック
    await store.dispatch("auth/currentUser");

    new Vue({
        // マウントする要素に「index.blade.php」の「<div id="app"></div>」のidを登録
        el: "#app",
        // ルーターの使用を宣言
        router,
        // ストアを登録
        store,
        // 使用するコンポーネントにルートコンポーネントの登録
        components: { App },
        // 描画するテンプレート
        template: "<App />"
    });

};

// createAppを実行
createApp();
