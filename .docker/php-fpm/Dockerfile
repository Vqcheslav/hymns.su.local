FROM php:8.3-fpm

LABEL maintainer="vqcheslav.official@gmail.com"

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions amqp apcu curl gd intl mbstring mysqli opcache pdo_mysql redis soap xdebug xml zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

CMD ["php-fpm"]
