@echo off
setlocal EnableExtensions
cd /d "%~dp0"

echo.
echo === SGP - desenvolvimento local - MySQL ===
echo.
echo Antes: XAMPP aberto, Start no MySQL, banco SGP criado.
echo Nao clone o projeto dentro do OneDrive.
echo.

echo --- Espaco em disco (pre-check) ---
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\windows\check-disk.ps1" > "%TEMP%\sgp-disk-check.txt" 2>&1
set "DISK_EXIT=%ERRORLEVEL%"
type "%TEMP%\sgp-disk-check.txt"
if "%DISK_EXIT%"=="2" (
  echo.
  echo [CRITICO] Pouco espaco livre. Login/API/MySQL podem falhar de novo.
  echo           Liberar espaco no disco C: antes de continuar ^(Temp, Downloads, caches^).
  echo           Este script NAO apaga arquivos.
  echo.
  set /p SEGUIR_CRIT="Continuar mesmo assim? [s/N]: "
  if /i not "%SEGUIR_CRIT%"=="s" (
    echo Abortado pelo operador.
    exit /b 2
  )
) else if "%DISK_EXIT%"=="1" (
  echo.
  echo [AVISO] Espaco livre baixo. Recomenda-se liberar disco antes de desenvolver.
  set /p SEGUIR_WARN="Continuar? [S/n]: "
  if /i "%SEGUIR_WARN%"=="n" exit /b 1
  if /i "%SEGUIR_WARN%"=="nao" exit /b 1
) else if not "%DISK_EXIT%"=="0" (
  echo [AVISO] Nao foi possivel validar o disco ^(exit %DISK_EXIT%^). Seguindo.
)
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

echo --- Liberando portas 8000 e 5173 ---
call :matarPorta 8000
call :matarPorta 5173

echo --- BACK: SGP_Back ---
cd /d "%~dp0SGP_Back"

echo [0/9] Pastas do Laravel
call :garantirPasta "bootstrap\cache"
if errorlevel 1 exit /b 1
call :garantirPasta "storage\app\public"
call :garantirPasta "storage\framework\cache\data"
call :garantirPasta "storage\framework\sessions"
call :garantirPasta "storage\framework\testing"
call :garantirPasta "storage\framework\views"
call :garantirPasta "storage\logs"
echo        Pastas ok

echo [1/9] Arquivo .env do back
set "ENV_NOVO=0"
if not exist ".env" (
  copy ".env.example" ".env" >nul
  set "ENV_NOVO=1"
  echo        Criado a partir do .env.example
  echo.
  echo        Abriu o Bloco de Notas no SGP_Back\.env
  echo        Ajuste nesta maquina e SALVE:
  echo          DB_PORT       - porta do MySQL no XAMPP desta maquina
  echo          DB_USERNAME   - em geral root
  echo          DB_PASSWORD   - senha do MySQL, ou vazio
  echo          DB_DATABASE   - SGP
  echo        Feche o Bloco de Notas para o script continuar.
  echo.
  start /wait notepad.exe ".env"
) else (
  echo        Ja existia. Conferindo o que vai ser usado:
)

echo        --- banco deste .env ---
findstr /B "DB_HOST= DB_PORT= DB_DATABASE= DB_USERNAME= DB_PASSWORD=" ".env"
echo        -------------------------
if "%ENV_NOVO%"=="0" (
  echo        Se a porta ou senha desta maquina forem outras, edite SGP_Back\.env agora.
  set /p EDITAR="        Abrir o .env para ajustar? [s/N]: "
)
if /i "%EDITAR%"=="s" start /wait notepad.exe ".env"

echo [2/9] Pacotes PHP - composer install
if not exist "vendor\autoload.php" (
  call composer install
  if errorlevel 1 exit /b 1
  echo        Concluido. Pasta vendor criada.
) else (
  echo        Ja existia vendor. Atualizando autoload...
  call composer dump-autoload
  if errorlevel 1 exit /b 1
)

