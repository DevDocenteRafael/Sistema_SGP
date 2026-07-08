Sistema de Gerenciamento de Portfólio 
Breve descrição do sistema.
Arquitetura do Sistema de Gerenciamento de Portfólio de Cursos do SENAC.

Equipe
- Lucas Leal
- Pedro Comis
- Hillary Oliveira
- Paloma Leandro
- Maria stephanny

Objetivo do Sistema
Esse sistema tem o objetivo principal de gerenciar todos os cursos do SENAC.


Problema que o sistema resolve
Falta de gerenciamento;
Organização das informações dos cursos;
Redução de erros causados por controles manuais;
Controle de acesso e segurança básica do sistema. 

Quem irá utilizar o sistema?

- Cordenação Pedagógica
- Equipe Administrativa
- Gestores

Funcionalidades principais
Algumas funcionalidades do sistema.

- Cadastro completo de cursos.
- Gerenciamento de usuários e controle de acesso.
- Integração com sistemas institucionais (SENAC e SIG).
- Edição e exclusão de cursos.

Protótipo do Sistema
Link do protótipo desenvolvido no Figma.
Protótipo:
https://www.figma.com/make/5QbvhRGGR9rW5bN8RzvzW6/Sistema-de-Gerenciamento-de-Portf%C3%B3lio?t=4tOwiSSgM3mkl7hq-1

Organização do Projeto
O projeto está sendo organizado utilizando:
- Trello
- Metodologia Scrum e Kanban
- Figma 
- Word



Tecnologias utilizadas
Tecnologias que poderão ser utilizadas no desenvolvimento.
Exemplo:
- PHP
- MySQL
- GitHub
- Figma

Status do Projeto
Projeto em desenvolvimento.

## Guia para o time — como rodar o SGP

Há **duas formas** de rodar o projeto:

| Forma | Quando usar | URL do login |
|---|---|---|
| **Local (PHP + Vite)** | Time sem Docker / desenvolvimento no Windows | http://127.0.0.1:8000/login |
| **Docker** | Mesmas versões em qualquer máquina | http://localhost/login |

> **Importante:** não commite o arquivo `.env`. Local usa `.env.example`; Docker usa `.env.docker`.

### Pré-requisito (único na máquina)

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado e com **Engine running**

Não é obrigatório instalar PHP, MySQL ou Node localmente para usar Docker.

### Primeira vez (clone do repositório)

```cmd
git clone <url-do-repositorio>
cd Sistema_SGP\SGP
copy .env.docker .env
docker-start.cmd
```

O script `docker-start.cmd` faz automaticamente:

- Sobe os containers (PHP, Nginx, MySQL, Node/Vite)
- Instala dependências PHP
- Gera `APP_KEY`
- Executa as migrations

### Acessar o sistema

| O quê | URL |
|---|---|
| Login | http://localhost/login |
| Início | http://localhost/app/inicio |
| Cursos | http://localhost/app/cursos |

### MySQL Workbench (opcional — banco do SGP no Docker)

O Workbench **não é obrigatório** para programar. O sistema roda em http://localhost/login sem ele.

Na tela do Workbench você pode ver **duas conexões diferentes**:

| Conexão | Porta | Uso |
|---|---|---|
| **Local instance MySQL80** | 3306 / 3307 | MySQL da máquina — **não é o SGP** |
| **SGP Docker** | **3308** | Banco **do projeto SGP** (Docker) |

**Padrão do time:** porta **3308** para o SGP Docker (`MYSQL_HOST_PORT=3308` no `.env.docker`).

Conexão **SGP Docker** no Workbench:

| Campo | Valor |
|---|---|
| Host | `127.0.0.1` |
| Porta | **3308** |
| Banco | `SGP` |
| Usuário | `laravel` |
| Senha | `password` |

> A porta 3308 é só em `localhost` (sua máquina). Em geral **não precisa de autorização da TI** — não é porta de rede externa.

Se preferir não usar Workbench, acesse o banco pelo terminal:

```cmd
docker-compose exec mysql mysql -u laravel -ppassword SGP
```

### Dia a dia (já configurou antes)

```cmd
cd Sistema_SGP\SGP
docker-compose up -d
```

Acesse: http://localhost/login

Para parar:

```cmd
docker-compose down
```

### Comandos úteis

