FROM php:8.3-fpm

# Installing dependencies:
RUN apt-get update && apt-get install -y \
    build-essential \
    libonig-dev \
    libzip-dev \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    sudo \
    libicu-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libxslt1-dev \
    libpq-dev \
    --no-install-recommends \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Yarn via Corepack:
RUN corepack enable && corepack prepare yarn@stable --activate

# Installing Ukrainian locale:
RUN sed -i 's/# uk_UA.UTF-8 UTF-8/uk_UA.UTF-8 UTF-8/' /etc/locale.gen \
    && locale-gen \
    && update-locale LANG=uk_UA.UTF-8

# Environment variables:
ENV APP_ENV=dev \
    APP_DEBUG=1 \
    LC_ALL=uk_UA.UTF-8 \
    LANG=uk_UA.UTF-8 \
    LANGUAGE=uk_UA.UTF-8

# Installing PHP extensions:
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    zip \
    exif \
    pcntl \
    opcache \
    intl \
    bcmath \
    dom \
    xml \
    xsl \
    simplexml \
    soap \
    sockets

# GD:
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# Composer:
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Symfony CLI (wildcard for path compatibility across installer versions):
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Xdebug:
# RUN pecl install xdebug && docker-php-ext-enable xdebug

# PHP ini config files:
COPY docker/php/conf.d/ $PHP_INI_DIR/conf.d/

RUN usermod -u 1000 www-data

# Healthcheck:
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php-fpm -t || exit 1

WORKDIR /var/www/html

CMD ["php-fpm"]