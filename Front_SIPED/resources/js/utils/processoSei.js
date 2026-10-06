/**
 * Links para SEI e SIG a partir da configuração (utils/sistemasExternos.js).
 *
 * SEI: valor que já é URL abre direto; com SEI_PROCESSO_URL configurada ({processo}),
 * abre o processo; sem ela, abre a página inicial do SEI.
 * SIG: só gera link direto com o padrão oficial (SIG_CURSO_URL com {codigo}).
 */
import { sistemasExternos } from './sistemasExternos.js';

export function hrefProcessoSei(valor, config = sistemasExternos) {
  const texto = String(valor || '').trim();
  if (!texto) {
    return null;
  }

  if (/^https?:\/\//i.test(texto)) {
    return texto;
  }

  const modelo = config?.sei?.processo_url;
  if (modelo && modelo.includes('{processo}')) {
    return modelo.replace('{processo}', encodeURIComponent(texto));
  }

  return config?.sei?.base_url || null;
}

/** true quando o link abre o próprio processo (e não só a página inicial do SEI). */
export function seiLinkDireto(valor, config = sistemasExternos) {
  const texto = String(valor || '').trim();
  return Boolean(texto) && (/^https?:\/\//i.test(texto) || Boolean(config?.sei?.processo_url));
}

export function hrefCursoSig(codigo, config = sistemasExternos) {
  const texto = String(codigo || '').trim();
  const modelo = config?.sig?.curso_url;
  if (!texto || !modelo || !modelo.includes('{codigo}')) {
    return null;
  }

  return modelo.replace('{codigo}', encodeURIComponent(texto));
}
