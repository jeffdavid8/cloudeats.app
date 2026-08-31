
# Stage 1: Build stage to install Composer dependencies

FROM composer:2.7 AS composer_build

WORKDIR /app

COPY composer.json composer.lock ./

RUN docker-php-ext-install bcmath && \

    docker-php-ext-enable bcmath

# Copy source files needed for autoloading

COPY ./html ./html

ARG SKIP_COMPOSER_INSTALL

RUN mkdir -p /app/vendor

RUN if [ "$SKIP_COMPOSER_INSTALL" != "true" ]; then composer install --no-dev --no-interaction --optimize-autoloader --prefer-source; fi



# Stage 1: Build web server

# Use the official PHP image.

# https://hub.docker.com/_/php

FROM php:8.4-apache AS production_build

# Install required system dependencies and production database drivers
# Install required system dependencies and production database drivers
RUN apt-get update && apt-get install -y \
    libmariadb-dev \
    sqlite3 \
    libsqlite3-dev \
    libpng-dev \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    git \
    unzip \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. Install database and math extensions
RUN docker-php-ext-install -j "$(nproc)" mysqli pdo_mysql pdo_sqlite bcmath

# 3. CONFIGURE AND COMPILE GD (Separated into its own explicit layer)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j "$(nproc)" gd



# Configure PHP for Cloud Run.

# Precompile PHP code with opcache. 

RUN docker-php-ext-install -j "$(nproc)" opcache

RUN set -ex; \

  { \

    echo "; Cloud Run enforces memory & timeouts"; \

    echo "memory_limit = -1"; \

    echo "max_execution_time = 0"; \

    echo "short_open_tag = On"; \

    echo "; File upload at Cloud Run network limit"; \

    echo "upload_max_filesize = 32M"; \

    echo "post_max_size = 32M"; \

    echo "; Configure Opcache for Containers"; \

    echo "opcache.enable = On"; \

    echo "opcache.validate_timestamps = Off"; \

    echo "; Configure Opcache Memory (Application-specific)"; \

    echo "opcache.memory_consumption = 32"; \

    echo "; --- Error Logging ---"; \

    echo "; Log errors to stderr for Cloud Run logging"; \

    echo "log_errors = On"; \

    echo "; Optionally, set the error reporting level (E_ALL shows all errors, warnings, notices)"; \

    echo "error_reporting = E_ALL"; \

    echo "display_errors = Off ; Never display errors in production environment"; \

    echo "display_startup_errors = Off ; Never display startup errors in production environment"; \

  } > "$PHP_INI_DIR/conf.d/cloud-run.ini"


# Make local storage directory
RUN mkdir -p /var/www/storage
RUN mkdir -p /var/www/storage/backups

COPY ./storage/default_db.json /var/www/storage/backups/default_db.json

# Copy in custom code from the host machine.

WORKDIR /var/www/html



COPY ./html ./







# Copy the installed vendor dependencies from the build stage



COPY --from=composer_build /app/vendor /var/www/vendor







# Ensure the webserver has permissions to execute index.php



RUN chown -R www-data:www-data /var/www/html



RUN chown -R www-data:www-data /var/www/vendor



# Configure PHP for development.

# Switch to the production php.ini for production operations.

# RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# https://github.com/docker-library/docs/blob/master/php/README.md#configuration

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]



FROM production_build AS development_build

RUN apt-get update && apt-get install -y openssl && rm -rf /var/lib/apt/lists/*



# Now use the a2enmod and a2ensite utilities

RUN a2enmod ssl \

    && a2enmod socache_shmcb \

    && a2enmod headers \

    && a2enmod rewrite \

    && a2ensite default-ssl \

    && a2dissite 000-default



# Disable the default SSL site which uses a nonexistent certificate.



RUN a2dissite default-ssl







COPY --from=composer_build /app/vendor /var/www/vendor

# Create self-signed certificates for local use

#RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \

#    -keyout /etc/ssl/private/localhost.key \

#    -out /etc/ssl/certs/localhost.crt \

#    -subj "/C=US/ST=IN/L=Matthews/O=Organization/CN=webserver1.local"

#

COPY ./config/webserver1.local.crt /etc/ssl/certs/localhost.crt

COPY ./config/webserver1.local.key /etc/ssl/private/localhost.key

COPY ./config/ports.conf /etc/apache2/ports.conf

COPY ./config/apache-dev.conf /etc/apache2/sites-available/000-default.conf



# Override opcache settings for development

RUN set -ex; \

  { \

    echo "; Development Opcache Overrides"; \

    echo "opcache.validate_timestamps = On"; \

    echo "opcache.revalidate_freq = 0"; \

    echo "opcache.max_accelerated_files = 3000"; \

    echo "; Development Error Display"; \

    echo "display_errors = On"; \

    echo "display_startup_errors = On"; \

  } > "$PHP_INI_DIR/conf.d/development-overrides.ini"



# Enable your custom default site configuration.

# This is also needed to ensure it takes precedence over any defaults.

RUN a2ensite 000-default.conf

# Expose port 443 for HTTPS

EXPOSE 80 443



# Default command to run Apache

CMD ["httpd", "-D", "FOREGROUND"]
