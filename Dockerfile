FROM php:8.2-apache

# Apacheの作業ディレクトリを設定
WORKDIR /var/www/html

# アプリのファイルをコピー
COPY . .

# Apacheがアクセスできるように権限を修正
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# ---- 👇ここが重要！ MySQLのPDOドライバをインストール ----
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Apacheのrewriteモジュールを有効化
RUN a2enmod rewrite

EXPOSE 80
CMD ["apache2-foreground"]