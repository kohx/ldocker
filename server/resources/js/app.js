import "./bootstrap";
import Vue from "vue";
// ルートコンポーネントをインポート
import App from "./App.vue";
// ルーターをインポート
import router from "./router";
// ストアをインポート
import store from "./store";
// ヘルパーをインポート
import Helper from "./helper";

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

/**
 * vue-i18n
 * https://kazupon.github.io/vue-i18n/
 */
// モジュールのインポート
import VueI18n from "vue-i18n";
// 言語コンテンツのインポート
import {
    messages,
    dateTimeFormats,
    numberFormats
} from "./lang/index";
// 多言語化の宣言
Vue.use(VueI18n);
// ローカルストレージから「language」を取得してセット、ない場合はブラウザーの言語をセット
const locale = Vue.storage.get("language", Helper.getLanguage());
// VueI18nコンストラクタのオプションを指定
const i18n = new VueI18n({
    // 言語設定
    locale: locale,
    // 選択中の言語に対応する文字列が存在しない場合はこの言語の文字列を使用する
    fallbackLocale: "en",
    // インポートした言語コンテンツ
    messages,
    dateTimeFormats,
    numberFormats
});

/**
 * fontawesome
 * https://github.com/FortAwesome/vue-fontawesome
 * http://l-lin.github.io/font-awesome-animation/
 */
// コアのインポート
import {
    library
} from "@fortawesome/fontawesome-svg-core";

// 無料で使えるフォントをインポート
import {
    fab
} from "@fortawesome/free-brands-svg-icons";
import {
    far
} from "@fortawesome/free-regular-svg-icons";
import {
    fas
} from "@fortawesome/free-solid-svg-icons";
// コンポネントをインポート
import {
    FontAwesomeIcon,
    FontAwesomeLayers,
    FontAwesomeLayersText
} from "@fortawesome/vue-fontawesome";
// ライブラリに追加
library.add(fas, far, fab);
// コンポーネントを名前を指定して追加
// 名前は自由にきめてOK
Vue.component("FAIcon", FontAwesomeIcon);
Vue.component('FALayers', FontAwesomeLayers);
Vue.component('FAText', FontAwesomeLayersText);

/**
 * VueFormulate
 * https://vueformulate.com/guide/
 * https://vueformulate.com/guide/internationalization/#registering-a-locale
 * https://vueformulate.com/guide/custom-inputs/#custom-types
 */
// コアをインポート
import VueFormulate from "@braid/vue-formulate";
// 言語をインポート
import {
    en,
    ja
} from "@braid/vue-formulate-i18n";
// 宣言
Vue.use(VueFormulate, {
    // 使用するプラグイン
    plugins: [en, ja],
    // グローバルに使う独自ルール
    rules: {
        maxPhoto: (context, limit) => {
            const value = context.value ? context.value.files.length : 0;
            return value <= limit
        }
    },
    locales: {
        en: {
            maxPhoto(args) {
                return `Photo is ${args[0]} or less`;
            }
        },
        ja: {
            maxPhoto(args) {
                return `写真は${args[0]}ファイルまでです。`;
            }
        }
    }
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
        // I18nを登録
        i18n,
        // 使用するコンポーネントにルートコンポーネントの登録
        components: {
            App
        },
        // 描画するテンプレート
        template: "<App />"
    });

};

// createAppを実行
createApp();
