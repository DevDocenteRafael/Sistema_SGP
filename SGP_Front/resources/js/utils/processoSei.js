/**
 * Link simples para processo SEI (fase 1).
 * Se o valor já for URL (http/https), usa direto.
 * Caso contrário abre a home pública do SEI-DF — não há URL institucional
 * de busca autenticada no projeto e não se inventam credenciais.
 */
export const SEI_BASE_URL = 'https://sei.df.gov.br/';

export function hrefProcessoSei(valor) {
  const texto = String(valor || '').trim();
  if (!texto) {
    return null;
  }

  if (/^https?:\/\//i.test(texto)) {
    return texto;
  }

  return SEI_BASE_URL;
}
