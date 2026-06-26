@echo off
cd /d "%~dp0"

echo [SGP] Copiando .env.docker para .env...
copy /Y .env.docker .env >nul

echo [SGP] Subindo containers...
docker-compose up -d --build
if errorlevel 1 exit /b 1

echo [SGP] Aguardando MySQL...
timeout /t 10 /nobreak >nul

echo [SGP] Instalando dependencias PHP...
docker-compose exec app composer install

echo [SGP] Gerando APP_KEY (se necessario)...
docker-compose exec app php artisan key:generate --force

echo [SGP] Rodando migrations...
docker-compose exec app php artisan migrate --force

echo.
echo Pronto!
echo - App:    http://localhost
echo - Vite:   http://localhost:5173
echo - MySQL:  localhost:3308 (banco SGP — Workbench)
echo.
echo Logs: docker-compose logs -f
