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

- Cadastro e gestão do portfólio (cursos, metas, PCA, eixos).
- Processos pedagógicos (visitas, horas, ações extensivas, eventos).
- Importação de planilhas com backup automático pré-substituição.
- Relatórios em PDF, auditoria de alterações e ferramentas (Kanban, fluxograma, CPED).
- Gerenciamento de usuários e controle de acesso por perfil.

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
- PHP / Laravel
- Vue 3 + Vite
- MySQL / SQLite
- GitHub
- Figma

Status do Projeto
Em uso interno pela CPED / SENAC DF (testes internos). Pronto para homologação com ressalvas técnicas.

## Guia para o time — como rodar o SGP

**Padrão do time:** local (PHP + Vite + MySQL/XAMPP ou SQLite), **sem Docker**.

| Forma | Quando usar | URL do login |
|---|---|---|
| **Local (recomendado)** | Desenvolvimento no Windows | http://127.0.0.1:8000/login |
| **Docker** | Opcional — ver [DOCKER.md](SGP/DOCKER.md) | http://localhost/login |

> **Importante:** não commite o arquivo `.env`. Use `.env.example` no local.

### Pré-requisitos
- **PHP 8.2+** no PATH (extensões: `mbstring`, `fileinfo`, `openssl`, `pdo_mysql`, `gd`, `zip`, `bcmath`)
- **Composer** e **Node.js 20.19+** (ou 22.12+)
- Banco: **MySQL local/XAMPP** ou **SQLite**

### Primeira vez

```cmd
git clone <url-do-repositorio>
cd Sistema_SGP\SGP
local-start.cmd
```

Em dois terminais (sempre dentro de `SGP`):

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

Dados de exemplo (usuários, cursos, fluxogramas, fotos CPED):

```cmd
php artisan storage:link
php artisan db:seed
```

| Perfil | E-mail | Senha |
|---|---|---|
| Admin | `administrador@df.senac.br` | `senac2025` |
| Editor | `editor@df.senac.br` | `editor2025` |
| Consultor | `consultor@df.senac.br` | `consultor2025` |

### Atualizar o projeto (git pull)

```cmd
git pull
cd SGP
composer install
npm install
php artisan migrate
php artisan storage:link
php artisan db:seed --class=CpedEquipeSeeder
```

> O seed da equipe CPED só atualiza/cria membros e copia fotos versionadas — não apaga o resto do banco.  
> Fotos ficam em `SGP/database/data/cped/` e o seed copia para `storage`. Sem `storage:link` + seed, as imagens não aparecem.

### MySQL local (Workbench)

| Campo | Valor típico (XAMPP) |
|---|---|
| Host | `127.0.0.1` |
| Porta | `3306` |
| Banco | `SGP` |
| Usuário | `root` (ou o que estiver no `.env`) |

No `.env`, use `DB_CONNECTION=mysql` apontando para esse banco (não use `.env.docker` — `DB_HOST=mysql` só existe dentro do Docker).

### Erros comuns
- `Class "ZipArchive" not found` / Composer pedindo `ext-gd` / `ext-zip` → no `php.ini` habilite `extension=gd` e `extension=zip`, depois `composer install`.
- Fotos CPED não aparecem → `php artisan storage:link` + `php artisan db:seed --class=CpedEquipeSeeder`.
- `http://localhost/` sem porta → use **http://127.0.0.1:8000**.
- `Could not read package.json` → rode os comandos **dentro de `SGP`**.

### Docker (opcional)

Só se alguém do time quiser. Detalhes em [SGP/DOCKER.md](SGP/DOCKER.md):

```cmd
cd SGP
copy .env.docker .env
docker-start.cmd
```

---

## Detalhes extras (local)

### Se você vinha do Docker

```cmd
cd Sistema_SGP\SGP
docker-compose down
copy .env.example .env
php artisan key:generate
local-start.cmd
```

> Não use `.env.docker` fora do Docker.

### PHP no Windows (se ainda não tiver)
1. Baixe PHP 8.2+, extraia em `C:\php`, copie `php.ini-development` → `php.ini`.
2. Habilite as extensões listadas nos pré-requisitos (`gd`, `zip`, etc.).
3. Adicione `C:\php` ao PATH e reinstale/abra o Composer.
