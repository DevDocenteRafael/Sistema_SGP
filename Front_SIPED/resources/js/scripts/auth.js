/**
 * Perfis e permissões do front (espelha config/permissoes.php).
 *
 * Root — dono do sistema: único que cadastra, edita, inativa e reativa usuários.
 * Administrador — acesso a tudo (dados, auditoria e restauração); só consulta usuários.
 * Editor — altera dados do portfólio (sem usuários).
 * Consultor — apenas consulta.
 */

export const PERFIS = {
  ROOT: 'Root',
  ADMINISTRADOR: 'Administrador',
  EDITOR: 'Editor',
  CONSULTOR: 'Consultor',
};

export const MENU_POR_PERFIL = {
  inicio: [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  dashboard: [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  relatorios: [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  importacoes: [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR],
  auditoria: [PERFIS.ROOT, PERFIS.ADMINISTRADOR],
  cursos: [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  ciclos: [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'ciclos-portfolio': [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'plano-de-metas': [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  pca: [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'controle-de-resolucoes': [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'termos-de-referencia': [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  eixos: [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'visitas-tecnicas': [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'horas-pedagogicas': [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'acoes-extensivas': [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  eventos: [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'jornada-pedagogica': [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  ferramentas: [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'sistemas-apoio': [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  unidades: [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'estruturas-institucionais': [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  cped: [PERFIS.ROOT, PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  usuarios: [PERFIS.ROOT, PERFIS.ADMINISTRADOR],
};

/** null = ainda não validou; true/false = resultado da última checagem */
let sessaoValida = null;
let validacaoEmAndamento = null;

export function getUsuario() {
  const raw = localStorage.getItem('sgp_usuario');

  if (!raw) {
    return null;
  }

  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

export function getPerfil() {
  return getUsuario()?.perfil ?? null;
}

export function clearSessao() {
  localStorage.removeItem('sgp_token');
  localStorage.removeItem('sgp_usuario');
  delete window.axios?.defaults?.headers?.common?.Authorization;
  sessaoValida = false;
  validacaoEmAndamento = null;
}

export function marcarSessao(token, usuario) {
  localStorage.setItem('sgp_token', token);
  localStorage.setItem('sgp_usuario', JSON.stringify({
    id: usuario.id,
    nome: usuario.nome,
    email: usuario.email,
    perfil: usuario.perfil,
    unidade: usuario.unidade ?? null,
    foto: usuario.foto ?? null,
  }));
  window.axios.defaults.headers.common.Authorization = `Bearer ${token}`;
  sessaoValida = true;
  validacaoEmAndamento = null;
}

/** Atualiza campos do usuário logado no localStorage (ex.: foto). */
export function atualizarUsuarioSessao(parcial) {
  const atual = getUsuario();
  if (!atual) {
    return;
  }

  localStorage.setItem('sgp_usuario', JSON.stringify({
    ...atual,
    ...parcial,
  }));
}

/**
 * Confirma se o token local ainda é válido no backend.
 * Evita abrir /app com token expirado e só depois cair no login.
 */
export async function garantirSessao() {
  const token = localStorage.getItem('sgp_token');

  if (!token) {
    sessaoValida = false;
    return false;
  }

  if (sessaoValida === true) {
    return true;
  }

  if (validacaoEmAndamento) {
    return validacaoEmAndamento;
  }

  window.axios.defaults.headers.common.Authorization = `Bearer ${token}`;

  validacaoEmAndamento = window.axios
    .get('/api/user', { skipAuthRedirect: true })
    .then(({ data }) => {
      const usuario = data.usuario ?? data;

      if (!usuario?.perfil) {
        clearSessao();
        return false;
      }

      localStorage.setItem('sgp_usuario', JSON.stringify({
        id: usuario.id,
        nome: usuario.nome,
        email: usuario.email,
        perfil: usuario.perfil,
        unidade: usuario.unidade ?? null,
        foto: usuario.foto ?? null,
      }));
      sessaoValida = true;
      return true;
    })
    .catch(() => {
      clearSessao();
      return false;
    })
    .finally(() => {
      validacaoEmAndamento = null;
    });

  return validacaoEmAndamento;
}

export function isRoot() {
  return getPerfil() === PERFIS.ROOT;
}

export function isAdministrador() {
  return getPerfil() === PERFIS.ADMINISTRADOR;
}

/** Root ou Administrador. */
export function temPerfilAdministrativo() {
  return isRoot() || isAdministrador();
}

export function isEditor() {
  return getPerfil() === PERFIS.EDITOR;
}

export function isConsultor() {
  return getPerfil() === PERFIS.CONSULTOR;
}

export function podeAcessarMenu(rota) {
  const perfil = getPerfil();
  const permitidos = MENU_POR_PERFIL[rota] ?? [];

  return perfil && permitidos.includes(perfil);
}

/** Só o Root cadastra, edita, inativa e reativa usuários; o Administrador só consulta. */
export function podeGerenciarUsuarios() {
  return isRoot();
}

export function podeEditarDados() {
  const perfil = getPerfil();

  return perfil === PERFIS.ROOT || perfil === PERFIS.ADMINISTRADOR || perfil === PERFIS.EDITOR;
}

export function podeConsultarDados() {
  return Boolean(getPerfil());
}

export function podeImportarDados() {
  const perfil = getPerfil();

  return perfil === PERFIS.ROOT || perfil === PERFIS.ADMINISTRADOR || perfil === PERFIS.EDITOR;
}

export function podeConsultarAuditoria() {
  return temPerfilAdministrativo();
}

export function podeRestaurarRegistros() {
  return temPerfilAdministrativo();
}
