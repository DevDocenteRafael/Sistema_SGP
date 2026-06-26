# Use imagem oficial do PHP com FPM
FROM php:8.2-fpm

# Definir diretório de trabalho
WORKDIR /var/www

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP necessárias
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar arquivos de configuração PHP
COPY docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/
COPY docker/php/php.ini /usr/local/etc/php/

# Copiar arquivos da aplicação
COPY . .

# Definir permissões
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Instalar dependências do Composer (dev)
RUN composer install --optimize-autoloader

# Expor porta (não é necessário para FPM, mas fica documentado)
EXPOSE 9000

# Comando padrão
CMD ["php-fpm"]