echo [3/9] APP_KEY - php artisan key:generate
findstr /C:"APP_KEY=base64:" ".env" >nul 2>&1
if errorlevel 1 (
  php artisan key:generate
  if errorlevel 1 exit /b 1
  echo        Key gerada no .env
) else (
  echo        Ja tinha APP_KEY, nao gerei de novo
)

echo [4/9] Limpar cache do Laravel - php artisan optimize:clear
php artisan optimize:clear
if errorlevel 1 (
  echo [ERRO] Nao consegui limpar o cache. Confira o .env e tente de novo.
  exit /b 1
)
echo        Cache limpo - evita back antigo em memoria

echo [5/9] Banco - php artisan migrate
echo        Usa HOST/PORTA/USUARIO/SENHA do SGP_Back\.env desta maquina.
php artisan migrate --force
if errorlevel 1 (
  echo.
  echo [ERRO] Migrate falhou. O Laravel leu o SGP_Back\.env.
  echo   1. XAMPP: Start no MySQL, luz verde
  echo   2. Banco criado: CREATE DATABASE SGP;
  echo   3. No .env desta maquina, confira:
  echo        DB_HOST=127.0.0.1
  echo        DB_PORT=     porta que o XAMPP mostra nesta maquina
  echo        DB_DATABASE=SGP
  echo        DB_USERNAME=root
  echo        DB_PASSWORD= senha desta maquina, ou vazio
  echo   Depois rode local-start.cmd de novo.
  exit /b 1
)
echo        Tabelas ok

echo [6/9] Storage - php artisan storage:link
php artisan storage:link
echo        Link publico ok

echo [7/9] Dados de teste - php artisan db:seed
php artisan db:seed --force
if errorlevel 1 (
  echo.
  echo [ERRO] Seed falhou.
  exit /b 1
)
echo        Usuarios demo e exemplos ok
echo        Login: administrador@df.senac.br / senac2025

echo.
echo --- FRONT: SGP_Front ---
cd /d "%~dp0SGP_Front"

echo [8/9] Arquivo .env do front
if not exist ".env" (
  copy ".env.example" ".env" >nul
  echo        Criado a partir do .env.example
) else (
  echo        Ja existia, nao copiei de novo
)

echo [9/9] Pacotes do front - npm install
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
echo Pasta do back usada: %~dp0SGP_Back
echo.

set /p SUBIR="Subir back e front agora? [S/n]: "
if /i "%SUBIR%"=="n" goto fim
if /i "%SUBIR%"=="nao" goto fim

echo Abrindo duas janelas a partir destas pastas...
start "SGP - Backend" /D "%~dp0SGP_Back" cmd /k "php artisan optimize:clear && php artisan serve --host=127.0.0.1 --port=8000"
start "SGP - Frontend" /D "%~dp0SGP_Front" cmd /k npm run dev

echo.
echo Nao feche essas duas janelas.
echo Aguarde o Vite subir e abra http://127.0.0.1:5173/login
echo A porta 8000 e so a API deste SGP_Back.
echo Se o login falhar com mensagem estranha, feche tudo e rode local-start.cmd de novo.

:fim
echo.
endlocal
exit /b 0

:garantirPasta
if exist "%~1\" (
  attrib -R "%~1" /S /D >nul 2>&1
  exit /b 0
)
if exist "%~1" (
  echo        Apagando arquivo que estava no lugar da pasta %~1
  del /f /q "%~1" >nul 2>&1
)
mkdir "%~1" >nul 2>&1
if not exist "%~1\" (
  echo [ERRO] Nao consegui criar a pasta %~1
  echo        Tire o projeto do OneDrive e rode de novo.
  exit /b 1
)
attrib -R "%~1" /S /D >nul 2>&1
exit /b 0

:matarPorta
set "PORTA=%~1"
for /f "tokens=5" %%P in ('netstat -ano ^| findstr /R /C:":%PORTA% .*LISTENING"') do (
  echo        Encerrando processo na porta %PORTA% - PID %%P
  taskkill /F /PID %%P >nul 2>&1
)
exit /b 0
