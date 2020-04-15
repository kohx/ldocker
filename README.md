# ldocker

laravel7 php7.4 nginx mysql redis in docker, and vue in Laravel Mix
dockerでlaravel環境 (laradockを使わない)
​

## 要件

- 64 bit Windows 10 Pro
- docker -> php7.4, nginx, mysql, redis, nodejs
- laravel7
​

---

## フォルダ構成

```text
├── server
├── docker-compose.yml <- １これ作る
└─── docker
   ├─ php
   │   ├── Dockerfile <- ２これ作る
   │   └── php.ini <- ３これ作る
   ├── nginx
   │   └── default.conf <- ４これ作る
   └── db
​
```

---

## ファイルを用意する

### １. docker-compose.ymlをつくる

```yml:docker-compose.yml
# Docker Composeのバージョン
version: "3"
​
# 作成するコンテナを定義
services:
  ## phpサービス
  php:
    ### コンテナの名前
    container_name: my_php
    ### コンテナの元になるDockerfileがおいてあるパス
    build: ./docker/php
    ### ホストPC側のプログラムソースディレクトリをマウント
    volumes:
      - ./server:/var/www
    ports:
      - "3000:3000"
      - "3001:3001"
​
  ## nginxサービス
  nginx:
    ### Nginxコンテナのもとになるイメージを指定
    image: nginx
    ### コンテナの名前
    container_name: my_nginx
    ### ホスト側の80番ポートとコンテナ側の80番ポートをつなげる
    ports:
      - 80:80
    ### ホストPC側をnginxにマウント
    volumes:
      - ./server:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    ### 依存関係
    depends_on:
      - php
​
  ## dbサービス
  db:
    ### イメージを指定
    image: mysql:5.7
    ### コンテナの名前 -> これがホスト名になるので.envでは「DB_HOST=my_db」とする
    container_name: my_db
    ### db設定
    environment:
      MYSQL_ROOT_PASSWORD: root
      #### .envで使うDB_DATABASEの値
      MYSQL_DATABASE: database
      #### .envで使うDB_USERNAMEの値
      MYSQL_USER: docker
      #### .envで使うDB_PASSWORDの値
      MYSQL_PASSWORD: docker
      TZ: "Asia/Tokyo"
    ### コマンドで設定
    command: mysqld --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
    ### ホスト側のポートとコンテナ側のポートをつなげる
    volumes:
      - ./docker/db/data:/var/lib/mysql
      - ./docker/db/my.cnf:/etc/mysql/conf.d/my.cnf
      - ./docker/db/sql:/docker-entrypoint-initdb.d
    ### ホスト側のポートとコンテナ側のポートをつなげる
    ports:
      - 3306:3306
​
  ## redisサービス
  redis:
    ### イメージを指定
    image: redis:latest
    ### コンテナの名前 -> これがホスト名になるので.envでは「REDIS_HOST=my_redis」とする
    container_name: my_redis
    ### ホスト側のポートとコンテナ側のポートをつなげる
    ports:
      - 6379:6379
```

### 2. Dockerfileをつくる

```Dockerfile:docker/php/Dockerfile
FROM php:7.4-fpm

COPY php.ini /usr/local/etc/php/
​
#time zone setting!
ENV TZ Asia/Tokyo
RUN echo "${TZ}" > /etc/timezone \
   && dpkg-reconfigure -f noninteractive tzdata
​
RUN apt-get update
​
#pdo
RUN apt-get update \
  && apt-get install -y libzip-dev mariadb-client \
  && docker-php-ext-install zip pdo_mysql
​
#node
RUN apt-get install -y wget git unzip libpq-dev \
    && : 'Install Node.js' \
    &&  curl -sL https://deb.nodesource.com/setup_12.x | bash - \
    && : 'Install PHP Extensions' \
    && apt-get install -y nodejs
​
#composer
COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /composer
ENV PATH $PATH:/composer/vendor/bin
​
#git
RUN apt-get install -y git
​
# redis
RUN git clone https://github.com/phpredis/phpredis.git /usr/src/php/ext/redis
RUN docker-php-ext-install redis
​
#vim
RUN apt-get install -y vim
​
# workdir
WORKDIR /var/www
```

### 3. phpのphp.iniをつくる

```ini:docker/php/php.ini
[Date]
date.timezone = "Asia/Tokyo"
[mbstring]
mbstring.internal_encoding = "UTF-8"
mbstring.language = "Japanese"
```

