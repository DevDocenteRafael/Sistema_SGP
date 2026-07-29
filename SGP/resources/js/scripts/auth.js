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
  cursos: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'plano-de-metas': [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  pca: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  eixos: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'visitas-tecnicas': [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'horas-pedagogicas': [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  'acoes-extensivas': [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  eventos: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  ferramentas: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  cped: [PERFIS.ADMINISTRADOR, PERFIS.EDITOR, PERFIS.CONSULTOR],
  usuarios: [PERFIS.ADMINISTRADOR],
};

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
