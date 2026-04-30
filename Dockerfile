FROM php:8.2-apache

# Cài đặt extension để PHP kết nối được với MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Bật mod_rewrite để làm đường dẫn đẹp (SEO URL) nếu Schedio có dùng
RUN a2enmod rewrite

# Cấp quyền để PHP có thể upload file (Duyệt demo)
RUN chown -R www-data:www-data /var/www/html/