FROM php:7.4-fpm

COPY php.ini /usr/local/etc/php/

#timezone
ENV TZ Asia/Tokyo
RUN echo "${TZ}" > /etc/timezone \
   && dpkg-reconfigure -f noninteractive tzdata

RUN apt-get update

#pdo
RUN apt-get install -y libzip-dev mariadb-client \
  && docker-php-ext-install zip pdo_mysql

#node
RUN apt-get install -y wget git unzip libpq-dev \
    && : 'Install Node.js' \
    &&  curl -sL https://deb.nodesource.com/setup_12.x | bash - \
    && : 'Install PHP Extensions' \
    && apt-get install -y nodejs

#composer
COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /composer
ENV PATH $PATH:/composer/vendor/bin

#git
RUN apt-get install -y git

# phpredis
RUN git clone https://github.com/phpredis/phpredis.git /usr/src/php/ext/redis
RUN docker-php-ext-install redis

# vim
RUN apt-get install -y vim

RUN apt-get -y install cron
COPY testcron /etc/cron.d/testcron
RUN chmod 0644 /etc/cron.d/testcron
RUN crontab /etc/cron.d/testcron
RUN touch /var/log/cron.log

# workdir
WORKDIR /var/www