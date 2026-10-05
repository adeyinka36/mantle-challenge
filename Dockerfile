# Official PHP image: ships with ctype, iconv, dom, xml, mbstring and pdo_sqlite.
FROM php:8.4-cli-alpine

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/tmp/composer

WORKDIR /app

EXPOSE 8000
