# Defining environment.
ARG APP_ENV=development

################################################################################
#
## Base.
#
FROM alpine:3.19 as base

WORKDIR /var/www

LABEL Maintainer="Liam Walder <contact@liamwalder.com>"

# Trust this project public key to trust the packages.
#ADD https://packages.whatwedo.ch/php-alpine.rsa.pub /etc/apk/keys/php-alpine.rsa.pub

# Make sure you can use HTTPS/.
#RUN apk --update add ca-certificates

# Add PHP  repository.
#RUN echo "https://packages.whatwedo.ch/php-alpine/v3.11/php-8.1" >> /etc/apk/repositories

# Install packages
RUN apk add --no-cache php \
    php83-common \
    php83-fpm \
    php83-bcmath \
    php83-gd \
    php83-pdo \
    php83-opcache \
    php83-zip \
    php83-phar \
    php83-curl \
    php83-openssl \
    php83-mbstring \
    php83-json \
    php83-xml \
    php83-xmlwriter \
    php83-dom \
    php83-pdo_sqlite \
    php83-session \
    php83-exif \
    php83-fileinfo \
    php83-tokenizer \
    php83-pcntl \
    php83-pgsql \
    php83-posix \
    php83-pdo_pgsql \
    php83-redis \
    php83-simplexml \
    php83-iconv \
    py3-setuptools \
    curl \
    nginx \
    bash \
    supervisor \
    busybox-suid \
    jpegoptim\
    && ln -s /usr/bin/php83 /usr/bin/php

# Configure bash.
RUN sed -i 's/bin\/ash/bin\/bash/g' /etc/passwd

# Remove default server definition.
RUN #rm /etc/nginx/conf.d/default.conf

# Make sure files/folders needed by the processes are accessable when they run under the nobody user.
RUN chown -R nobody.nobody /run && \
  chown -R nobody.nobody /var/lib/nginx && \
  chown -R nobody.nobody /var/log/nginx

################################################################################
#
## Development.
#
FROM base AS development-base

# Install node and npm.
RUN apk add --update nodejs-current npm
RUN mkdir -p /.npm
RUN chown -R nobody:nobody /.npm

# Install composer.
RUN curl -sS https://getcomposer.org/installer | /usr/bin/php -- --install-dir=/usr/local/bin --filename=composer

# Switch to use a non-root user from here on
USER nobody

################################################################################
#
## PHP Dependencies
#
FROM composer:2.0 as production-vendor

WORKDIR /var/www

COPY database/ database/
COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --no-dev \
    --prefer-dist

COPY . .

RUN composer dump-autoload

################################################################################
#
## Frontend.
#
FROM node:18.7.0 as production-frontend

WORKDIR /var/www

COPY package.json webpack.mix.js tailwind.config.js ./

RUN npm install

COPY resources/js ./resources/js
COPY resources/css ./resources/css
COPY resources/fonts ./resources/fonts
COPY resources/views ./resources/views

#RUN npm run production

################################################################################
#
## Production.
#
FROM base AS production-base

## Configure nginx
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf

# Configure PHP-FPM
COPY docker/php/fpm-pool.conf /etc/php83/php-fpm.d/www.conf
COPY docker/php/php.ini /etc/php83/conf.d/custom.ini

# Configure supervisord
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create cron
COPY ./docker/conf/scheduler /var/spool/cron/crontabs/
RUN cat /var/spool/cron/crontabs/scheduler >> /var/spool/cron/crontabs/root
RUN mkdir -p /var/log/cron && touch /var/log/cron/cron.log

VOLUME /var/log/cron

# Switch to use a non-root user from here on
USER nobody

# Copy in application files.
COPY --chown=nobody . .

# Copy Composer dependencies.
COPY --chown=nobody --from=production-vendor /var/www/vendor ./vendor

# Copy assets.
COPY --from=production-frontend /var/www/public/js/ ./public/js/
COPY --from=production-frontend /var/www/public/css/ ./public/css/

################################################################################
#
## Final.
#
FROM ${APP_ENV}-base as final

# Expose the port nginx is reachable on.
EXPOSE 80

# Let supervisord start nginx & php-fpm.
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Configure a healthcheck to validate that everything is up&running
#HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping
