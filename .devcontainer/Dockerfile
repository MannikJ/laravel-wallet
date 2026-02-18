FROM mcr.microsoft.com/devcontainers/php:1-8.4

# Remove the yarn APT repo to avoid missing key errors during build.
RUN rm -f /etc/apt/sources.list.d/yarn.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends sqlite3 libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
