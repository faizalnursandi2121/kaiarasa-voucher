FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite-dev \
    libzip-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_sqlite zip

# Worker PHP-FPM mewarisi environment kontainer (APP_KEY, TURNSTILE_*, dll
# dari panel deployment) tanpa dibersihkan clear_env.
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf

# Production PHP hardening: warning/notice TIDAK BOLEH tercetak ke respons
# (merusak JSON + membocorkan path). Catat ke stderr saja.
RUN set -eu; \
    echo "display_errors = Off" > /usr/local/etc/php/conf.d/zz-prod.ini; \
    echo "display_startup_errors = Off" >> /usr/local/etc/php/conf.d/zz-prod.ini; \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/zz-prod.ini; \
    echo "error_log = /proc/self/fd/2" >> /usr/local/etc/php/conf.d/zz-prod.ini; \
    echo "error_reporting = E_ALL & ~E_DEPRECATED" >> /usr/local/etc/php/conf.d/zz-prod.ini

# Configure Nginx
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Configure Supervisor
COPY docker/supervisord.conf /etc/supervisord.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Create Database directory explicitly & Set Permissions
RUN mkdir -p /var/www/html/app/Database && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Copy Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port
EXPOSE 80

# Use Entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Start Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
