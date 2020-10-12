const mix = require('laravel-mix');

mix.js("resources/js/app.js", "public/js")
    // 「public/css」に「/main.css」として作成
    .sass('resources/sass/main.scss', 'public/css')
    // 配列で渡したcssを「public/css/app.css」にまとめる
    .styles([
        'resources/css/reset.css',
        'resources/css/style.css',
        'node_modules/font-awesome-animation/dist/font-awesome-animation.min.css'
    ], 'public/css/app.css');

// 本番環境だけバージョンを自動的に付与してキャッシュを自動更新できるようにする
if (mix.inProduction()) {
    mix.version();
}

mix.browserSync({
    // アプリの起動アドレスを「nginx」
    proxy: "nginx",
    // ブラウザを自動で開かないようにする
    open: false
});
