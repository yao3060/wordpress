FROM wordpress:5.8-apache

# add redis extension for Object Cache
RUN pecl install redis-5.3.7 \
    && docker-php-ext-enable redis

# some plugins require PDO
RUN docker-php-ext-install pdo pdo_mysql

CMD ["apache2-foreground"]
