FROM php:7.2.7-fpm-alpine3.7

RUN apk update; \
    apk upgrade;

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN docker-php-ext-install mysqli
RUN docker-php-ext-enable xdebug

RUN mkdir -p /home/www-data/.php/composer \
    && cd /home/www-data/.php/composer \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar composer \
    && echo "export PATH=/home/www-data/.php/composer:$PATH" > /home/www-data/.profile

COPY composer.json /var/www/html/composer.json

RUN /home/www-data/.php/composer/composer install
