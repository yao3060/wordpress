FROM wordpress:5.7-apache

RUN pecl install redis-5.1.1 \
    && docker-php-ext-enable redis

CMD ["apache2-foreground"]
