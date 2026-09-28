const ROTAS_POR_CAMINHO = {
  '/app/termos-de-referencia': 'termos-de-referencia',
  '/app/controle-de-resolucoes': 'controle-de-resolucoes',
  '/app/visitas-tecnicas': 'visitas-tecnicas',
};

const ROTAS_POR_MODULO = {
  'termos-referencia': 'termos-de-referencia',
  resolucoes: 'controle-de-resolucoes',
  'visitas-tecnicas': 'visitas-tecnicas',
};

export function idDaNotificacao(route) {
  const bruto = route?.query?.alerta ?? route?.query?.id;
  if (bruto == null || bruto === '') {
    return '';
  }

  const id = String(Array.isArray(bruto) ? bruto[0] : bruto).trim();

  return /^\d+$/.test(id) ? id : '';
}

export function destinoDaNotificacao(item) {
  const registroId = item?.registro_id ?? item?.registroId ?? item?.id;
  if (registroId == null || registroId === '') {
    return null;
  }

  const nome = ROTAS_POR_CAMINHO[item.rota] || ROTAS_POR_MODULO[item.modulo];
  if (!nome) {
    return null;
  }

  return {
    name: nome,
    query: { alerta: String(registroId) },
  };
}

export function limparQueryNotificacao(vm) {
  if (!idDaNotificacao(vm.$route)) {
    return false;
  }

  const query = { ...vm.$route.query };
  delete query.alerta;
  delete query.id;
  vm.$router.replace({ name: vm.$route.name, query });

  return true;
}

export function registroDaResposta(data, chaves = []) {
  for (const chave of chaves) {
    if (data?.[chave] && !Array.isArray(data[chave])) {
      return data[chave];
    }
  }

  if (data?.data && !Array.isArray(data.data)) {
    return data.data;
  }

  return null;
}

export async function buscarRegistroDoAlerta(endpoint, id, chavesResposta = []) {
  const { data } = await window.axios.get(`${endpoint}/${id}`);
  return registroDaResposta(data, chavesResposta);
}

export function metaListaUnitaria(meta, quantidade) {
  return {
    ...meta,
    total: quantidade,
    current_page: 1,
    last_page: 1,
    from: quantidade ? 1 : 0,
    to: quantidade,
  };
}
