# Planejamento do Projeto SGP

## Objetivo

Organizar o desenvolvimento do Sistema de Gerenciamento de Portfolio do SENAC DF a partir do prototipo do Figma, usando uma stack simples para o grupo:

- HTML, CSS e JavaScript
- PHP com Laravel
- Vue.js
- Docker
- MySQL

O prototipo exportado do Figma usa React, TypeScript, Vite e Tailwind. Essas tecnologias devem ser usadas apenas como referencia visual e funcional. A implementacao final do projeto sera feita com Laravel, Vue.js e MySQL.

## Escopo Inicial

O sistema deve permitir:

- Login, cadastro e recuperacao de senha.
- Dashboard com indicadores do portfolio.
- Listagem de cursos por area de conhecimento.
- Cadastro, edicao, visualizacao e exclusao de cursos.
- Gerenciamento de usuarios e permissoes.
- Consulta de plano de metas, processos e quantidade de cursos por eixo.
- Relatorios e filtros para apoiar coordenacao pedagogica, equipe administrativa e gestores.

## Divisao Da Equipe

### Backend

Responsavel por Laravel, regras de negocio, API, autenticacao, permissoes e integracao com banco.

### Frontend

Responsavel por telas Vue.js, componentes, responsividade e integracao com API.

### Banco De Dados

Responsavel por modelagem MySQL, migrations, seeders e consistencia dos dados.

### Documentacao

Responsavel por README, guias de instalacao, padrao de uso do GitHub, manual basico do sistema e registro de decisoes.

### QA

Responsavel por roteiro de testes, validacao das telas, bugs, regressao e criterios de aceite.

## Fluxo Git Sugerido

1. Criar uma branch a partir da `main`.
2. Usar nomes simples, por exemplo:
   - `feature/backend-auth`
   - `feature/frontend-login`
   - `feature/database-modelagem`
   - `docs/guia-instalacao`
   - `testes/roteiro-qa`
3. Fazer commits pequenos e descritivos.
4. Abrir Pull Request para `main`.
5. Pedir revisao de pelo menos uma pessoa.
6. So fazer merge quando os criterios de aceite da issue estiverem completos.

## Modulos Do Prototipo

As principais telas identificadas no prototipo sao:

- Login
- Cadastro de usuario
- Recuperacao e reset de senha
- Dashboard
- Menu lateral
- Cursos por area
- Novo curso
- Usuarios
- Plano de metas
- Processos de visitas tecnicas
- Processos de horas pedagogicas
- Valores PCA 2025
- Quantidade de cursos por eixo

## Dados Principais

### Curso

Campos esperados:

- titulo
- status
- modalidade
- carga horaria
- codigo DN
- codigo SIG
- identificacao
- tipo
- ultima revisao
- processo SEI
- valores
- observacoes
- unidade
- compativel com bolsa
- comercial
- PCN
- PCR
- area/eixo

### Usuario

Campos esperados:

- nome
- email
- senha
- perfil
- unidade
- area de atuacao
- telefone
- status

### Area/Eixo

Campos esperados:

- nome
- descricao
- cor/identificacao visual
- quantidade de cursos

## Ordem Recomendada De Trabalho

1. Preparar base do projeto Laravel, Vue, Docker e MySQL.
2. Definir modelagem inicial do banco.
3. Implementar autenticacao e permissoes.
4. Implementar CRUD de cursos.
5. Implementar telas principais do frontend.
6. Integrar frontend com API.
7. Criar dashboard e consultas.
8. Documentar instalacao, uso e fluxo de contribuicao.
9. Executar testes de QA.
10. Revisar e ajustar bugs antes da entrega.

## Cronograma Recomendado

Este cronograma organiza as issues em etapas. A equipe deve tentar concluir uma etapa antes de depender fortemente da proxima.

### Etapa 1 - Preparar O Projeto

Issues:

- `#1` Setup Laravel + Vue + Docker + MySQL
- `#2` Modelagem do banco de dados
- `#8` Layout principal, sidebar e rotas Vue
- `#13` README com instalacao e fluxo de trabalho
- `#15` Plano de testes

Objetivo da etapa:

- Todo mundo conseguir rodar o projeto.
- Banco estar desenhado.
- Frontend ter uma base de navegacao.
- Documentacao inicial existir.
- QA ja ter roteiro de testes.

### Etapa 2 - Criar A Base Funcional

Issues:

- `#3` Migrations e seeders iniciais
- `#4` Autenticacao e perfis de acesso
- `#5` API CRUD de cursos
- `#9` Telas de login, cadastro e recuperacao de senha
- `#10` Tela de listagem de cursos por area/eixo

Objetivo da etapa:

- Banco criar tabelas e dados de teste.
- Login comecar a funcionar.
- Cursos poderem ser cadastrados, consultados, editados e excluidos pela API.
- Frontend comecar a consumir dados reais.

### Etapa 3 - Completar Modulos Principais

Issues:

- `#6` API de gerenciamento de usuarios
- `#7` Endpoints para dashboard e relatorios
- `#11` Formulario de novo curso e edicao
- `#12` Dashboard com cards e graficos

Objetivo da etapa:

- Administrador conseguir gerenciar usuarios.
- Dashboard mostrar indicadores reais.
- Frontend permitir criar e editar cursos.
- Sistema ficar proximo do fluxo principal do prototipo.

### Etapa 4 - Fechamento E Validacao

Issues:

- `#14` Manual simples de uso do sistema
- `#16` Testar ambiente Docker e instalacao do zero
- Testes gerais de QA

Objetivo da etapa:

- Documentar como usar o sistema.
- Validar instalacao em uma maquina limpa.
- Registrar e corrigir bugs antes da entrega.
- Preparar o projeto para apresentacao final.

## Issues Planejadas

As issues do GitHub devem ser usadas como passo a passo. Cada issue deve conter:

- Objetivo claro.
- Responsavel sugerido.
- Passos para executar.
- Criterios de aceite.
- Dependencias, quando houver.

## Labels Sugeridas

- `backend`
- `frontend`
- `database`
- `documentation`
- `qa`
- `setup`
- `docker`
- `auth`
- `courses`
- `users`
- `dashboard`
- `priority-high`
- `priority-medium`
- `priority-low`
