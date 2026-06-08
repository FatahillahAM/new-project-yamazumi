# ============================================================
# Dockerfile Railway — Laravel 12 + Livewire 4 + Flux
# FrankenPHP (production server) — ganti php artisan serve
# Fix root cause: temp file hilang karena artisan serve tidak stabil
# ============================================================

FROM dunglas/frankenphp:1-php8.3

# ── PHP extensions ───────────────────────────────────────────
RUN install-php-extensions \
    gd \
    zip \
    pdo_mysql \
    mbstring \
    bcmath \
    exif \
    opcache

# ── PHP config: upload limits untuk video besar ─────────────
RUN { \
    echo "upload_max_filesize = 250M"; \
    echo "post_max_size = 260M"; \
    echo "memory_limit = 256M"; \
    echo "max_execution_time = 300"; \
    echo "max_input_time = 300"; \
    echo "max_file_uploads = 50"; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# ── Node.js 20 untuk Vite build ─────────────────────────────
RUN apt-get update && apt-get install -y curl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# ── Composer ─────────────────────────────────────────────────
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# ── Install dependencies ─────────────────────────────────────
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm install && npm run build

# ── Struktur storage ─────────────────────────────────────────
# CATATAN: Laravel 11+ disk 'local' root = storage/app/private
RUN mkdir -p \
    storage/app/private/livewire-tmp \
    storage/app/public/analisis_videos \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8080

# ── Start: FrankenPHP serve (HTTP, di belakang proxy Railway) ─
# SERVER_NAME hanya port → FrankenPHP serve HTTP tanpa TLS
CMD php artisan storage:link --force 2>/dev/null; \
    php artisan migrate --force && \
    php artisan config:cache && \
    SERVER_NAME=":${PORT:-8080}" frankenphp php-server --root public/
