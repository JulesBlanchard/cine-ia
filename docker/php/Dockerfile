FROM php:8.2-apache

# Installation des extensions pour MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Activation de l'URL rewriting (pr routage plus tard)
RUN a2enmod rewrite

WORKDIR /var/www/html