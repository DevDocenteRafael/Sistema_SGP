@echo off
setlocal
cd /d "%~dp0"

echo.
echo === SGP - desenvolvimento local (sem Docker) ===
echo.

where php >nul 2>&1
if errorlevel 1 (
  echo [ERRO] PHP nao encontrado no PATH. Instale PHP 8.2+ e tente de novo.
  exit /b 1
)

where composer >nul 2>&1
if errorlevel 1 (
  echo [ERRO] Composer nao encontrado no PATH.
  exit /b 1
)

where node >nul 2>&1
if errorlevel 1 (
  echo [ERRO] Node.js nao encontrado no PATH. Use Node 20.19+ ou 22.12+.
  exit /b 1
)

if not exist ".env" (
  echo Copiando .env.example para .env ...
  copy ".env.example" ".env" >nul
)

if not exist "vendor\autoload.php" (
  echo Instalando dependencias PHP...
  call composer install
  if errorlevel 1 exit /b 1
)

if not exist "node_modules\" (
  echo Instalando dependencias Node...
  call npm install
  if errorlevel 1 exit /b 1
)

findstr /C:"APP_KEY=base64:" ".env" >nul 2>&1
if errorlevel 1 (
  echo Gerando APP_KEY...
  php artisan key:generate
)

findstr /B /C:"DB_CONNECTION=sqlite" ".env" >nul 2>&1
if not errorlevel 1 (
  if not exist "database\database.sqlite" (
    echo Criando database\database.sqlite ...
    type nul > "database\database.sqlite"
  )
)

echo Rodando migrations...
php artisan migrate --force
if errorlevel 1 exit /b 1

echo Criando link publico do storage (fotos CPED)...
php artisan storage:link

echo.
echo Pronto. Em dois terminais, na pasta SGP, execute:
echo   Terminal 1: php artisan serve
echo   Terminal 2: npm run dev
echo.
echo Ou use: composer dev
echo.
echo Login: http://127.0.0.1:8000/login
echo Opcional - dados de exemplo (inclui fotos CPED): php artisan db:seed
echo.
endlocal
