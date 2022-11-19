FROM wordpress:5.8-apache

RUN pecl install redis-5.3.7 \
    && docker-php-ext-enable redis

CMD ["apache2-foreground"]
