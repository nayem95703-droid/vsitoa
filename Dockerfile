FROM php:8.2-apache

# প্রয়োজনীয় পিএইচপি এক্সটেনশন ইনস্টল করা
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql pdo_pgsql

# কম্পোজার (Composer) ইনস্টল করা
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# প্রজেক্টের সব ফাইল সার্ভারে কপি করা
COPY . /var/www/html

# অ্যাপাচি সার্ভারের রুট ফোল্ডার লারাভেলের public-এ সেট করা
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# লারাভেলের স্টোরেজ ফোল্ডারের পারমিশন দেওয়া
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# কম্পোজার রান করা
RUN composer install --no-dev --optimize-autoloader

EXPOSE 80