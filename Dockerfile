FROM php:8.5-cli AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpcre2-dev \
    libz-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pcntl posix

# Build and install OpenSwoole
RUN pecl install openswoole \
    && docker-php-ext-enable openswoole

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install dependencies first (layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# Create storage directories
RUN mkdir -p storage/views

EXPOSE 8000

CMD ["php", "bin/swoole-server.php", "--port=8000", "--workers=4"]
