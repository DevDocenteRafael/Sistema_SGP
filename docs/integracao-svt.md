# Integração SIPED ← SVT (Sistema de Visitas Técnicas)

O SVT é o sistema onde as visitas técnicas são solicitadas e aprovadas
(Instrutor → Coordenação → CPAD → DEP → NULOG). O SIPED **recebe** as visitas do SVT
e as mostra na página **Visitas Técnicas**, que continua no menu para consulta.

O SVT é a fonte da verdade: visitas que vieram do SVT não podem ser editadas nem
excluídas no SIPED.

## Configuração no SIPED (`Back_SIPED/.env`)

| Variável | Para que serve |
|---|---|
| `VISITAS_TECNICAS_ORIGEM` | `local` (padrão): o SIPED ainda cadastra e importa visitas por planilha. `svt`: as visitas vêm só do SVT; no SIPED a página fica somente para consulta e a importação por planilha é bloqueada. |
| `SVT_INTEGRACAO_TOKEN` | Token compartilhado com o SVT. Sem ele, o endpoint responde `503` (integração desligada). Use um valor longo e aleatório. |
| `SVT_BASE_URL` | Página inicial do SVT. Mostra o atalho “SVT” em Sistemas de Apoio e na página de Visitas. |
| `SVT_VISITA_URL` | Link direto para uma visita, com `{id}` no lugar do id do SVT. Ex.: `https://svt.df.senac.br/visitas/{id}`. |

Depois de alterar o `.env`: `php artisan config:clear` (ou `optimize:clear`).

## Autenticação

Todas as chamadas levam o cabeçalho:

```
Authorization: Bearer <SVT_INTEGRACAO_TOKEN>
Accept: application/json
```

Limite: 60 chamadas por minuto por IP.

## Enviar visitas — `POST /api/integracoes/svt/visitas`

Envia uma ou mais visitas (até 500 por chamada). Cada visita é identificada pelo `id`
do SVT: se ainda não existe no SIPED, é criada; se já existe, é atualizada. Campos que
não forem enviados ficam como estão.

```json
{
  "visitas": [
    {
      "id": "SVT-2026-000123",
      "unidade": "Faculdade de Tecnologia e Inovação Senac-DF — Campus 712/912 Norte",
      "eixo": "Gestão e Moda",
      "processo_sei": "0001.123456/2026-01",
      "data_solicitacao": "2026-10-01",
      "data_visita_prevista": "2026-10-20",
      "prazo_limite": "2026-10-15",
      "status": "Em andamento",
      "etapa": "CPAD",
      "responsavel": "Coordenação de Gestão",
      "relatorio": "",
      "observacao": "Visita à empresa X"
    },
    { "id": "SVT-2026-000099", "excluida": true }
  ]
}
```

| Campo | Observação |
|---|---|
| `id` | Obrigatório. Id da visita no SVT (texto, até 120 caracteres). |
| `status` | Pendente, Em andamento, Realizada, Cancelada ou Atrasada (grafias equivalentes são ajustadas). Outros valores são guardados como vieram. |
| `etapa` | Etapa atual do fluxo no SVT (Instrutor, Coordenação, CPAD, DEP, NULOG). |
| datas | Formato `AAAA-MM-DD`. |
| `excluida` | `true` exclui a visita no SIPED (exclusão lógica, restaurável pela Auditoria). Reenviar a visita sem `excluida` a restaura. |

Resposta `200`:

```json
{
  "message": "Sincronização concluída.",
  "novos": 1, "atualizados": 0, "excluidos": 1, "ignorados": 0,
  "erros": [],
  "historico_id": 42
}
```

Itens com problema (por exemplo, sem `id`) são ignorados e listados em `erros`; os
demais são gravados normalmente. Cada chamada fica registrada no Histórico de
Importações do SIPED (tipo “integração”).

| Código | Quando |
|---|---|
| `401` | Token ausente ou errado. |
| `422` | Corpo inválido (sem `visitas`, ou mais de 500). |
| `429` | Mais de 60 chamadas por minuto. |
| `503` | `SVT_INTEGRACAO_TOKEN` não configurado no SIPED. |

## Conferir a integração — `GET /api/integracoes/svt/situacao`

```json
{
  "data": {
    "modo": "svt",
    "integracao_ativa": true,
    "svt_url": "https://svt.df.senac.br/",
    "ultima_sincronizacao": "2026-10-06T14:00:00.000000Z",
    "ultima_mensagem": "Sincronização SVT: 1 nova(s), 0 atualizada(s), 1 excluída(s), 0 ignorada(s).",
    "visitas_do_svt": 1
  }
}
```

## O que muda no SIPED

- A página **Visitas Técnicas** continua no menu.
- Visitas do SVT aparecem com o selo “SVT”, a etapa do fluxo e o link “Abrir no SVT”.
- Visitas do SVT não podem ser editadas nem excluídas no SIPED (`409`).
- Com `VISITAS_TECNICAS_ORIGEM=svt`: sem “Nova Visita”, sem edição/exclusão e sem
  importação de planilha de visitas.
- Notificações de prazo, Dashboard e Relatórios continuam usando as visitas
  normalmente, venham do SIPED ou do SVT.

## Pendências

- Definir com a equipe do SVT o formato final dos campos (unidade, eixo, status).
- Definir se o SVT envia tudo periodicamente ou só as alterações (o endpoint aceita os dois).
- Definir o destino das visitas cadastradas no SIPED antes da integração (migrar para o SVT ou manter como histórico).
