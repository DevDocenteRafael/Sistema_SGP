# SIPED — Sistema Integrado da Coordenação Pedagógica

Sistema interno do SENAC DF (CPED/DEP) para gestão pedagógica, portfólio de cursos e processos educacionais.

**Arquitetura:** API Laravel 12 (`Back_SIPED`, porta 8000) + SPA Vue 3 (`Front_SIPED`, porta 5173) + MySQL.

## Equipe

Lucas Leal, Pedro Comis, Hillary Oliveira, Paloma Leandro e Maria Stephanny.

## Quem usa

| Perfil | Papel |
|---|---|
| **Administrador** | Gestão do sistema, usuários e auditoria |
| **Editor** | Cadastro, edição, exclusão e importação |
| **Consultor** | Consulta (sem alterar nem importar) |

## O que o sistema faz

- Portfólio: cursos, plano de metas, PCA e eixos oficiais
- Processos: visitas técnicas, horas pedagógicas, ações extensivas, eventos e jornada
- Ciclos de gestão (um seletor no topo vale para as telas periódicas)
- Dashboard, importação de Excel (prévia + upsert) e relatórios em PDF
- Ferramentas da CPED: Kanban, fluxograma, organograma e carômetro
- Controle de acesso por perfil; usuário inativo não entra

## Como rodar em outra máquina

Requisitos no PATH: **PHP 8.2+**, **Composer**, **Node 20.19+ ou 22.12+**. MySQL do XAMPP ligado. **Não clone dentro do OneDrive.**

### 1. Clone

```cmd
git clone https://github.com/DevDocenteRafael/Sistema_SGP.git
cd Sistema_SGP
```

### 2. Crie o banco

No MySQL (phpMyAdmin ou cliente):

```sql
CREATE DATABASE SIPED;
```

### 3. Configure o `.env` desta máquina

O `local-start.cmd` copia os exemplos se ainda não existirem. Confira principalmente o banco em `Back_SIPED\.env`:

| Variável | Valor típico |
|---|---|
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | porta do MySQL neste XAMPP (`3306`, às vezes `3307`/`3308`) |
| `DB_DATABASE` | `SIPED` |
| `DB_USERNAME` | `root` |
| `DB_PASSWORD` | senha deste MySQL, ou vazio |

O front usa `Front_SIPED\.env` com `VITE_API_URL=http://127.0.0.1:8000`.

### 4. Suba o projeto

Na raiz do repositório:

```cmd
local-start.cmd
```

O script instala dependências, gera a `APP_KEY`, roda migrations, limpa cache, cria o link de fotos (`php artisan storage:link`), faz o seed e pergunta se sobe back e front. Quando perguntar, digite **s**. Não feche as duas janelas.

Abra **http://127.0.0.1:5173/login**

Se o login falhar, a tela mostra a mensagem da API. Feche as janelas e rode `local-start.cmd` de novo.

### Logins de teste (após o seed)

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | `administrador@df.senac.br` | `senac2025` |
| Editor | `editor@df.senac.br` | `editor2025` |
| Consultor | `consultor@df.senac.br` | `consultor2025` |

## Se preferir os comandos na mão

```cmd
copy Back_SIPED\.env.example Back_SIPED\.env
copy Front_SIPED\.env.example Front_SIPED\.env
```

Ajuste o `Back_SIPED\.env` (banco desta máquina). Depois:

```cmd
cd Back_SIPED
composer install
php artisan key:generate
php artisan migrate --force
php artisan optimize:clear
php artisan storage:link
php artisan db:seed --force

cd ..\Front_SIPED
npm install
```

Em dois terminais:

```cmd
cd Back_SIPED
php artisan serve --host=127.0.0.1 --port=8000
```

```cmd
cd Front_SIPED
npm run dev
```

A ordem importa: as tabelas (inclusive `cache`) precisam existir **antes** de `optimize:clear`. Se o migrate falhar, não suba o sistema.

`storage:link` aponta `Back_SIPED/public/storage` para `Back_SIPED/storage/app/public`. Sem esse link as fotos da CPED e do carômetro não abrem. Se você mover ou renomear a pasta do projeto, rode `php artisan storage:link` de novo.

## Problemas comuns

- **MySQL recusou a conexão:** confira Start no XAMPP, porta, usuário e senha do `.env` desta máquina — não copie o `.env` de outro computador.
- **Fotos da CPED quebradas depois de mover a pasta:** `cd Back_SIPED` e `php artisan storage:link`.
- **Porta 8000 ou 5173 ocupada:** o `local-start.cmd` encerra o processo antigo nessas portas.
- **Disco C: cheio:** o script avisa e não apaga arquivos. Libere Temp/Downloads antes de continuar.
- **Health check da API:** `GET http://127.0.0.1:8000/up` deve responder 200.

## Homologação

Não use `php artisan serve` nem `npm run dev` como servidor definitivo.

- Front: `cd Front_SIPED && npm run build` e sirva os estáticos pelo servidor web.
- API: IIS/Apache/Nginx apontando para `Back_SIPED/public`.
- Produção: `APP_DEBUG=false` e `LOG_LEVEL=warning`.

## Estrutura

- `Back_SIPED/` — API Laravel
- `Front_SIPED/` — SPA Vue 3 + Vite
- `local-start.cmd` — instala, migra, seeda e sobe os dois no Windows
