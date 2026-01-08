# Use the official PHP image with Apache
FROM php:8.1-apache

# Install system tools needed for Composer
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip

# Install Composer (The tool that installs your Firebase library)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory inside the server
WORKDIR /var/www/html

# Copy all your files (index.php, composer.json) into the server
COPY . .

# Run Composer to install the Firebase library
RUN composer install --no-dev --optimize-autoloader

# Allow Apache to read the files
RUN chown -R www-data:www-data /var/www/html

# Tell Render to use Port 80
EXPOSE 80
