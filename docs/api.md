# API SIPED

## Convenções e autenticação

As rotas ficam em `Back_SIPED/routes/api.php` e usam prefixo `/api`. Salvo as rotas explicitamente públicas descritas abaixo, os endpoints estão sob `auth:sanctum`, `usuario.ativo` e `throttle:api-usuario`. A SPA envia token Bearer obtido no login, `Accept: application/json` e, quando selecionado, o cabeçalho `X-SIPED-Ciclo-Id`.

```http
Authorization: Bearer <TOKEN_DE_TESTE>
Accept: application/json
```

O formato exato de payload e resposta é definido por cada controller, Form Request e Resource, quando houver. O endpoint deve ser consultado no código/teste correspondente antes de integrar um cliente novo; este inventário não substitui o contrato campo a campo.

## Rotas públicas

| Método | Caminho | Finalidade e proteção |
|---|---|---|
| `POST` | `/api/login` | Autentica usuário; usa `throttle:login`. |
| `POST` | `/api/integracoes/svt/visitas` | Recebe sincronização do SVT; token próprio e `throttle:svt`. |
| `GET` | `/api/integracoes/svt/situacao` | Retorna situação da integração; token próprio e `throttle:svt`. |

O contrato detalhado da integração SVT, variáveis, exemplos e erros está em [integracao-svt.md](integracao-svt.md). A integração responde `503` quando desativada, conforme implementação/documentação da integração.

## Sessão autenticada

| Método | Caminho | Finalidade |
|---|---|---|
| `POST` | `/api/logout` | Revoga a sessão/token corrente. |
| `GET` | `/api/user` | Retorna o usuário autenticado. |

## Autorização por perfil

As regras centrais estão em `Back_SIPED/config/permissoes.php`, mas rotas podem impor middleware de perfil específico:

| Operação | Perfis confirmados |
|---|---|
| Consultar usuários | Root e Administrador |
| Criar/editar/inativar/reativar usuários | Root |
| Consultar e alterar dados de negócio | Root, Administrador e Editor; regras específicas podem restringir módulos |
| Consultar dados e relatórios | Root, Administrador, Editor e Consultor |
| Importar dados | Root, Administrador e Editor |
| Consultar auditoria e restaurar excluídos | Root e Administrador |
| Consultar e alterar fluxogramas | Leitura para os perfis autorizados; testes confirmam Editor altera e Consultor não altera |

`401` representa ausência/invalidade de autenticação e `403` falta de autorização nos testes de API correspondentes. Validação e regras de negócio retornam códigos definidos pelo controller/request; `422` é usado, por exemplo, para payload/planilha inválidos. Limites de taxa podem responder `429`.

## Recursos REST

Para recursos registrados com `apiResource`, Laravel expõe `GET` de coleção e detalhe, `POST` de criação, `PUT/PATCH` de atualização e `DELETE` de remoção, exceto as exceções abaixo. Os campos, filtros, paginação e validações são específicos de cada controller e Form Request.

| Recurso | Caminho | Observação confirmada |
|---|---|---|
| Ciclos | `/api/ciclos`, `/api/portfolio-ciclos` | Há aliases para ciclos do portfólio e rotas de gerar próximo/marcar atual. |
| Usuários | `/api/usuarios` | Consulta para Root/Admin; mutações somente Root. |
| Cursos e planejamento | `/api/cursos`, `/api/plano-de-metas`, `/api/pcas`, `/api/resolucoes`, `/api/termos-referencia` | CRUD; resoluções e termos usam parâmetros nomeados próprios. |
| Ofertas e execução | `/api/curso-execucoes`, `/api/curso-por-eixos` | Há rota adicional para vincular execução a curso. |
| Processos | `/api/horas-pedagogicas`, `/api/visitas-tecnicas`, `/api/acoes-extensivas`, `/api/eventos`, `/api/jornadas-pedagogicas` | Recursos CRUD; PDF de jornada é endpoint separado. |
| Estruturas | `/api/regioes-administrativas`, `/api/unidades-oferta`, `/api/cped-equipes` | Regiões e unidades não expõem exclusão pelo `apiResource`; unidades têm rotas de nomes/opções. |

Rotas adicionais de módulos:

| Caminho(s) | Operações |
|---|---|
| `/api/eixos/resumo`, `/api/eixos/pendentes`, `/api/eixos/{eixo}/detalhes`, `/api/eixos/{eixo}/cursos` | Consultas de catálogo/eixo. |
| `/api/revisao-dados` e subrotas `cursos/{curso}/classificar`, `execucoes/{curso_execucao}/vincular` | Revisão e classificação de dados. |
| `/api/dashboard`, `/api/notificacoes`, `/api/notificacoes/marcar-lidas` | Indicadores e notificações. |
| `/api/relatorios`, `/api/relatorios/{tipo}/preview`, `/api/relatorios/{tipo}/pdf` | Catálogo, prévia e geração de relatório. PDF usa `throttle:pesado`. |
| `/api/importacoes`, `/api/importacoes/historico/...`, `/api/importacoes/{modulo}/preview`, `/api/importacoes/{modulo}/commit` | Catálogo, histórico, prévia e confirmação de importações. Preview/commit usam `throttle:pesado`. |
| `/api/documentos` e `/api/documentos/{documento}/...` | Consulta, upload, atualização, exclusão e download de anexo. Upload usa `throttle:pesado`. |
| `/api/lixeira`, `/api/lixeira/{modulo}/{id}/restaurar` | Consulta e restauração lógica; restauração restrita a Root/Admin. |
| `/api/cadastros`, `/api/cadastros/{cadastro}` | Consulta de auditoria restrita a Root/Admin. |
| `/api/kanban/quadros`, `/api/kanban/colunas/...`, `/api/kanban/cartoes/...` | Quadros, colunas e cartões, incluindo movimentação. |
| `/api/fluxogramas` e `/api/fluxogramas/{fluxograma}` | Consulta e CRUD de fluxogramas; acesso controlado por perfil. |
| `/api/sistemas-apoio`, `/api/sistemas-externos`, `/api/ferramentas`, `/api/organograma`, `/api/carometro` | Catálogos e consultas de apoio. |
| `/api/jornadas-pedagogicas/{id}/pdf` | Geração de PDF com `throttle:pesado`. |

## Exemplos

Consulta autenticada, com valores fictícios:

```http
GET /api/cursos?per_page=20&page=1
Authorization: Bearer <TOKEN_DE_TESTE>
Accept: application/json
```

Login usa `LoginRequest` e aceita `email` (obrigatório, formato e-mail) e `senha` (obrigatória, string). Resposta `200` contém `token` e `usuario` (id, nome, email, perfil, unidade e foto). Credenciais inválidas ou usuário inativo retornam `422` com erro de validação; excesso de tentativas retorna `429`.

Exemplo fictício:

```json
{
	"email": "pessoa@exemplo.invalid",
	"senha": "senha-ficticia"
}
```

Não inclua credenciais reais em exemplos, tickets ou logs. Os parâmetros de filtros e formato de retorno devem ser conferidos no controller/teste do recurso escolhido.

## Verificação do contrato

Antes de publicar integração cliente, consulte `routes/api.php`, controller, Form Request, model/resource e testes da rota. Não há especificação OpenAPI no repositório. As rotas da SPA servidas pelo Laravel não são endpoints de API.