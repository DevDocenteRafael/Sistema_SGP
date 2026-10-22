# Manual do usuário

## Acesso

Em desenvolvimento local, inicie o projeto pela raiz com `local-start.cmd` e abra `http://127.0.0.1:5173/login`. O script usa o banco configurado em `Back_SIPED/.env`, executa migrations e seeders e pode encerrar processos que já estejam ocupando as portas 8000 e 5173. Use-o somente em ambiente local controlado.

Entre com e-mail e senha fornecidos pela equipe responsável. A opção de lembrar credenciais não deve ser usada em computador compartilhado. Para encerrar a sessão, use a ação de saída do menu do usuário; o token da sessão é removido pelo front-end.

As contas listadas no README principal são dados de demonstração para desenvolvimento. Não as use para operar dados reais.

## Perfis

As permissões são definidas em `Back_SIPED/config/permissoes.php` e também aplicadas nas rotas e controllers.

| Perfil | Acesso confirmado |
|---|---|
| Root | Consulta e gerencia usuários; também possui as permissões do Administrador. |
| Administrador | Consulta usuários, consulta e altera dados, importa, consulta auditoria e restaura registros excluídos. |
| Editor | Consulta e altera dados e pode importar; não gerencia usuários nem consulta auditoria. |
| Consultor | Consulta dados e relatórios; não altera nem importa. |

Uma ação pode exigir autorização específica mesmo que a página esteja visível. Em caso de acesso negado, solicite a permissão à equipe administradora; não tente contornar o controle.

## Navegação e rotinas

- O menu reúne início, dashboard e relatórios, além dos módulos de portfólio e processos disponíveis ao perfil.
- O seletor de ciclo é usado pelas consultas periódicas. Confira o ciclo selecionado antes de cadastrar ou consultar registros, pois a API também aceita o ciclo no contexto da requisição.
- Módulos de dados incluem cursos, plano de metas, PCA, eixos, visitas técnicas, horas pedagógicas, ações extensivas, eventos, jornada pedagógica, resoluções e termos de referência.
- Use filtros e paginação disponíveis em cada listagem. Os filtros variam por módulo; consulte os campos apresentados na própria tela.
- Para importações, escolha o módulo, envie a planilha e revise a prévia antes de confirmar. A confirmação grava dados e pode gerar cópia de segurança/histórico. O acesso é restrito a Root, Administrador e Editor.
- Relatórios permitem pré-visualização e geração de PDF conforme o tipo disponível. A geração de PDF tem limite de requisições próprio.
- Auditoria e lixeira são restritas a Root e Administrador. A auditoria é de consulta; a lixeira oferece restauração conforme as regras do módulo.
- Documentos anexos são distintos das planilhas de importação. O módulo de documentos controla upload, vínculo, consulta e download de arquivos.
- Ferramentas incluem Kanban, fluxogramas, organograma e carômetro. A capacidade de alterar Kanban e fluxogramas depende do perfil.

## Cuidados com dados

- Confira ciclo, módulo e filtros antes de confirmar uma alteração ou importação.
- Não use dados de demonstração em homologação ou produção.
- Não carregue arquivos com dados pessoais ou institucionais fora dos fluxos autorizados.
- Se houver erro, preserve a mensagem e informe módulo, operação, horário e identificador do registro, sem compartilhar senha ou token.

Os textos e estados vazios específicos de cada tela não estão descritos aqui porque não foram validados em uma sessão autenticada completa.