export const EIXOS_OFICIAIS = [
  'Gastronomia e Turismo',
  'Ambiente e Saúde',
  'Gestão e Moda',
  'Tecnologia e Economia Criativa',
  'Beleza e Cuidado Pessoal',
];

const EIXO_ALIASES = {
  saude: 'Ambiente e Saúde',
  'saude e seguranca': 'Ambiente e Saúde',
  gastronomia: 'Gastronomia e Turismo',
  'turismo e hospitalidade': 'Gastronomia e Turismo',
  'gestao e negocios': 'Gestão e Moda',
};

export function chaveEixo(valor) {
  return String(valor || '')
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, ' ');
}

export function eixoCanonico(valor) {
  const chave = chaveEixo(valor);
  if (!chave) {
    return '';
  }

  const oficial = EIXOS_OFICIAIS.find((eixo) => chaveEixo(eixo) === chave);
  if (oficial) {
    return oficial;
  }

  return EIXO_ALIASES[chave] || String(valor || '').trim();
}

export function eixosIguais(a, b) {
  const canonA = eixoCanonico(a);
  const canonB = eixoCanonico(b);
  return Boolean(canonA) && canonA === canonB;
}
