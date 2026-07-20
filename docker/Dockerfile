FROM php:8.4-cli

# Extension installer (resolves system libs automatically).
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

# soap = SUNAT transport (inherited from quipu-lite); zip = envelope packaging;
# pcov = coverage driver. dom + openssl already ship in the base image.
RUN install-php-extensions soap zip pcov

# Composer from its official image.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Pest type-coverage needs more than the default 128M; CI's setup-php raises it
# already, this matches the container to that.
RUN echo 'memory_limit=1G' > /usr/local/etc/php/conf.d/quipu.ini

WORKDIR /app
