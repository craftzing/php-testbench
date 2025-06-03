#!/usr/bin/env sh

# Add a non-root user we can use to run the app
addgroup -g 1000 -S app \
  && adduser -u 1000 -S app -G app \
  && chown app /code

apk -U upgrade
apk --no-cache add \
    ${PHPIZE_DEPS} \
    curl

pecl install pcov && docker-php-ext-enable pcov
