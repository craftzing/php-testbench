#!/usr/bin/env sh

# Add a non-root user we can use to run the app
addgroup -g 1000 -S app \
  && adduser -u 1000 -S app -G app \
  && chown app /code

apk upgrade \
  && apk --update --no-cache add \
    ${PHPIZE_DEPS} \
    curl

  # Install Composer
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer