/*
 * ステート（データの入れ物）
 */
const state = {
    status: false
};

/*
 * ミューテーション（同期処理）
 */
const mutations = {
    setStatus(state, status) {
        state.status = status;
    }
};

/*
 * エクスポート
 */
export default {
    namespaced: true,
    state,
    mutations
};
