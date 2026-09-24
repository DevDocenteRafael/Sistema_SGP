import { podeEditarDados } from './auth';

export const ENTIDADES_ADMINISTRATIVAS = [
  'usuarios',
  'cped',
  'ferramentas',
  'kanban',
  'fluxogramas',
  'organograma',
  'ciclos',
];

export function entidadeEhAdministrativa(chave) {
  return ENTIDADES_ADMINISTRATIVAS.includes(chave);
}

export function podeAdministrarEntidade(chave) {
  return podeEditarDados() && entidadeEhAdministrativa(chave);
}

export function origemDoRegistro(registro) {
  if (!registro) {
    return null;
  }

  if (registro.origem && typeof registro.origem === 'object') {
    return registro.origem;
  }

  return {
    source_type: registro.source_type || null,
    source_system: registro.source_system || null,
    external_id: registro.external_id || null,
    synced_at: registro.synced_at || null,
  };
}

export function rotuloOrigem(registro) {
  const origem = origemDoRegistro(registro);
  const tipo = origem?.source_type;
  if (tipo === 'integration') {
    return origem?.source_system || 'Integração externa';
  }
  if (tipo === 'local') {
    return 'Cadastro interno do SIPED';
  }
  if (tipo === 'seeder') {
    return 'Massa de homologação (seeder)';
  }
  return 'Origem não informada';
}

export function textoSincronizacao(registro) {
  const iso = origemDoRegistro(registro)?.synced_at;
  if (!iso) {
    return null;
  }

  const data = new Date(iso);
  if (Number.isNaN(data.getTime())) {
    return null;
  }

  return data.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
