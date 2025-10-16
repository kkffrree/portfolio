FROM php:8.2-apache

WORKDIR /var/www/html
COPY . .

# ビルドに必要なパッケージをインストール
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# ファイル権限を調整
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

RUN a2enmod rewrite

EXPOSE 80
CMD ["apache2-foreground"]