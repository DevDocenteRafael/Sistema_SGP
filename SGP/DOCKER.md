# Docker Setup para SGP

Este projeto está configurado para rodar com Docker usando PHP-FPM, Nginx e MySQL 8.

## Estrutura Docker

- **PHP-FPM**: Container com PHP 8.2-FPM para executar a aplicação Laravel
- **Nginx**: Web server que redireciona requisições para o PHP-FPM
- **MySQL 8**: Banco de dados relacional
- **Node.js**: Container opcional para Vite (desenvolvimento frontend)

## Pré-requisitos

- Docker instalado e rodando
- Docker Compose (geralmente vem com Docker Desktop)

## Como iniciar

### 1. Copiar arquivo de ambiente
```bash
cp .env.docker .env
```

### 2. Construir e iniciar os containers
```bash
docker-compose up -d --build
```

Isso vai:
- Construir a imagem PHP-FPM
- Baixar e iniciar Nginx, MySQL e Node.js
- Instalar dependências do Composer
- Criar volumes para persistência de dados

### 3. Executar migrações do Laravel
```bash
docker-compose exec app php artisan migrate
```

### 4. Gerar chave da aplicação
```bash
docker-compose exec app php artisan key:generate
```

### 5. Acessar a aplicação

- **Backend Laravel**: http://localhost
- **Frontend Vite**: http://localhost:5173
- **MySQL**: localhost:3306

## Comandos úteis

### Logs
```bash
# Logs de todos os containers
docker-compose logs -f

# Logs de um container específico
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f mysql
```

### Artisan
```bash
# Rodar comandos Artisan
docker-compose exec app php artisan tinker
docker-compose exec app php artisan make:migration
docker-compose exec app php artisan db:seed
```

### Composer
```bash
# Instalar pacotes
docker-compose exec app composer require vendor/package

# Atualizar dependências
docker-compose exec app composer update
```

### npm/Node
```bash
# Instalar dependências frontend
docker-compose exec node npm install

# Build frontend
docker-compose exec node npm run build
```

### MySQL
```bash
# Acessar MySQL CLI
docker-compose exec mysql mysql -u laravel -p

# Fazer dump do banco
docker-compose exec mysql mysqldump -u laravel -p laravel > backup.sql
```

### Parar containers
```bash
docker-compose down
```

### Remover volumes (cuidado!)
```bash
docker-compose down -v
```

## Variáveis de ambiente

Editar `.env.docker` (ou `.env` se já copiado) para customizar:

- `DB_DATABASE`: nome do banco (padrão: laravel)
- `DB_USERNAME`: usuário MySQL (padrão: laravel)
- `DB_PASSWORD`: senha MySQL (padrão: password)
- `MYSQL_ROOT_PASSWORD`: senha root do MySQL (padrão: root)
- `APP_DEBUG`: ativar modo debug (padrão: true)

## Troubleshooting

### Known issues & fixes (what we ran into)

- `Call to undefined function mb_split()` — PHP `mbstring` was disabled. The Docker `app` image enables `mbstring` and other extensions in `docker/php/php.ini`.

- `Database file ... does not exist` — Your local `.env` may point to SQLite. Use `.env.docker` for MySQL when running with Docker. See "Como voltar para desenvolvimento local" to switch back.

- Vite / Rollup native module error (`@rollup/rollup-linux-x64-musl`) — Cause: host bind-mount of the project hides container `node_modules` and breaks optional native modules. Fixes applied in this repo:
  - `Dockerfile.node` uses `npm install --legacy-peer-deps --no-audit --no-fund` during image build.
  - `docker-compose.yml` mounts a named volume `node_modules` to `/var/www/node_modules` so the container uses its own installed dependencies instead of the host's `node_modules`.
  - If you still see errors, remove host `node_modules` and `package-lock.json` before rebuilding:
    ```cmd
    rmdir /s /q node_modules
    del package-lock.json
    docker-compose down
    docker-compose up -d --build
    ```

- `Application in production` prompt when running `php artisan migrate` — Laravel detected `APP_ENV=production` and asked confirmation. In this setup `APP_ENV` is set to `local` by default. To run migrations non-interactively use:
  ```cmd
  docker-compose exec app php artisan migrate --force
  ```

### Quick diagnostic commands

```cmd
# Show container status
docker-compose ps

# View logs (helpful to paste here when reporting errors)
docker-compose logs --tail=200 --follow node
docker-compose logs --tail=200 --follow app

# Check migration status
docker-compose exec app php artisan migrate:status

# Test HTTP
curl -I http://localhost
```

### Notes about `.env`

- `.env` is not tracked by Git (recommended). If you switched to Docker environment with `copy .env.docker .env` you can restore local dev quickly from `.env.example`:
  ```cmd
  copy .env.example .env
  ```

- If you want to keep a backup before switching, rename:
  ```cmd
  ren .env .env.backup
  copy .env.docker .env
  ```

---

### Port already in use
Se a porta 80 ou 3306 já estão em uso:

Editar `docker-compose.yml` e mudar:
```yaml
ports:
  - "8080:80"  # Muda 80 para 8080
  - "3307:3306"  # Muda 3306 para 3307
```

### Permissões de arquivo
Se houver erro de permissão:
```bash
docker-compose exec app chown -R www-data:www-data /var/www
```

### Resetar banco de dados
```bash
docker-compose exec mysql mysql -u root -proot -e "DROP DATABASE laravel; CREATE DATABASE laravel;"
docker-compose exec app php artisan migrate
```

### Verificar saúde do MySQL
```bash
docker-compose exec mysql mysqladmin ping -h localhost -u root -proot
```

## Produção

Para produção, ajustar em `docker-compose.yml`:

- `APP_DEBUG=false`
- `APP_ENV=production`
- Adicionar volumes para SSL (certificados HTTPS)
- Configurar backup automático do MySQL
- Usar serviço de CI/CD para deploy

## Como voltar para desenvolvimento local (sem Docker)

Se quiser parar Docker e voltar a rodar local com SQLite:

### 1. Parar Docker
```bash
docker-compose down
```

### 2. Restaurar .env para SQLite
O `.env` não está versionado no Git (está em `.gitignore`), então copie de `.env.example`:
```bash
copy .env.example .env
```

### 3. Editar `.env` para SQLite
Altere essas linhas no `.env`:
```
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=laravel
# DB_PASSWORD=password

SESSION_DRIVER=file
```

### 4. Rodar localmente
```bash
php artisan serve
npm run dev
```

## Trocar entre Docker e local

| Ação | Comando |
|------|---------|
| Usar Docker | `copy .env.docker .env && docker-compose up -d --build` |
| Usar local | `copy .env.example .env && php artisan serve` |
| Ver qual está ativo | `type .env \| findstr DB_CONNECTION` |

## Volumes

Os dados do MySQL são persistidos em `sgp-mysql-data` volume. Para limpar (perderá dados):

```bash
docker volume rm sgp-mysql-data
```
