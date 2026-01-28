#!/bin/bash

echo "🚀 Optimizando Condoriri SG..."

# Detener contenedores existentes
echo "📦 Deteniendo contenedores..."
docker-compose down

# Limpiar cache de Docker
echo "🧹 Limpiando cache..."
docker system prune -f

# Reconstruir con optimizaciones
echo "🔨 Reconstruyendo con optimizaciones..."
docker-compose build --no-cache

# Levantar servicios
echo "▶️ Iniciando servicios optimizados..."
docker-compose up -d

# Esperar a que los servicios estén listos
echo "⏳ Esperando servicios..."
sleep 10

# Verificar estado
echo "✅ Verificando estado..."
docker-compose ps

echo "🎉 Optimización completada!"
echo "📍 Accede a: http://localhost:8080"
echo "📊 Monitorea logs con: docker-compose logs -f app"