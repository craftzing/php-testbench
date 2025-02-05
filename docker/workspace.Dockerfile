FROM php:8.3-cli-alpine3.21

WORKDIR /code

USER root

# Install container deps that apply to all target environments...
COPY docker/setup.sh .
RUN chmod u+x /code/setup.sh && /code/setup.sh && rm /code/setup.sh

USER app