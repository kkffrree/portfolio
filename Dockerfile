FROM php:8.2-apache
WORKDIR /var/www/html
COPY . .
RUN a2enmod rewrite
EXPOSE 80
CMD ["apache2-foreground"]