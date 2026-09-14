# Стенд OpenCart 4.1.x на PHP 8.3 для разработки и проверки модуля ApiShip (пакет 4.x)
FROM php:8.3-apache

ARG OC_VERSION=4.1.0.4

RUN apt-get update \
	&& apt-get install -y --no-install-recommends unzip curl libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libcurl4-openssl-dev libonig-dev libicu-dev default-mysql-client \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j"$(nproc)" gd zip mysqli curl mbstring intl \
	&& a2enmod rewrite \
	&& rm -rf /var/lib/apt/lists/*

RUN curl -fsSL -o /tmp/opencart.zip "https://github.com/opencart/opencart/releases/download/${OC_VERSION}/opencart-${OC_VERSION}.zip" \
	&& mkdir -p /tmp/opencart \
	&& unzip -q /tmp/opencart.zip -d /tmp/opencart \
	&& cp -r /tmp/opencart/upload/. /var/www/html/ \
	&& rm -rf /tmp/opencart /tmp/opencart.zip \
	&& mv /var/www/html/.htaccess.txt /var/www/html/.htaccess 2>/dev/null || true

COPY php.ini /usr/local/etc/php/conf.d/zz-opencart.ini
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
	&& chown -R www-data:www-data /var/www/html

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
