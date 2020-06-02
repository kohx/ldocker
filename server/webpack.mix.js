const mix = require('laravel-mix');

mix.js("resources/js/app.js", "public/js");

mix.browserSync({
  // アプリの起動アドレスを「nginx」
  proxy: "nginx",
  // ブラウザを自動で開かないようにする
  open: false
});
