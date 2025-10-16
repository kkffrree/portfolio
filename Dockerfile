FROM php:8.2-apache
WORKDIR /var/www/html
COPY . .

# Apache が mmb フォルダにアクセスできるように権限付与
RUN chown -R www-data:www-data /var/www/html/mmb
RUN chmod -R 755 /var/www/html/mmb

# SQLiteの拡張有効化
RUN docker-php-ext-install pdo pdo_sqlite

EXPOSE 80
CMD ["apache2-foreground"]