/**
 * Perfis e permissões do front (espelha config/permissoes.php).
 *
 * Administrador — gerencia usuários e acesso total.
 * Editor — altera dados do portfólio (sem usuários).
 * Consultor — apenas consulta.
 */

export const PERFIS = {
  ADMINISTRADOR: 'Administrador',
  EDITOR: 'Editor',
  CONSULTOR: 'Consultor',
};

export const MENU_POR_PERFIL = {
  inicio: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  dashboard: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  relatorios: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  importacoes: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR],
  auditoria: [PERFIS.ADMINISTRADOR],
  cursos: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'ciclos-portfolio': [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'plano-de-metas': [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  pca: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'controle-de-resolucoes': [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'termos-de-referencia': [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  eixos: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'visitas-tecnicas': [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'horas-pedagogicas': [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'acoes-extensivas': [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  eventos: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'jornada-pedagogica': [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  ferramentas: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'sistemas-apoio': [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  cped: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  usuarios: [PERFIS.ADMINISTRADOR],
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
  localStorage.setItem('sgp_usuario', JSON.stringify(usuario));
  window.axios.defaults.headers.common.Authorization = `Bearer ${token}`;
  sessaoValida = true;
  validacaoEmAndamento = null;
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

export function isAdministrador() {
  return getPerfil() === PERFIS.ADMINISTRADOR;
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

export function podeGerenciarUsuarios() {
  return isAdministrador();
}

export function podeEditarDados() {
  const perfil = getPerfil();

  return perfil === PERFIS.ADMINISTRADOR || perfil === PERFIS.EDITOR;
}

export function podeConsultarDados() {
  return Boolean(getPerfil());
}

export function podeImportarDados() {
  const perfil = getPerfil();

  return perfil === PERFIS.ADMINISTRADOR || perfil === PERFIS.EDITOR;
}

export function podeConsultarAuditoria() {
  return isAdministrador();
}
