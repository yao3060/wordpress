FROM wordpress:5.8-apache

# add redis extension for Object Cache
RUN pecl install redis-5.3.7 \
    && docker-php-ext-enable redis

COPY ./.docker/php/custom.ini $PHP_INI_DIR/conf.d/

CMD ["apache2-foreground"]
