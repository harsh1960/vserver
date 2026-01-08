# Use the official PHP image
FROM php:8.1-apache

# 1. INSTALL SYSTEM DEPENDENCIES (Critical Fix)
# We add zip, unzip, and git so Composer doesn't crash
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# 2. Enable Apache Rewrite Module
RUN a2enmod rewrite

# 3. Set Working Directory
WORKDIR /var/www/html

# 4. Copy Composer from the official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Copy your project files
COPY . .

# 6. Install PHP Dependencies
# We use --ignore-platform-reqs to prevent errors if specific PHP extensions are missing
# (Google Firestore works fine in REST mode without them)
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# 7. Expose Port
EXPOSE 80
