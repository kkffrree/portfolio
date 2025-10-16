FROM php:8.2-apache
WORKDIR /var/www/html
COPY . .       # portfolio/ の中身が /var/www/html にコピーされる
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN docker-php-ext-install pdo pdo_sqlite
RUN a2enmod rewrite
EXPOSE 80
CMD ["apache2-foreground"]