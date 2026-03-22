FROM php:8.2-apache

# Install PHP extensions yang dibutuhkan
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Aktifkan mod_rewrite untuk Apache (jika pakai .htaccess)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Salin semua file proyek ke dalam container
COPY . /var/www/html/

# Buat folder upload dan beri permission yang sesuai
RUN mkdir -p /var/www/html/assets/images/articles \
    && chmod -R 777 /var/www/html/assets/images/articles

# Konfigurasi Apache agar AllowOverride aktif (untuk .htaccess)
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/blog.conf \
    && a2enconf blog

EXPOSE 80
