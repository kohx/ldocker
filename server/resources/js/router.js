import Vue from "vue";
// ルーターをインポート
import VueRouter from "vue-router";
// ストアをインポート
import store from "./store";

// ページをインポート
import Home from "./pages/Home.vue";
import PhotoUpload from "./pages/PhotoUpload.vue";
import PhotoDetail from "./pages/PhotoDetail.vue";
import Login from "./pages/Login.vue";
import Reset from "./pages/Reset.vue";
import SystemError from "./pages/errors/SystemError.vue";
import NotFound from "./pages/errors/NotFound.vue";

// VueRouterをVueで使う
// これによって<RouterView />コンポーネントなどを使うことができる
Vue.use(VueRouter);

// パスとページの設定
const routes = [
    // home
    {
        // urlのパス
        path: "/",
        // ルートネーム
        name: 'home',
        // インポートしたページ
        component: Home,
        // https://router.vuejs.org/ja/guide/essentials/passing-props.html
        props: route => {
            const page = route.query.page;
            // 整数と解釈されない値は「1」を返却
            return {
                page: /^[1-9][0-9]*$/.test(page) ? page * 1 : 1
            };
        }
    },
    {
        // urlのパス
        path: "/photos/:id",
        // ルートネーム
        name: 'photo',
        component: PhotoDetail,
        // props: true は :id を props として受け取ることを意味
        // props を true に設定しているので、この :id の値が <PhotoDetail> コンポーネントに props として渡される
        // リンクは「{name: 'photo', params: { id: item.id }}」このように渡す
        props: true
    },
    // photo-upload
    {
        // urlのパス
        path: "/photo-upload",
        // ルートネーム
        name: 'photo-upload',
        // インポートしたページ
        component: PhotoUpload,
        // ページコンポーネントが切り替わる直前に呼び出される関数
        // to はアクセスされようとしているルートのルートオブジェクト
        // from はアクセス元のルート
        // next はページの移動先
        beforeEnter(to, from, next) {
            if (store.getters["auth/check"]) {
                next();
            } else {
                next({
                    name: 'login'
                });
            }
        }
    },
    // login
    {
        // urlのパス
        path: "/login",
        // ルートネーム
        name: 'login',
        // インポートしたページ
        component: Login,
        // ページコンポーネントが切り替わる直前に呼び出される関数
        // to はアクセスされようとしているルートのルートオブジェクト
        // from はアクセス元のルート
        // next はページの移動先
        beforeEnter(to, from, next) {
            // AUTHストアでログインしているかチェック
            if (store.getters["auth/check"]) {
                // してる場合はホームへ
                next({
                    name: 'home'
                });
            } else {
                // してない場合はそのまま
                next();
            }
        }
    },
    // password reset
    {
        // urlのパス
        path: "/reset",
        // ルートネーム
        name: 'reset',
        // インポートしたページ
        component: Reset,
        // ページコンポーネントが切り替わる直前に呼び出される関数
        // to はアクセスされようとしているルートのルートオブジェクト
        // from はアクセス元のルート
        // next はページの移動先
        beforeEnter(to, from, next) {
            if (store.getters["auth/check"]) {
                next({
                    name: 'home'
                });
            } else {
                next();
            }
        }
    },
    // システムエラー
    {
        path: "/500",
        // ルートネーム
        name: 'system-error',
        component: SystemError
    },
    // not found
    {
        // 定義されたルート以外のパスでのアクセスは <NotFound> が表示
        path: "*",
        // ルートネーム
        name: 'not-found',
        component: NotFound
    }
];

// VueRouterインスタンス
const router = new VueRouter({
    // いつもどうりのURLを使うために「history」モードにする
    mode: "history",
    // 設定したルートオブジェクト
    routes
});

// VueRouterインスタンスをエクスポート
export default router;
