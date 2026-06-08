# ============================================================
# Dockerfile untuk Railway — Laravel 12 + Livewire 4 + Flux
# PHP 8.3 + upload limits + storage setup untuk Volume
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

# ── PHP CONFIG: upload limits untuk video besar ─────────────
RUN { \
    echo "upload_max_filesize = 250M"; \
    echo "post_max_size = 260M"; \
    echo "memory_limit = 256M"; \
    echo "max_execution_time = 300"; \
    echo "max_input_time = 300"; \
    echo "max_file_uploads = 50"; \
    } > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app

# ── Copy project ─────────────────────────────────────────────
COPY . .

# ── Install dependencies ─────────────────────────────────────
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm install && npm run build

EXPOSE 8080

# ── Start: setup storage (untuk Volume) + migrate + serve ───
# Folder storage dibuat ulang di sini karena Volume mount awalnya kosong
CMD mkdir -p \
        storage/app/public \
        storage/app/livewire-tmp \
        storage/app/public/analisis_videos \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs && \
    chmod -R 775 storage bootstrap/cache && \
    php artisan storage:link --force 2>/dev/null; \
    php artisan migrate --force && \
    php artisan config:cache && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
