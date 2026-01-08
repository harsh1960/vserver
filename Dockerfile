# Use the official PHP image
FROM php:8.1-apache

# 1. INSTALL THE EXTENSION INSTALLER (The Magic Fix)
# This script automatically installs complex extensions like gRPC without errors
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# 2. INSTALL gRPC, PROTOBUF, and ZIP
# We install grpc (required by error), protobuf (usually needed too), and zip (for composer)
RUN install-php-extensions grpc protobuf zip unzip git

# 3. Enable Apache Rewrite Module
RUN a2enmod rewrite

# 4. Set Working Directory
WORKDIR /var/www/html

# 5. Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Copy your project files
COPY . .

# 7. Install PHP Dependencies
RUN composer install --no-dev --optimize-autoloader

# 8. Expose Port
EXPOSE 80
