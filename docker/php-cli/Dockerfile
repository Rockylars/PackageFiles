# Smaller than other installs
FROM php:8.0-cli-alpine

# Bash
RUN apk add --no-cache bash

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

VOLUME /opt/project
WORKDIR /opt/project