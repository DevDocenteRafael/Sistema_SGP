# Sistema de Gerenciamento de Portfólio (SGP)

Sistema interno do SENAC DF para gerenciar o portfólio de cursos da CPED: cadastro, consulta e apoio à decisão sobre a oferta de cursos por unidade.

**Arquitetura:** aplicação web monolítica — backend Laravel (API REST) + frontend SPA Vue.js + banco MySQL.

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

## Como rodar

1. Clone o repositório:
```cmd
git clone https://github.com/LucasLeal0619/Sistema_SGP.git
```

2. Entre na pasta do sistema:
```cmd
cd Sistema_SGP\SGP
```

3. Crie o banco **SGP** no MySQL (XAMPP / Workbench):
```sql
CREATE DATABASE SGP;
```

4. Se for necessário, configure o arquivo `.env` (usuário e senha do MySQL).  
   Se ainda não existir:
```cmd
copy .env.example .env
```

5. Rode o setup:
```cmd
local-start.cmd
```

6. Suba o back e o front em **terminais diferentes** (ainda dentro de `SGP`):

Terminal 1:
```cmd
php artisan serve
```

Terminal 2:
```cmd
npm run dev
```

7. Abra no navegador: http://127.0.0.1:8000/login

### Logins de teste

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | `administrador@df.senac.br` | `senac2025` |
| Editor | `editor@df.senac.br` | `editor2025` |
| Consultor | `consultor@df.senac.br` | `consultor2025` |
