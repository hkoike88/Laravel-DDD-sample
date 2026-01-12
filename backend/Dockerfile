# ===================================
# Backend Dockerfile (PHP-FPM + Laravel)
# ===================================
# Laravel 12 + PHP 8.2 開発環境

FROM php:8.2-fpm-alpine

# 必要なパッケージのインストール
RUN apk add --no-cache \
    bash \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev \
    linux-headers \
    autoconf \
    g++ \
    make

# PHP 拡張のインストール
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        opcache

# PCOV拡張をインストール（テストカバレッジ用）
RUN pecl install pcov && docker-php-ext-enable pcov

# PCOV設定（packages ディレクトリを含む全体をカバレッジ対象にする）
RUN echo "pcov.directory=/var/www/html" > /usr/local/etc/php/conf.d/pcov.ini

# Composer のインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリ設定
WORKDIR /var/www/html

# Composer の依存関係をインストール（キャッシュ効率化）
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

# ソースコードをコピー
COPY . .

# 必要なディレクトリを作成
RUN mkdir -p bootstrap/cache storage/framework/{sessions,views,cache}

# オートローダーの生成とスクリプト実行
RUN composer dump-autoload --optimize

# ストレージとキャッシュディレクトリの権限設定
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# PHP-FPM のポート
EXPOSE 9000

# PHP-FPM を起動
CMD ["php-fpm"]
