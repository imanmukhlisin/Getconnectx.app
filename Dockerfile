FROM php:8.2-fpm

# Arguments ini harus SAMA PERSIS dengan yang ada di docker-compose.yml
ARG USER_NAME
ARG USER_ID

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libzip-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u ${USER_ID} -d /home/${USER_NAME} ${USER_NAME}
RUN mkdir -p /home/${USER_NAME}/.composer && \
    chown -R ${USER_NAME}:${USER_NAME} /home/${USER_NAME}

# Set working directory
WORKDIR /var/www

USER ${USER_NAME}
