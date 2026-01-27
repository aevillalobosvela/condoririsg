FROM php:8.1-cli

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libicu-dev \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PostgreSQL COMPLETAS (pgsql + pdo_pgsql)
RUN docker-php-ext-install pdo pgsql pdo_pgsql intl

# Habilitar las extensiones (algunas necesitan esto explícitamente)
RUN docker-php-ext-enable pgsql pdo_pgsql

# Directorio de trabajo
WORKDIR /app

# Copiar aplicación
COPY . .

# Exponer puerto
EXPOSE 8080
