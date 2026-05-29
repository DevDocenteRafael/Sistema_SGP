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

## Como rodar este projeto em outro computador
Este guia foi testado no Windows e mostra os passos necessários para instalar e executar o sistema sem perrengue.

### 1. Pré-requisitos
- PHP 8.2 ou superior (recomendado 8.5). No Windows, use uma instalação manual ou o instalador oficial.
- Composer instalado e disponível no PATH.
- Node.js 20.19+ ou 22.12+ (a versão 20.16 não funciona bem com Vite 7).
- npm (vem com o Node.js).
- Editor/terminal com privilégios normais.

### 2. Preparar o PHP no Windows
1. Baixe uma versão compatível de PHP (8.2+).
2. Extraia para `C:\php` ou outra pasta de sua escolha.
3. Copie `php.ini-development` para `php.ini` em `C:\php`.
4. No `php.ini`, habilite as extensões necessárias:
   - `extension=mbstring`
   - `extension=fileinfo`
   - `extension=php_openssl.dll`
5. Opcional, mas recomendado para Composer HTTPS:
   - baixe `cacert.pem` em `https://curl.se/ca/cacert.pem`
   - defina em `php.ini`:
     - `curl.cainfo = C:/php/extras/cacert.pem`
     - `openssl.cafile = C:/php/extras/cacert.pem`
6. Adicione `C:\php` ao PATH do Windows.
7. Feche e reabra o terminal.

### 3. Instalar Composer
No terminal CMD, verifique:
```cmd
php -v
composer -V
```
Se o Composer não estiver disponível, use:
```cmd
php C:\php\composer.phar install
```
ou instale o Composer globalmente.

### 4. Instalar Node.js
- Baixe o instalador oficial em https://nodejs.org/
- Instale a versão LTS compatível: `20.19+` ou `22.12+`.
- Verifique no CMD:
```cmd
node -v
npm -v
```

### 5. Clonar o projeto e entrar na pasta correta
No CMD do Windows:
```cmd
cd C:\Users\lucas\OneDrive\Desktop\Sistema_SGP\SGP
```
O `package.json` e o backend estão dentro de `SGP`, então todos os comandos npm devem ser executados lá.

### 6. Instalar dependências
No diretório `SGP` no CMD:
```cmd
composer install
npm install
```

### 7. Preparar o ambiente Laravel
Ainda em `SGP` no CMD:
```cmd
copy .env.example .env
php artisan key:generate
```
Se estiver usando SQLite, crie o arquivo de banco:
```cmd
type nul > database\database.sqlite
```
Se preferir usar MySQL, configure `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` no `.env`.

### 8. Executar o projeto
No terminal 1 (Laravel) no CMD:
```cmd
php artisan serve
```
Acesse:
- `http://127.0.0.1:8000`

No terminal 2 (Vite) no CMD:
```cmd
npm run dev
```
Acesse:
- `http://localhost:5173`

### 9. Erros comuns e como resolver
- `ERR_CONNECTION_REFUSED` em `http://localhost/`:
  - não use apenas `http://localhost/`; use `http://127.0.0.1:8000` para o Laravel ou `http://localhost:5173` para o Vite.
- `Call to undefined function mb_split()`:
  - habilite `extension=mbstring` no `php.ini`.
- `Database file ... does not exist`:
  - crie `database\database.sqlite` ou configure corretamente o `.env`.
- `Could not read package.json`:
  - execute `npm install` dentro de `SGP`, não na pasta pai.
- `Vite requires Node.js version 20.19+ or 22.12+`:
  - atualize o Node.js para a versão exigida.

### 10. Dica final
Sempre trabalhe na pasta `SGP` para comandos como `composer install`, `npm install`, `php artisan serve` e `npm run dev`. O caminho correto evita a maioria dos erros.

## Como rodar com Docker
Este projeto também pode ser executado usando Docker com PHP-FPM, Nginx e MySQL 8.

### Pré-requisitos
- Docker instalado e rodando
- Docker Compose

### Instruções rápidas
1. Entre na pasta `SGP`:
```cmd
cd C:\Users\lucas\OneDrive\Desktop\Sistema_SGP\SGP
```

2. Copie o arquivo de configuração Docker:
```cmd
copy .env.docker .env
```

3. Construa e inicie os containers:
```cmd
docker-compose up -d --build
```

4. Execute as migrações:
```cmd
docker-compose exec app php artisan migrate
```

5. Acesse a aplicação:
- Backend: `http://localhost`
- Frontend Vite: `http://localhost:5173`

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

# Acessar banco de dados
docker-compose exec mysql mysql -u laravel -p
```
