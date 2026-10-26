FROM php:8.4-apache

RUN pecl install xdebug 
RUN docker-php-ext-enable xdebug 

RUN apt-get update && apt-get install -y libpq-dev libfreetype-dev libjpeg62-turbo-dev libpng-dev
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install -j$(nproc) gd && docker-php-ext-enable gd

RUN docker-php-ext-install pdo pdo_pgsql
RUN a2enmod rewrite

RUN echo "post_max_size=20M" > /usr/local/etc/php/conf.d/php-uploadsize.ini
RUN echo "upload_max_filesize=20M" >> /usr/local/etc/php/conf.d/php-uploadsize.ini
RUN echo "client_max_body_size=20M" >> /usr/local/etc/php/conf.d/php-uploadsize.ini
RUN echo "memory_limit=20M" >> /usr/local/etc/php/conf.d/php-uploadsize.ini