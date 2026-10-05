/** Utilitários de importação e documentos PDF (anexo controlado). */

/** Página de destino de cada módulo importável, para "Abrir registro". */
const ROTAS_IMPORTACAO = {
  cursos: { path: '/app/cursos', chave: 'curso_id' },
  'plano-de-metas': { path: '/app/plano-de-metas', chave: 'id' },
  pcas: { path: '/app/pca', chave: 'id' },
  eixos: { path: '/app/eixos', chave: null },
  'visitas-tecnicas': { path: '/app/visitas-tecnicas', chave: 'id' },
  'horas-pedagogicas': { path: '/app/horas-pedagogicas', chave: 'id' },
  'acoes-extensivas': { path: '/app/acoes-extensivas', chave: 'id' },
  eventos: { path: '/app/eventos', chave: 'id' },
  resolucoes: { path: '/app/controle-de-resolucoes', chave: 'id' },
  'termos-referencia': { path: '/app/termos-de-referencia', chave: 'id' },
};

/** Endpoints usados para escolher o registro ao vincular um PDF. */
export const MODULOS_DOCUMENTO = {
  eventos: { endpoint: '/api/eventos', titulo: (item) => item.nome, detalhe: (item) => [item.data, item.unidade].filter(Boolean).join(' · ') },
  resolucoes: { endpoint: '/api/resolucoes', titulo: (item) => item.numero, detalhe: (item) => item.curso_relacionado || '' },
  'termos-referencia': { endpoint: '/api/termos-referencia', titulo: (item) => item.nome, detalhe: (item) => item.processo_sei || '' },
};

export function rotaDoRegistro(modulo, id, cicloId = null) {
  const destino = ROTAS_IMPORTACAO[modulo];
  if (!destino) return null;

  const query = {};
  if (destino.chave && id) query[destino.chave] = String(id);
  if (cicloId) query.ciclo_id = String(cicloId);

  return { path: destino.path, query };
}

export function ehPdf(arquivo) {
  if (!arquivo) return false;
  return arquivo.type === 'application/pdf' || /\.pdf$/i.test(arquivo.name || '');
}

export function formatarTamanho(bytes) {
  const valor = Number(bytes) || 0;
  if (valor < 1024) return `${valor} B`;
  if (valor < 1024 * 1024) return `${(valor / 1024).toFixed(0)} KB`;
  return `${(valor / (1024 * 1024)).toFixed(1)} MB`;
}

export function formatarDataHora(iso) {
  if (!iso) return '—';
  const data = new Date(iso);
  if (Number.isNaN(data.getTime())) return '—';
  return data.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}

/** Baixa um arquivo autenticado (Bearer) como blob e abre o "salvar como". */
export async function baixarArquivo(url, nome) {
  const resposta = await window.axios.get(url, { responseType: 'blob' });
  const blob = new Blob([resposta.data], { type: resposta.headers?.['content-type'] || 'application/octet-stream' });
  const link = document.createElement('a');
  const href = window.URL.createObjectURL(blob);
  link.href = href;
  link.download = nome || 'arquivo';
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => window.URL.revokeObjectURL(href), 1000);
}
