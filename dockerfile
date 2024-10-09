FROM php:8.3

# Installer les dépendances système nécessaires
RUN apt-get update -y && apt-get install -y \
    openssl \
    zip \
    unzip \
    git \
    libonig-dev \
    libzip-dev \
    libpng-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    mariadb-client \
    && docker-php-ext-install pdo_mysql mbstring zip gd curl

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installer l'AWS CLI
RUN curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip" \
    && unzip awscliv2.zip \
    && ./aws/install \
    && rm -rf aws awscliv2.zip

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers du projet
COPY . /app

# Définir les permissions
RUN chown -R www-data:www-data /app

# Installer les dépendances du projet
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --verbose

# Installer JWT
RUN composer require php-open-source-saver/jwt-auth

#donner tous les autorisation 
RUN chmod -R 777 /app/storage /app/bootstrap/cache

# Exposer le port 8181
EXPOSE 8181

# Commande de démarrage
CMD php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider" && \
    php artisan key:generate && \
    php artisan migrate:fresh && \
    php artisan storage:link && \
    php artisan jwt:secret && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan serve --host=0.0.0.0 --port=8181
