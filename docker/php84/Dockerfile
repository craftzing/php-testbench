FROM php:8.4-cli-alpine3.21

WORKDIR /code

USER root
COPY docker/setup.sh .
RUN chmod u+x /code/setup.sh && /code/setup.sh && rm /code/setup.sh
COPY --from=composer:2.8 /usr/bin/composer /usr/local/bin/composer

USER app
