FROM php:8.3-fpm

ARG DOCKER_UID=1000
ARG DOCKER_GID=1000

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        curl \
        git \
        unzip \
        zip \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
        libicu-dev \
        libssl-dev \
        default-mysql-client \
        redis-tools \
        netcat-traditional \
        openssl \
        ca-certificates \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath \
        mbstring \
        intl \
        zip \
        pcntl \
        opcache \
        gd \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN if [ "$(id -u www-data)" != "${DOCKER_UID}" ]; then \
        usermod -u ${DOCKER_UID} www-data; \
    fi \
 && if [ "$(id -g www-data)" != "${DOCKER_GID}" ]; then \
        groupmod -g ${DOCKER_GID} www-data; \
    fi

COPY php.ini /usr/local/etc/php/conf.d/zz-custom.ini
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

RUN rm -f /etc/nginx/sites-enabled/default \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /var/log/supervisor /run/php /etc/nginx/certs \
    && chown -R www-data:www-data /run/php

WORKDIR /var/www/html

EXPOSE 80 443

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
