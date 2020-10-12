// クッキーを簡単に扱えるモジュールをインポート
import Cookies from "js-cookie";
import store from "./store";

/*
 * lodash
 * あると便利のなのでそのままおいておく
 */
window._ = require("lodash");

/*
 * axios
 * Ajax通信にはこれを使う
 */
window.axios = require("axios");

// Ajaxリクエストであることを示すヘッダーを付与する
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// ベースURLを.envから取得
// MIX_URL="${APP_URL}"を.envに追加
// このときのプレフィックスは「MIX_」でないと取得できない
const baseUrl = process.env.MIX_URL;

// ベースURLの設定
window.axios.defaults.baseURL = `${baseUrl}/api/`;

// requestの設定
window.axios.interceptors.request.use(config => {

    // ローディングストアのステータスをTRUE
    store.commit("loading/setStatus", true);

    // クッキーからトークンを取り出す
    const xsrfToken = Cookies.get("XSRF-TOKEN");
    // ヘッダーに添付する
    config.headers["X-XSRF-TOKEN"] = xsrfToken;
    return config;
});

// responseの設定
// API通信の成功、失敗でresponseの形が変わるので、どちらとも response にレスポンスオブジェクトを代入
window.axios.interceptors.response.use(
    // 成功時の処理
    response => {
        // ローディングストアのステータスをFALSE
        store.commit("loading/setStatus", false);
        return response;
    },
    // 失敗時の処理
    error => {
        // ローディングストアのステータスをFALSE
        store.commit("loading/setStatus", false);
        return error.response || error;
    }
);

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// window.Pusher = require('pusher-js');

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: process.env.MIX_PUSHER_APP_KEY,
//     cluster: process.env.MIX_PUSHER_APP_CLUSTER,
//     forceTLS: true
// });
