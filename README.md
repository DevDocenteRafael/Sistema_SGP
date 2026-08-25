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

PHP 8.2+, Composer, Node 20.19+ ou 22.12+ no PATH. MySQL do XAMPP ligado. Não clone dentro do OneDrive.

1. Clone:
```cmd
git clone https://github.com/DevDocenteRafael/Sistema_SGP.git
cd Sistema_SGP
```

2. Crie o banco no MySQL:
```sql
CREATE DATABASE SGP;
```

3. Configure o `.env` desta máquina:
```cmd
copy SGP_Back\.env.example SGP_Back\.env
copy SGP_Front\.env.example SGP_Front\.env
```
Abra `SGP_Back\.env` e ajuste:
- `DB_PORT` — porta do MySQL neste XAMPP (3306, 3307, 3308…)
- `DB_USERNAME` — em geral `root`
- `DB_PASSWORD` — senha deste MySQL, ou vazio
- `DB_DATABASE` — `SGP`

4. Rode:
```cmd
local-start.cmd
```
O script limpa cache do Laravel, garante o seed e sobe **só** o `SGP_Back` na porta 8000 e o `SGP_Front` na 5173. Se já houver outro `php artisan serve` antigo aberto, ele encerra a porta antes. Quando perguntar se sobe back e front, digite **s**. Não feche as duas janelas.

5. Abra **http://127.0.0.1:5173/login**  
Se o login falhar, a tela agora mostra a mensagem real da API. Feche as janelas, rode `local-start.cmd` de novo e entre com as credenciais abaixo.

### Logins de teste

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | `administrador@df.senac.br` | `senac2025` |
| Editor | `editor@df.senac.br` | `editor2025` |
| Consultor | `consultor@df.senac.br` | `consultor2025` |
