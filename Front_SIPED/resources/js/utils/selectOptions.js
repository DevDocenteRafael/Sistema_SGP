const SIGLAS = new Set([
  'API',
  'CEP',
  'CNPJ',
  'CPF',
  'CPED',
  'EAD',
  'IF',
  'IFAC',
  'IFAL',
  'IFAP',
  'IFAM',
  'IFB',
  'IFC',
  'IFCE',
  'IFBA',
  'IFES',
  'IFF',
  'IFFAR',
  'IFG',
  'IFGOIANO',
  'IFMA',
  'IFMG',
  'IFMS',
  'IFMT',
  'IFNMG',
  'IFPA',
  'IFPB',
  'IFPE',
  'IFPI',
  'IFPR',
  'IFRJ',
  'IFRN',
  'IFSC',
  'IFSP',
  'IFRS',
  'IFSUL',
  'IFSULDEMINAS',
  'IFTM',
  'IFTO',
  'LDB',
  'MEC',
  'PCA',
  'PDI',
  'PPC',
  'PRONATEC',
  'SEI',
  'SENAC',
  'SENAI',
  'SENAR',
  'SENAT',
  'SGP',
  'SIG',
]);

const PARTICULAS = new Set(['a', 'as', 'da', 'das', 'de', 'do', 'dos', 'e', 'o', 'os']);

function capitalizarPalavra(palavra, indice, manterMinuscula) {
  const chave = palavra.toLocaleUpperCase('pt-BR');
  if (SIGLAS.has(chave)) {
    return chave;
  }

  if (palavra !== palavra.toLocaleLowerCase('pt-BR')
    && palavra !== palavra.toLocaleUpperCase('pt-BR')) {
    return palavra;
  }

  const minuscula = palavra.toLocaleLowerCase('pt-BR');
  if (indice > 0 && (manterMinuscula || PARTICULAS.has(minuscula))) {
    return minuscula;
  }

  return minuscula.charAt(0).toLocaleUpperCase('pt-BR') + minuscula.slice(1);
}

export function formatSelectLabel(value) {
  let indicePalavra = 0;
  let minusculaAposParticula = false;
  return String(value ?? '').replace(/\p{L}[\p{L}\p{M}'’]*/gu, (palavra) => {
    const formatada = capitalizarPalavra(palavra, indicePalavra, minusculaAposParticula);
    minusculaAposParticula = PARTICULAS.has(palavra.toLocaleLowerCase('pt-BR'));
    indicePalavra += 1;
    return formatada;
  });
}

export function compareSelectLabels(a, b) {
  return a.localeCompare(b, 'pt-BR', { sensitivity: 'base', numeric: true });
}
