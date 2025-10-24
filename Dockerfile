FROM php:8.2-apache

WORKDIR /var/www/html
COPY . .

# SQLite拡張を確実にビルドして有効化
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-configure pdo_sqlite --with-pdo-sqlite=/usr \
    && docker-php-ext-install pdo_sqlite

# Apacheのアクセス権限を調整
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# URLルーティングに必要ならmod_rewriteを有効化
RUN a2enmod rewrite

EXPOSE 80
CMD ["apache2-foreground"]