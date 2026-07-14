FROM php:8.2.1-apache as base

RUN apt-get update && apt-get install -y --no-install-recommends \
    libbz2-dev \
    libc-client-dev \
    libkrb5-dev \
    libxslt-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap

RUN pecl install -o -f redis \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis

RUN pecl install apcu \
    && docker-php-ext-enable apcu \
    && echo "apc.enable_cli=1" >> /usr/local/etc/php/php.ini

# imagick: the asset pipeline (zzImageConverter.php, zzCropTester.php, CosmeticsImage.php)
# requires Imagick — the GD fallbacks were removed, so these fatal without it. Matches the
# deployed box, which gets Imagick via newhost/harden-webp.sh. (libmagickwand-dev is the
# build header, installed in the apt layer above.)
RUN pecl install imagick \
    && docker-php-ext-enable imagick

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd

RUN docker-php-ext-install zip mysqli pdo pdo_mysql shmop bz2

# Apply default PHP configuration
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini
# Remove memory limit
RUN sed -i 's/memory_limit = .*/memory_limit = -1/' /usr/local/etc/php/php.ini

# Raise upload limits above the app's 10MB cosmetic-image cap (PHP defaults are
# upload_max_filesize=2M / post_max_size=8M, which rejected valid uploads early).
RUN { \
        echo "upload_max_filesize=12M"; \
        echo "post_max_size=13M"; \
    } > /usr/local/etc/php/conf.d/zz-uploads.ini

# Enable opcache; validate_timestamps + revalidate_freq=0 re-checks file mtimes
# every request, so live edits and generated-code rewrites apply immediately
RUN docker-php-ext-enable opcache \
    && { \
        echo "opcache.enable=1"; \
        echo "opcache.enable_cli=1"; \
        echo "opcache.validate_timestamps=1"; \
        echo "opcache.revalidate_freq=0"; \
        echo "opcache.memory_consumption=256"; \
        echo "opcache.max_accelerated_files=20000"; \
    } > /usr/local/etc/php/conf.d/zz-opcache.ini


# Development stage (only builds if --target=dev is used)
FROM base as dev
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Production stage (builds by default)
FROM base as prod