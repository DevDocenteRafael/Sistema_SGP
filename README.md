# Sistema de Gerenciamento de Portfólio (SGP)

Sistema interno do SENAC DF para gerenciar o portfólio de cursos da CPED: cadastro, consulta e apoio à decisão sobre a oferta de cursos por unidade.

**Arquitetura:** backend Laravel (API REST, `SGP_Back`) + frontend SPA Vue.js (`SGP_Front`, servido pelo Vite) + banco MySQL, cada parte em sua própria origem/porta.

## Equipe

- Lucas Leal
- Pedro Comis
- Hillary Oliveira
- Paloma Leandro
- Maria Stephanny

## Objetivo do sistema

Gerenciar os cursos do SENAC, centralizando informações acadêmicas e operacionais e apoiando a definição de:

- quais cursos ofertar;
- em quais unidades;
- com base em critérios pedagógicos e estratégicos.

## Problema que o sistema resolve

- Falta de gerenciamento centralizado do portfólio
- Desorganização das informações dos cursos
- Erros causados por controles manuais
- Falta de controle de acesso e segurança básica

## Quem utiliza o sistema

| Perfil no sistema | Papel |
|---|---|
| **Administrador** | Gestão do sistema, usuários e auditoria |
| **Editor** | Cadastro, edição, exclusão e importação de dados |
| **Consultor** | Consulta e acompanhamento (sem alterar/importar) |

**Principais usuários:** Coordenação Pedagógica (CPED), equipe administrativa, gestores e responsáveis de eixo.

## Funcionalidades principais

- Cadastro e gestão do portfólio (cursos, plano de metas, PCA, eixos)
- Processos pedagógicos (visitas técnicas, horas pedagógicas, ações extensivas, eventos)
- Dashboard e filtros para apoio à decisão
- Importação de planilhas Excel (prévia + backup antes de substituir)
- Relatórios em PDF
- Auditoria de alterações (quem, quando e o quê)
- Ferramentas da CPED (Kanban, fluxograma, organograma e carômetro)
- Controle de usuários com acesso por perfil (RBAC)
- Usuário ativo/inativo (inativo não entra no sistema)

## Protótipo

https://prototipo-sgp.vercel.app/

## Organização do projeto

- Trello
- Metodologia Scrum e Kanban
- Figma
- GitHub
- Documentação de arquitetura de software (SENAC-DF)

## Tecnologias

- PHP / Laravel (API REST)
- Vue 3 + Vite (SPA)
- MySQL
- Laravel Sanctum (autenticação)
- GitHub / Figma

## Status do projeto

Em uso interno pela CPED / SENAC DF (testes internos). Pronto para homologação com ressalvas técnicas.

---

## Estrutura do projeto

- `SGP_Back/` — API Laravel 12 (PHP), porta **8000**
- `SGP_Front/` — SPA Vue 3 + Vite, porta **5173**
- `local-start.cmd` — instala, migra, seeda e sobe os dois

## Como rodar

Precisa ter no PATH: **PHP 8.2+**, **Composer**, **Node 20.19+ ou 22.12+**, e o **MySQL do XAMPP** ligado. Se o `php` não for reconhecido, coloque `C:\xampp\php` no PATH.

1. Clone e entre na pasta:
```cmd
git clone https://github.com/DevDocenteRafael/Sistema_SGP.git
cd Sistema_SGP
```

2. Crie o banco **SGP** no MySQL (XAMPP / Workbench):
```sql
CREATE DATABASE SGP;
```

3. Rode **uma vez**:
```cmd
local-start.cmd
```

No terminal você vai ver 8 passos, nesta ordem:

1. Copia `SGP_Back\.env` se ainda não existir  
2. `composer install` — pacotes PHP (`vendor/`)  
3. `php artisan key:generate` — gera a `APP_KEY`  
4. `php artisan migrate` — cria as tabelas  
5. `php artisan storage:link`  
6. `php artisan db:seed` — usuários e dados de teste  
7. Copia `SGP_Front\.env` se ainda não existir  
8. `npm install` — pacotes do front (`node_modules/`)

Se o passo já foi feito antes, o script escreve “já existia” e segue. Não precisa rodar duas vezes.

No final aparece:

`Subir back e front agora? [S/n]:`

Digite **s** e Enter. Duas janelas abrem sozinhas. Não feche elas.

Se o MySQL não for `root` sem senha, o passo 4 (migrate) falha. Aí abra `SGP_Back\.env`, ajuste `DB_USERNAME` / `DB_PASSWORD` e rode `local-start.cmd` de novo.

4. Abra no navegador: **http://127.0.0.1:5173/login**  
A tela do SGP é o front (`:5173`). O back (`:8000`) é só a API.

No dia a dia é o mesmo comando (`local-start.cmd`). Se preferir no braço: `cd SGP_Back` → `php artisan serve` e, em outro terminal, `cd SGP_Front` → `npm run dev`.

### Logins de teste

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | `administrador@df.senac.br` | `senac2025` |
| Editor | `editor@df.senac.br` | `editor2025` |
| Consultor | `consultor@df.senac.br` | `consultor2025` |
