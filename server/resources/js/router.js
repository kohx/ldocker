import Vue from 'vue'
// ルーターをインポート
import VueRouter from 'vue-router'

// ページをインポート
import Home from './pages/Home.vue'
import Login from './pages/Login.vue'
import SystemError from './pages/errors/SystemError.vue'
import NotFound from './pages/errors/NotFound.vue'

// VueRouterをVueで使う
Vue.use(VueRouter)

// パスとページの設定
const routes = [

    // home
    {
        // urlのパス
        path: '/',
        // インポートしたページ
        component: Home
    },
    // login
    {
        // urlのパス
        path: '/login',
        // インポートしたページ
        component: Login
    },
    // システムエラー
    {
        path: "/500",
        component: SystemError
    },
    // not found
    {
        // 定義されたルート以外のパスでのアクセスは <NotFound> が表示
        path: "*",
        component: NotFound
    }
]

// VueRouterインスタンス
const router = new VueRouter({
    // いつもどうりのURLを使うために「history」モードにする
    mode: 'history',
    // 設定したルートオブジェクト
    routes
})

// VueRouterインスタンスをエクスポート
export default router