### 4. Nginxのdefault.conf

```Nginx:docker/nginx/default.conf
server {
    listen       80;
    index index.php index.html;
    root /var/www/public;
​
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
​
    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param PATH_INFO $fastcgi_path_info;
    }
}
```

---

## docker を動かす

`docker-compose.yml` ディレクトリで実行

### docker start

```bash:terminal
docker-compose up -d

# 表示
Creating my_redis ... done
Creating my_php   ... done
Creating my_db    ... done
Creating my_nginx ... done
```

### コンテナに入る

`docker-compose exec` + [ サービス名 ] + `bash`

```bash:terminal
docker-compose exec php bash
```

### laravel プロジェクト作成

```bash:terminal
composer create-project --prefer-dist laravel/laravel .

# 以下が出ると成功
Application key set successfully.
```

### ブラウザで表示

ブラウザで`http://localhost/`にアクセス

![キャプチャ5](http://www.aska-ltd.jp/uploads/blogs/2004030655docker5.png)

---

## docker の操作

コンテナから出る

```bash:terminal
exit
```

終了

```bash:terminal
docker-compose stop
```

スタート

```bash:terminal
docker-compose start
```

終了とコンテナ削除

```bash:terminal
docker-compose down
```

コンテナ確認

```bash:terminal
docker-compose ps
```

起動していないものも含めてコンテナ確認

```bash:terminal
docker ps -a
```

使ってないコンテナの削除

```bash:terminal
docker container prune
```

イメージ確認

```bash:terminal
docker images
```

使ってないイメージの削除

```bash:terminal
docker image prune
```

ネットワークの確認

```bash:terminal
docker network list
```

使ってないネットワークの削除

```bash:terminal
docker network prune
```

使ってないコンテナ、ボリューム、ネットワーク、イメージの削除

```bash:terminal
docker system prune
```

---

## laravel のセッティング

### appコンフィグを修正

```php:server/config/app.php
...
​
'timezone' => 'Asia/Tokyo',
...

'locale' => 'ja',
```

### .envを修正

```text:.env
APP_NAME=[project name]
...
​
DB_CONNECTION=mysql
DB_HOST=my_db # コンテナの名前
DB_PORT=3306
DB_DATABASE=database
DB_USERNAME=docker
DB_PASSWORD=docker
...
​
REDIS_HOST=my_redis # コンテナの名前
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### キャッシュクリア

```bash:terminal
php artisan config:clear
php artisan config:cache
```

---

## vue の準備

### npmのインストール

npm-check-updatesでチェック
[npm-check-updates](https://www.npmjs.com/package/npm-check-updates)

```bash:terminal
# npmのアップデート
npm i -g npm
# npmモジュールインストール
npm i
# npm-check-updatesインストール
npm i -g npm-check-updates
# モジュールアップデート確認
ncu -u
# モジュールアップデート
npm update
```

### vueのインストール

後で使うので`vue-router`、 `vuex` も入れる

```bash:terminal
npm i -D vue
npm i -D vue-router
npm i -D vuex
```

### webpack.mix.jsを修正

```javascript:server\webpack.mix.js
const mix = require('laravel-mix');

mix.js("resources/js/app.js", "public/js");
​
mix.browserSync({
  // アプリの起動アドレスを「nginx」
  proxy: "nginx",
  // ブラウザを自動で開かないようにする
  open: false
});
```

### laravelのウェブルートを修正


```php:server/routes/web.php
// API以外はindexを返すようにして、VueRouterで制御
Route::get('/{any?}', fn() => view('index'))->where('any', '.+');
```

### index.blade.phpを作成

`welcome.blade.php`は削除

```blade:server\resources\views\index.blade.php
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name') }}</title>
  <script src="{{ mix('js/app.js') }}" defer></script>
</head>
<body>
  <div id="app"></div>
</body>
</html>
```

### vueのapp.jsを修正

```javascript:server\resources/js/app.js
import "./bootstrap";
import Vue from "vue";
​
new Vue({
  el: "#app",
  template: "<h1>Hello world</h1>"
});
```

### 開発ビルド

```bash
npm run dev
```

ブラウザで`http://localhost/`にアクセス

## ホットリロード

```bash
npm run watch
```

ブラウザで`http://localhost:3000/`にアクセス
​
これで開発がらくになります。