```cmd
docker-compose ps                              # status dos containers
docker-compose logs -f                         # ver logs
docker-compose exec app php artisan migrate    # rodar migrations
docker-compose exec app php artisan tinker     # console Laravel
docker-compose exec app composer install       # dependências PHP
docker-compose exec node npm install           # dependências front
docker-compose exec node npm run build         # build do front
```

### Atualizar o projeto (git pull)

```cmd
git pull
cd SGP
docker-compose up -d --build
docker-compose exec app php artisan migrate
```

### Versões padronizadas no Docker

| Tecnologia | Versão |
|---|---|
| PHP | 8.2-FPM |
| Laravel | 12 |
| MySQL | 8.0 |
| Node.js | 20 |
| Nginx | Alpine |

Documentação detalhada: [SGP/DOCKER.md](SGP/DOCKER.md)

---

## Como rodar localmente (sem Docker) — recomendado para o time

Não precisa de Docker. Cada pessoa instala PHP, Composer e Node na máquina.

### 1. Pré-requisitos
- **PHP 8.2+** no PATH (extensões: `mbstring`, `fileinfo`, `openssl`, `pdo_mysql`, `gd`, `bcmath`)
- **Composer** no PATH
- **Node.js 20.19+** ou **22.12+** (Vite 7 não roda bem em Node antigo)
- Banco: **SQLite** (padrão, zero configuração) **ou** MySQL 8 local

### 2. Primeira vez

```cmd
cd Sistema_SGP\SGP
local-start.cmd
```

O script prepara `.env`, instala dependências, gera a chave e roda as migrations (SQLite por padrão).

Depois, em **dois terminais** (sempre dentro de `SGP`):

```cmd
php artisan serve
```

```cmd
npm run dev
```

Ou tudo junto: `composer dev`

| O quê | URL |
|---|---|
| Login | http://127.0.0.1:8000/login |
| Início | http://127.0.0.1:8000/app/inicio |

Dados de exemplo (usuários + cursos):

```cmd
php artisan db:seed
```

| Perfil | E-mail | Senha |
|---|---|---|
| Admin | `administrador@df.senac.br` | `senac2025` |
| Editor | `editor@df.senac.br` | `editor2025` |
| Consultor | `consultor@df.senac.br` | `consultor2025` |

### 3. Se você vinha do Docker

```cmd
cd Sistema_SGP\SGP
docker-compose down
copy .env.example .env
php artisan key:generate
local-start.cmd
```

> Não use `.env.docker` fora do Docker (`DB_HOST=mysql` só existe dentro da rede Docker).

### 4. MySQL local (opcional)

No `.env`, troque SQLite por:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=SGP
DB_USERNAME=laravel
DB_PASSWORD=password
```

Crie o banco `SGP` no MySQL da máquina e rode `php artisan migrate`.

### 5. Erros comuns
- `http://localhost/` sem porta → use **http://127.0.0.1:8000** (local não usa a porta 80).
- `mb_split()` → habilite `extension=mbstring` no `php.ini`.
- `Database file ... does not exist` → `type nul > database\database.sqlite`
- `Could not read package.json` → rode os comandos **dentro de `SGP`**, não na pasta pai.
- Node antigo → atualize para 20.19+ ou 22.12+.

### 6. PHP no Windows (se ainda não tiver)
1. Baixe PHP 8.2+, extraia em `C:\php`, copie `php.ini-development` → `php.ini`.
2. Habilite as extensões listadas no passo 1.
3. (Opcional) ca-certs: `curl.cainfo` e `openssl.cafile` apontando para `cacert.pem`.
4. Adicione `C:\php` ao PATH e reinstale/abra o Composer.

## Como rodar com Docker (resumo)

Veja a seção **Guia para o time** no topo deste README.

Passo rápido:

```cmd
cd SGP
copy .env.docker .env
docker-start.cmd
```

Acesse: http://localhost/login

### Mais informações
Leia o arquivo [DOCKER.md](SGP/DOCKER.md) para instruções detalhadas, troubleshooting e comandos úteis.

### Comandos Docker essenciais
```cmd
# Iniciar containers
docker-compose up -d

# Parar containers
docker-compose down

# Ver logs
docker-compose logs -f

# Executar comando Artisan
docker-compose exec app php artisan tinker

# Acessar MySQL no terminal
docker-compose exec mysql mysql -u laravel -ppassword SGP
```
