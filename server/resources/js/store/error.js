/*
 * ステート（データの入れ物）
 */
const state = {
    // エラーコードの保持
    code: null
};

/*
 * ミューテーション（同期処理）
 */
const mutations = {
    // エラーコードの更新
    setCode(state, code) {
        state.code = code;
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
