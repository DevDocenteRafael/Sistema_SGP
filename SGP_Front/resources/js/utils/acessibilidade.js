/**
 * Preferências de acessibilidade globais do SGP.
 * Persistidas em localStorage e aplicadas em <html>.
 *
 * - Tema claro/escuro (data-theme)
 * - Alto contraste (data-contrast)
 * - Escala tipográfica em rem via font-size do root (NÃO zoom/scale)
 */

const STORAGE_KEY = 'sgp_acessibilidade';
const FONT_MIN = 0.75;
const FONT_MAX = 2;
const FONT_STEP = 0.25;
const FONT_DEFAULT = 1;

/** @type {{ theme: 'light'|'dark', highContrast: boolean, fontScale: number }} */
let estado = {
  theme: 'light',
  highContrast: false,
  fontScale: FONT_DEFAULT,
};

const ouvintes = new Set();

function clampEscala(valor) {
  const arredondado = Math.round(valor / FONT_STEP) * FONT_STEP;
  return Math.min(FONT_MAX, Math.max(FONT_MIN, Number(arredondado.toFixed(3))));
}

function lerStorage() {
  if (typeof localStorage === 'undefined') {
    return null;
  }

  try {
    const bruto = localStorage.getItem(STORAGE_KEY);
    if (!bruto) return null;
    return JSON.parse(bruto);
  } catch {
    return null;
  }
}

function salvarStorage() {
  if (typeof localStorage === 'undefined') {
    return;
  }

  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(estado));
  } catch {
    /* ignore quota / private mode */
  }
}

function notificar() {
  ouvintes.forEach((fn) => {
    try {
      fn({ ...estado });
    } catch {
      /* ignore */
    }
  });
}

function aplicarNoDom() {
  if (typeof document === 'undefined') {
    return;
  }

  const root = document.documentElement;
  root.setAttribute('data-theme', estado.theme);
  root.setAttribute('data-contrast', estado.highContrast ? 'high' : 'normal');
  root.style.setProperty('--sgp-font-scale', String(estado.fontScale));
  root.style.fontSize = `${16 * estado.fontScale}px`;
  root.style.colorScheme = estado.theme === 'dark' ? 'dark' : 'light';
  root.classList.toggle('sgp-font-large', estado.fontScale >= 1.25);
  root.classList.toggle('sgp-font-xlarge', estado.fontScale >= 1.5);
  root.classList.toggle('sgp-font-xxlarge', estado.fontScale >= 1.75);
  root.classList.toggle('sgp-high-contrast', estado.highContrast);
}

export function obterAcessibilidade() {
  return { ...estado };
}

export function initAcessibilidade() {
  const salvo = lerStorage();
  if (salvo && typeof salvo === 'object') {
    if (salvo.theme === 'dark' || salvo.theme === 'light') {
      estado.theme = salvo.theme;
    }
    if (typeof salvo.highContrast === 'boolean') {
      estado.highContrast = salvo.highContrast;
    } else if (salvo.contrast === 'high') {
      estado.highContrast = true;
    }
    const escala = Number(salvo.fontScale);
    if (Number.isFinite(escala)) {
      estado.fontScale = clampEscala(escala);
    }
  }
  aplicarNoDom();
  return obterAcessibilidade();
}

export function definirTema(theme) {
  estado.theme = theme === 'dark' ? 'dark' : 'light';
  salvarStorage();
  aplicarNoDom();
  notificar();
}

export function alternarTema() {
  definirTema(estado.theme === 'dark' ? 'light' : 'dark');
}

export function definirAltoContraste(ativo) {
  estado.highContrast = Boolean(ativo);
  salvarStorage();
  aplicarNoDom();
  notificar();
}

export function alternarAltoContraste() {
  definirAltoContraste(!estado.highContrast);
}

export function definirEscalaFonte(escala) {
  const valor = Number(escala);
  if (!Number.isFinite(valor)) return;
  estado.fontScale = clampEscala(valor);
  salvarStorage();
  aplicarNoDom();
  notificar();
}

export function aumentarFonte() {
  definirEscalaFonte(estado.fontScale + FONT_STEP);
}

export function diminuirFonte() {
  definirEscalaFonte(estado.fontScale - FONT_STEP);
}

export function resetarFonte() {
  definirEscalaFonte(FONT_DEFAULT);
}

export function podeAumentarFonte() {
  return estado.fontScale < FONT_MAX - 0.001;
}

export function podeDiminuirFonte() {
  return estado.fontScale > FONT_MIN + 0.001;
}

export function onAcessibilidadeChange(fn) {
  ouvintes.add(fn);
  return () => ouvintes.delete(fn);
}

export const ACESSIBILIDADE_LIMITES = {
  FONT_MIN,
  FONT_MAX,
  FONT_STEP,
  FONT_DEFAULT,
};
