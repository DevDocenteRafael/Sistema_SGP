# Integração SIPED ← SVT (Sistema de Visitas Técnicas)

O SVT é o sistema onde as visitas técnicas são solicitadas e aprovadas. Fluxo definido na
ATA de 30/09/2026: Instrutor → Núcleo Pedagógico da unidade → Coordenação/área responsável
na DEP/CPED → Direção Pedagógica → NULOG (transporte) → relatório pós-visita. O SIPED **recebe** as visitas do SVT
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
      "status": "Aprovada",
      "etapa": "NULOG",
      "responsavel": "Coordenação de Gestão (DEP/CPED)",
      "relatorio": "",
      "observacao": "Visita à empresa X",

      "curso": "Técnico em Administração",
      "tipo_curso": "Curso Técnico",
      "turma": "ADM-2026-01",
      "instrutor": "Nome do instrutor",
      "local_visita": "Empresa X — SIA Trecho 3",

      "visitas_utilizadas": 5,
      "visitas_limite": 4,
      "justificativa_excedente": "Atividade do projeto integrador",
      "motivo_recusa": null,

      "decisoes": [
        { "etapa": "Núcleo Pedagógico da unidade", "decisao": "aprovada", "responsavel": "Fulana", "data": "2026-10-02", "motivo": null },
        { "etapa": "Direção Pedagógica", "decisao": "aprovada", "responsavel": "Beltrano", "data": "2026-10-05T10:00:00-03:00" }
      ],
      "transporte": {
        "tipo": "Micro-ônibus", "veiculo": "Micro-ônibus 24 lugares", "placa": "ABC1D23",
        "data_hora": "2026-10-20T07:30:00-03:00", "motorista": "Nome do motorista", "observacao": "Saída da unidade"
      },
      "relatorio_url": "https://svt.df.senac.br/visitas/SVT-2026-000123/relatorio",
      "relatorio_arquivo_nome": "relatorio-visita.pdf"
    },
    { "id": "SVT-2026-000099", "excluida": true }
  ]
}
```

| Campo | Observação |
|---|---|
| `id` | Obrigatório. Id da visita no SVT (texto, até 120 caracteres). |
| `status` | Pendente, Em andamento, Aprovada, Recusada, Realizada, Cancelada ou Atrasada (grafias equivalentes são ajustadas). Outros valores são guardados como vieram. Visitas Recusadas, Realizadas ou Canceladas não geram alerta de prazo. |
| `etapa` | Etapa atual do fluxo: Instrutor, Núcleo Pedagógico da unidade, Coordenação/área DEP/CPED, Direção Pedagógica ou NULOG. |
| `responsavel` | Responsável na DEP/CPED atribuído pelo eixo/curso. |
| `curso`, `tipo_curso`, `turma`, `instrutor`, `local_visita` | Dados da solicitação (ATA, item 5). `turma` (com `curso`) monta o histórico da turma no SIPED. |
| `visitas_utilizadas`, `visitas_limite` | Contagem da turma e limite do tipo de curso (referência da ATA: Técnico 4, Qualificação até 2 conforme a carga horária, Aperfeiçoamento 1, Aprendizagem 4). O SVT aplica a regra; o SIPED só mostra e destaca quando passa do limite. |
| `justificativa_excedente` | Justificativa quando a turma passa do limite (a solicitação não é bloqueada). |
| `motivo_recusa` | Motivo quando alguma etapa recusa. |
| `decisoes` | Lista com a decisão de cada etapa: `etapa`, `decisao` (aprovada, recusada, encaminhada…), `responsavel`, `motivo`, `data`. Enviar a lista completa a cada atualização (substitui a anterior). |
| `transporte` | Definido pelo NULOG: `tipo`, `veiculo`, `placa`, `data_hora`, `motorista`, `observacao`. |
| `relatorio_url`, `relatorio_arquivo_nome` | Link (http/https) para o relatório pós-visita em Word ou PDF guardado no SVT. Links que não sejam http/https são descartados. |
| datas | `data_solicitacao`, `data_visita_prevista` (data pretendida) e `prazo_limite` no formato `AAAA-MM-DD`. |
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
- A tabela mostra curso, turma e local. O detalhe da visita mostra a solicitação, o limite
  de visitas da turma (com destaque quando passa do limite e a justificativa), as decisões
  de cada etapa, o transporte do NULOG, o relatório pós-visita e o histórico da turma
  (outras visitas da mesma turma/curso, com datas, locais, situação e relatórios).
- A busca e o relatório de Visitas Técnicas incluem curso, turma, instrutor e local.
- Visitas do SVT não podem ser editadas nem excluídas no SIPED (`409`).
- Com `VISITAS_TECNICAS_ORIGEM=svt`: sem “Nova Visita”, sem edição/exclusão e sem
  importação de planilha de visitas.
- Notificações de prazo, Dashboard e Relatórios continuam usando as visitas
  normalmente, venham do SIPED ou do SVT.

## Pendências

- Regra completa de limite por curso/carga horária (ATA: “a regra completa ainda será fornecida”).
- Integração do SVT com o SIG e o login educacional (decisão da ATA; é do SVT, não do SIPED).
- Definir com a equipe do SVT o formato final dos campos (unidade, eixo, status).
- Definir se o SVT envia tudo periodicamente ou só as alterações (o endpoint aceita os dois).
- Definir o destino das visitas cadastradas no SIPED antes da integração (migrar para o SVT ou manter como histórico).
