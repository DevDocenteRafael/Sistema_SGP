/**
 * Formata a quantidade do cabeçalho de tabela no padrão "1 registro" / "N registros".
 * @param {number|string|null|undefined} total
 * @returns {string}
 */
export function formatRegistrosCount(total) {
  const n = Number(total);
  const quantidade = Number.isFinite(n) && n > 0 ? Math.floor(n) : 0;
  return `${quantidade} registro${quantidade === 1 ? '' : 's'}`;
}

export default formatRegistrosCount;
