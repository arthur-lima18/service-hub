FROM dunglas/frankenphp:1-php8.2-alpine

# Instala extensões necessárias
RUN install-php-extensions \
    pdo_pgsql \
    intl \
    zip \
    opcache

# Define o diretório de trabalho
WORKDIR /app

# Copia os arquivos do projeto
COPY . .

# Instala dependências do Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# Permissões
RUN chown -R unit:unit var public/uploads

# Configurações de ambiente
ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="worker ./public/index.php"

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
