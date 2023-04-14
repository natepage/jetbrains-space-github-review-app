FROM openswoole/swoole:php8.2-alpine as php

WORKDIR /var/www

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY ./bin /var/www/bin
COPY ./config /var/www/config
COPY ./public /var/www/public
COPY ./src /var/www/src
COPY ./translations /var/www/translations
COPY ./.env /var/www/.env
COPY ./composer.json /var/www/composer.json
COPY ./composer.lock /var/www/composer.lock
COPY ./symfony.lock /var/www/symfony.lock
COPY ./secrets /var/www/secrets

RUN composer install --no-dev --optimize-autoloader

RUN chmod -R 1775 /var/www; \
    chown -R www-data:www-data /var/www;

CMD ["php", "./public/index.php"]
