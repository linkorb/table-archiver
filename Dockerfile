FROM php:7.4-zts
USER root
RUN apt-get update && \
    apt-get install -y --no-install-recommends git zip unzip libzip4 libzip-dev ssh libxml2-dev && \
    curl -sSL https://getcomposer.org/composer.phar -o /usr/bin/composer && \
    chmod +x /usr/bin/composer && \
    composer selfupdate && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    mkdir /app

RUN docker-php-ext-install opcache && docker-php-ext-enable opcache

RUN curl -sSL https://github.com/krakjoe/parallel/archive/develop.zip -o /tmp/parallel.zip \
    && unzip /tmp/parallel.zip -d /tmp \
    && cd /tmp/parallel-* \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && rm -rf /tmp/parallel*

RUN composer global require hirak/prestissimo

WORKDIR /app
