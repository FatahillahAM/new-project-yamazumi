# ============================================================
# Dockerfile untuk Railway — Laravel 12 + Livewire 4 + Flux
# PHP 8.3 + semua extension yang dibutuhkan
# ============================================================

FROM php:8.3-cli

ENV COMPOSER_ALLOW_SUPERUSER=1

# ── System dependencies + PHP extensions ────────────────────
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        zip \
        pdo_mysql \
        mbstring \
        bcmath \
        exif \
    && rm -rf /var/lib/apt/lists/*

# ── Node.js 20 (untuk Vite build) ───────────────────────────
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# ── Composer ─────────────────────────────────────────────────
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# ── Copy project ─────────────────────────────────────────────
COPY . .

# ── Install PHP dependencies (production only) ──────────────
# --no-dev: skip Pest/PHPUnit yang butuh PHP 8.3 dev deps
RUN composer install --no-dev --optimize-autoloader --no-interaction

# ── Install & build frontend assets ─────────────────────────
RUN npm install && npm run build

# ── Set permissions untuk storage ───────────────────────────
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8080

# ── Start: migrate + serve ───────────────────────────────────
CMD php artisan migrate --force && \
    php artisan config:cache && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
