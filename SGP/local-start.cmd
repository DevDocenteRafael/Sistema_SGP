@echo off
setlocal
cd /d "%~dp0"

echo.
echo === SGP - desenvolvimento local (MySQL) ===
echo.
echo Antes de continuar:
echo   1. MySQL/XAMPP ligado
echo   2. Banco SGP criado
echo   3. .env ok (usuario/senha do MySQL)
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
  echo.
  echo [AVISO] Revise o .env agora (DB_USERNAME / DB_PASSWORD) se o MySQL nao for root sem senha.
  echo         Depois rode este script de novo.
  echo.
  pause
  exit /b 0
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

echo Rodando migrations...
php artisan migrate --force
if errorlevel 1 (
  echo.
  echo [ERRO] Migrate falhou. Confira:
  echo   - MySQL do XAMPP ligado
  echo   - Banco SGP criado
  echo   - DB_USERNAME / DB_PASSWORD no .env
  exit /b 1
)

echo Criando link publico do storage (fotos CPED)...
php artisan storage:link

echo Rodando seeders (usuarios demo, exemplos, fotos CPED)...
php artisan db:seed --force
if errorlevel 1 (
  echo.
  echo [ERRO] Seed falhou.
  exit /b 1
)

echo.
echo === Setup concluido ===
echo Login: http://127.0.0.1:8000/login
echo Admin: administrador@df.senac.br / senac2025
echo.

set /p SUBIR="Subir back e front agora? (S/n): "
if /i "%SUBIR%"=="n" goto fim
if /i "%SUBIR%"=="nao" goto fim

echo Abrindo terminais: php artisan serve e npm run dev ...
start "SGP - Backend" cmd /k "cd /d "%~dp0" && php artisan serve"
start "SGP - Frontend" cmd /k "cd /d "%~dp0" && npm run dev"

echo.
echo Back e front iniciados em janelas separadas.
echo Aguarde o Vite subir e acesse http://127.0.0.1:8000/login

:fim
echo.
endlocal
