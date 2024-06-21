FROM php:8.1.1-fpm-buster

# Use a better shell syntax
SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# Set environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1 \
  COMPOSER_HOME=/composer

# Copy Composer from the Composer image
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

# Install required packages and PHP extensions
RUN apt-get update && \
  apt-get install -y \
  git \
  unzip \
  libzip-dev \
  libicu-dev \
  libonig-dev \
  libpng-dev \
  supervisor && \
  docker-php-ext-install -j$(nproc) \
  gd \
  intl \
  pdo_mysql \
  zip \
  bcmath && \
  apt-get clean && \
  rm -rf /var/lib/apt/lists/*

# Copy configuration files
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini
COPY ./docker/php/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# Set the working directory
WORKDIR /workspace

# Ensure supervisord can read the configuration file
RUN mkdir -p /var/log/supervisor && \
    touch /var/log/supervisor/supervisord.log && \
    chmod 644 /etc/supervisor/conf.d/supervisor.conf

# Start supervisord
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf", "-n"]
