@echo off
setlocal
cd /d "%~dp0"

echo.
echo === SGP - desenvolvimento local - MySQL ===
echo.
echo Antes: XAMPP aberto, botao Start no MySQL, banco SGP criado.
echo Nao clone o projeto dentro do OneDrive. Prefira C:\projetos ou a Area de Trabalho local.
echo.

where php >nul 2>&1
if errorlevel 1 (
  echo [ERRO] PHP nao encontrado no PATH. Instale PHP 8.2+ e tente de novo.
  echo        Se usa XAMPP, adicione C:\xampp\php ao PATH.
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

echo --- BACK: SGP_Back ---
cd /d "%~dp0SGP_Back"

echo [0/8] Pastas do Laravel
call :garantirPasta "bootstrap\cache"
call :garantirPasta "storage\app\public"
call :garantirPasta "storage\framework\cache\data"
call :garantirPasta "storage\framework\sessions"
call :garantirPasta "storage\framework\testing"
call :garantirPasta "storage\framework\views"
call :garantirPasta "storage\logs"
echo        Pastas ok

echo [1/8] Arquivo .env do back
if not exist ".env" (
  copy ".env.example" ".env" >nul
  echo        Criado a partir do .env.example
) else (
  echo        Ja existia, nao copiei de novo
)

echo [2/8] Pacotes PHP - composer install
if not exist "vendor\autoload.php" (
  call composer install
  if errorlevel 1 exit /b 1
  echo        Concluido. Pasta vendor criada.
) else (
  echo        Ja existia vendor. Atualizando autoload...
  call composer dump-autoload
  if errorlevel 1 exit /b 1
)

echo [3/8] APP_KEY - php artisan key:generate
findstr /C:"APP_KEY=base64:" ".env" >nul 2>&1
if errorlevel 1 (
  php artisan key:generate
  if errorlevel 1 exit /b 1
  echo        Key gerada no .env
) else (
  echo        Ja tinha APP_KEY, nao gerei de novo
)

echo [4/8] Banco - php artisan migrate
php -r "exit(@fsockopen('127.0.0.1',3306,$e,$s,2)?0:1);"
if errorlevel 1 (
  echo.
  echo [ERRO] MySQL nao esta rodando na porta 3306.
  echo        Abra o XAMPP Control Panel e clique em Start no MySQL.
  echo        Espere ficar verde e rode local-start.cmd de novo.
  exit /b 1
)
php artisan migrate --force
if errorlevel 1 (
  echo.
  echo [ERRO] Migrate falhou. Confira:
  echo   - MySQL do XAMPP ligado - botao Start, luz verde
  echo   - Banco SGP criado: CREATE DATABASE SGP;
  echo   - DB_USERNAME e DB_PASSWORD no arquivo SGP_Back\.env
  echo     Padrao XAMPP: usuario root e senha vazia.
  exit /b 1
)
echo        Tabelas ok

echo [5/8] Storage - php artisan storage:link
php artisan storage:link
echo        Link publico ok

echo [6/8] Dados de teste - php artisan db:seed
php artisan db:seed --force
if errorlevel 1 (
  echo.
  echo [ERRO] Seed falhou.
  exit /b 1
)
echo        Usuarios demo e exemplos ok

echo.
echo --- FRONT: SGP_Front ---
cd /d "%~dp0SGP_Front"

echo [7/8] Arquivo .env do front
if not exist ".env" (
  copy ".env.example" ".env" >nul
  echo        Criado a partir do .env.example
) else (
  echo        Ja existia, nao copiei de novo
)

echo [8/8] Pacotes do front - npm install
if not exist "node_modules\" (
  call npm install
  if errorlevel 1 exit /b 1
  echo        Concluido. Pasta node_modules criada.
) else (
  echo        Ja existia node_modules, pulei o npm install
)

cd /d "%~dp0"

echo.
echo === Setup concluido ===
echo Login: http://127.0.0.1:5173/login
echo Admin: administrador@df.senac.br / senac2025
echo.

set /p SUBIR="Subir back e front agora? [S/n]: "
if /i "%SUBIR%"=="n" goto fim
if /i "%SUBIR%"=="nao" goto fim

echo Abrindo duas janelas: php artisan serve e npm run dev ...
start "SGP - Backend" /D "%~dp0SGP_Back" cmd /k php artisan serve
start "SGP - Frontend" /D "%~dp0SGP_Front" cmd /k npm run dev

echo.
echo Nao feche essas duas janelas.
echo Aguarde o Vite subir e abra http://127.0.0.1:5173/login
echo A porta 8000 e so a API.

:fim
echo.
endlocal
exit /b 0

:garantirPasta
if not exist "%~1\" mkdir "%~1"
exit /b 0
