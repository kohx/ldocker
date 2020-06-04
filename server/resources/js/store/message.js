/*
 * ステート（データの入れ物）
 */
const state = {
    // メッセージの保持
    content: ""
};

/*
 * ミューテーション（同期処理）
 */
const mutations = {
    // メッセージの更新
    setContent(state, { content, timeout }) {
        state.content = content;

        // タイムアウトでステートをクリア
        if (typeof timeout === "undefined") {
            timeout = 3000;
        }

        setTimeout(() => (state.content = ""), timeout);
    }
};

/*
 * エクスポート
 */
export default {
    // 名前をストア別に区別できるようにネームスペースを使う
    namespaced: true,
    state,
    mutations
};
