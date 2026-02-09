FROM php:8.2-apache

# Installer les dépendances système et les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql zip

# Activer le module de réécriture d'Apache
RUN a2enmod rewrite

# Configurer le document root d'Apache vers le dossier public de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier les fichiers du projet
WORKDIR /var/www/html
COPY . .

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader

# Donner les permissions aux dossiers de stockage
RUN chown -R www-data:www-data storage bootstrap/cache

# Exposer le port par défaut (Render injectera le sien)
EXPOSE 80

# Script de démarrage pour lancer les migrations et Apache
CMD php artisan migrate --force && php artisan db:seed --force && apache2-foreground
