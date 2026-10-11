FROM php:8.2.1-apache as base

# Debian bullseye (this image's base) is archived, which broke every build in 2026-09:
#   E: Release file for .../debian-security/dists/bullseye-security/InRelease is expired
# and the security pool then 404s on libkrb5-dev / libssl-dev / libxslt1-dev. Fixes that do
# NOT work: `-o Acquire::Check-Valid-Until=false` alone (clears the expiry error, packages
# still 404), and pointing the security suite at archive.debian.org (it has no
# debian-security for bullseye -- verified 404).
#
# So: serve main + updates from archive.debian.org and drop the security suite, which has no
# archived counterpart. Archived Release files are permanently expired by design, hence
# Check-Valid-Until off. Trade-off: package versions are the release/updates ones rather than
# the last security builds -- acceptable for a local dev image.
#
# Staying on bullseye rather than bumping to a bookworm-based php tag is deliberate: bookworm
# dropped libc-client-dev, which `docker-php-ext-install imap` below needs.
RUN set -eux; \
    printf '%s\n' \
      'deb http://archive.debian.org/debian bullseye main' \
      'deb http://archive.debian.org/debian bullseye-updates main' \
      > /etc/apt/sources.list; \
    printf 'Acquire::Check-Valid-Until "false";\n' > /etc/apt/apt.conf.d/99archive-expired

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
# 64M covers the Generator Workspace card-art bundle (Hellbreak's WebpImages/ is ~15MB) and matches
# GeneratedCardDataArchive::MAX_ARCHIVE_BYTES; post_max_size stays a little above it for form fields.
RUN { \
        echo "upload_max_filesize=64M"; \
        echo "post_max_size=66M"; \
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

# Dev-only ML tooling for the SWUSim bot value model (docs/superpowers/specs/2026-09-19-swusim-value-model-design.md
# §6a). OFF by default: this Dockerfile is shared by every sim, and only docker-compose-files/swusim.dev.yml sets
# DEV_ML=1. Debian's packages, not pip — the image has no pip, and baking it in here (not a one-off install) keeps it
# across a container recreate, the documented fix for a broken dev env. Production never runs Python.
ARG DEV_ML=0
RUN if [ "$DEV_ML" = "1" ]; then \
      apt-get update && apt-get install -y --no-install-recommends python3-numpy python3-sklearn \
      && rm -rf /var/lib/apt/lists/*; \
    fi

# Production stage (builds by default)
FROM base as prod