/**
 * Barra de rolagem horizontal flutuante para tabelas largas.
 *
 * Usa trilha + alça (thumb) com pointer events — não depende da barra nativa
 * do overlay (Windows/macOS), que em um container de 14px fica impossível de
 * arrastar. A tabela continua com overflow-x para trackpad/shift+roda.
 *
 * Seletores: .tabela-wrap / .rel-tabela-wrap / .imp-tabela-wrap
 */

const SELECTOR = '.tabela-wrap, .rel-tabela-wrap, .imp-tabela-wrap';
const enhanced = new WeakMap();
/** @type {Set<object>} */
const instances = new Set();

function estadoFromDom(viewport) {
  const root = viewport.parentElement;
  if (!root?.classList.contains('sgp-table-scroll')) return null;
  const bar = root.querySelector('.sgp-table-scroll__sticky-bar');
  const track = bar?.querySelector('.sgp-table-scroll__track');
  const thumb = bar?.querySelector('.sgp-table-scroll__thumb');
  if (!bar || !track || !thumb) return null;
  return { viewport, bar, track, thumb, root, maxScroll: 0, dragging: false };
}

function registrar(state) {
  if (!state?.viewport) return;
  enhanced.set(state.viewport, state);
  instances.add(state);
}

function atualizarThumb(state) {
  const { viewport, track, thumb } = state;
  if (!track || !thumb || !viewport) return;

  const maxScroll = state.maxScroll;
  const trackW = track.clientWidth || 1;
  const viewW = Math.max(viewport.clientWidth, 1);
  const contentW = Math.max(viewport.scrollWidth, viewW);
  const thumbW = Math.min(trackW, Math.max(36, (viewW / contentW) * trackW));
  const maxThumb = Math.max(trackW - thumbW, 0);
  const ratio = maxScroll <= 0 ? 0 : viewport.scrollLeft / maxScroll;
  const left = ratio * maxThumb;

  thumb.style.width = `${thumbW}px`;
  thumb.style.transform = `translateX(${left}px)`;
}

function atualizarPosicaoBarra(state) {
  const { viewport, bar } = state;
  if (!viewport?.isConnected || !bar?.isConnected) {
    instances.delete(state);
    return;
  }

  const maxScroll = Math.max(
    Math.ceil(viewport.scrollWidth) - Math.ceil(viewport.clientWidth),
    0,
  );
  state.maxScroll = maxScroll;

  if (maxScroll <= 4) {
    bar.hidden = true;
    bar.classList.remove('is-floating');
    bar.style.left = '';
    bar.style.width = '';
    bar.style.bottom = '';
    viewport.classList.remove('sgp-hscroll-native-hidden');
    return;
  }

  viewport.classList.add('sgp-hscroll-native-hidden');

  const rect = viewport.getBoundingClientRect();
  const vh = window.innerHeight || document.documentElement.clientHeight;
  const tabelaVisivel = rect.bottom > 64 && rect.top < vh - 20;
  const aindaTemConteudoAbaixo = rect.bottom > vh - 12;

  if (tabelaVisivel && aindaTemConteudoAbaixo && rect.height > 100) {
    bar.hidden = false;
    bar.classList.add('is-floating');
    const isNarrow = window.innerWidth <= 480;
    const left = isNarrow ? 0 : Math.max(rect.left, 0);
    const width = isNarrow
      ? window.innerWidth
      : Math.max(Math.min(rect.width, window.innerWidth - left), 80);
    bar.style.left = `${left}px`;
    bar.style.width = `${width}px`;
    bar.style.bottom = '0px';
  } else if (tabelaVisivel && !aindaTemConteudoAbaixo) {
    bar.hidden = false;
    bar.classList.remove('is-floating');
    bar.style.left = '';
    bar.style.width = '';
    bar.style.bottom = '';
  } else {
    bar.hidden = true;
    bar.classList.remove('is-floating');
    bar.style.left = '';
    bar.style.width = '';
    bar.style.bottom = '';
  }

  if (!state.dragging) {
    atualizarThumb(state);
  }

  bar.setAttribute(
    'aria-valuenow',
    String(maxScroll ? Math.round((viewport.scrollLeft / maxScroll) * 100) : 0),
  );
}

function aplicarScrollLeft(state, next) {
  const max = state.maxScroll;
  const value = Math.max(0, Math.min(max, next));
  state.viewport.scrollLeft = value;
  atualizarThumb(state);
}

function bindSync(state) {
  if (state.bound) return;
  state.bound = true;
  const { viewport, bar, track, thumb } = state;

  viewport.addEventListener(
    'scroll',
    () => {
      if (state.dragging) return;
      atualizarThumb(state);
      bar.setAttribute(
        'aria-valuenow',
        String(state.maxScroll ? Math.round((viewport.scrollLeft / state.maxScroll) * 100) : 0),
      );
    },
    { passive: true },
  );

  const scrollFromClientX = (clientX) => {
    const trackRect = track.getBoundingClientRect();
    const thumbW = thumb.offsetWidth || 36;
    const maxThumb = Math.max(trackRect.width - thumbW, 1);
    const x = clientX - trackRect.left - thumbW / 2;
    const ratio = Math.max(0, Math.min(1, x / maxThumb));
    aplicarScrollLeft(state, ratio * state.maxScroll);
  };

  const onPointerMove = (event) => {
    if (!state.dragging) return;
    event.preventDefault();
    const trackRect = track.getBoundingClientRect();
    const thumbW = thumb.offsetWidth || 36;
    const maxThumb = Math.max(trackRect.width - thumbW, 1);
    const x = event.clientX - trackRect.left - (state.dragOffset || thumbW / 2);
    const ratio = Math.max(0, Math.min(1, x / maxThumb));
    aplicarScrollLeft(state, ratio * state.maxScroll);
  };

  const onPointerUp = () => {
    if (!state.dragging) return;
    state.dragging = false;
    bar.classList.remove('is-dragging');
    window.removeEventListener('pointermove', onPointerMove);
    window.removeEventListener('pointerup', onPointerUp);
    window.removeEventListener('pointercancel', onPointerUp);
  };

  const startDrag = (event) => {
    if (event.button != null && event.button !== 0) return;
    event.preventDefault();
    event.stopPropagation();
    state.dragging = true;
    bar.classList.add('is-dragging');
    const thumbRect = thumb.getBoundingClientRect();
    state.dragOffset = event.clientX - thumbRect.left;
    try {
      if (event.pointerId != null) thumb.setPointerCapture(event.pointerId);
    } catch {
      /* Pointer sintético / captura indisponível */
    }
    window.addEventListener('pointermove', onPointerMove, { passive: false });
    window.addEventListener('pointerup', onPointerUp);
    window.addEventListener('pointercancel', onPointerUp);
  };

  thumb.addEventListener('pointerdown', startDrag);
  track.addEventListener('pointerdown', (event) => {
    if (event.target === thumb) return;
    scrollFromClientX(event.clientX);
    startDrag(event);
  });

  bar.addEventListener('keydown', (event) => {
    const step = 64;
    let next = viewport.scrollLeft;
    if (event.key === 'ArrowRight') next += step;
    else if (event.key === 'ArrowLeft') next -= step;
    else if (event.key === 'Home') next = 0;
    else if (event.key === 'End') next = state.maxScroll;
    else return;
    event.preventDefault();
    aplicarScrollLeft(state, next);
  });
}

function montarBarra(root) {
  const bar = document.createElement('div');
  bar.className = 'sgp-table-scroll__sticky-bar';
  bar.setAttribute('role', 'scrollbar');
  bar.setAttribute('aria-orientation', 'horizontal');
  bar.setAttribute('aria-label', 'Rolagem horizontal da tabela');
  bar.setAttribute('aria-valuemin', '0');
  bar.setAttribute('aria-valuemax', '100');
  bar.tabIndex = 0;
  bar.hidden = true;

  const track = document.createElement('div');
  track.className = 'sgp-table-scroll__track';
  const thumb = document.createElement('div');
  thumb.className = 'sgp-table-scroll__thumb';
  thumb.setAttribute('aria-hidden', 'true');
  track.appendChild(thumb);
  bar.appendChild(track);
  root.appendChild(bar);
  return { bar, track, thumb };
}

function enhance(viewport) {
  if (!(viewport instanceof HTMLElement)) return;

  let state = enhanced.get(viewport);
  if (state) {
    bindSync(state);
    atualizarPosicaoBarra(state);
    return;
  }

  let root;
  if (viewport.parentElement?.classList.contains('sgp-table-scroll')) {
    state = estadoFromDom(viewport);
    if (state) {
      registrar(state);
      bindSync(state);
      atualizarPosicaoBarra(state);
      return;
    }
    root = viewport.parentElement;
    root.querySelector('.sgp-table-scroll__sticky-bar')?.remove();
  } else {
    const parent = viewport.parentNode;
    if (!parent) return;
    root = document.createElement('div');
    root.className = 'sgp-table-scroll';
    parent.insertBefore(root, viewport);
    root.appendChild(viewport);
  }

  const { bar, track, thumb } = montarBarra(root);

  state = {
    viewport,
    bar,
    track,
    thumb,
    root,
    maxScroll: 0,
    dragging: false,
    bound: false,
  };
  registrar(state);
  bindSync(state);

  if (typeof ResizeObserver !== 'undefined') {
    const ro = new ResizeObserver(() => atualizarPosicaoBarra(state));
    ro.observe(viewport);
    if (viewport.firstElementChild) ro.observe(viewport.firstElementChild);
    ro.observe(bar);
  }

  if (typeof MutationObserver !== 'undefined') {
    new MutationObserver(() => atualizarPosicaoBarra(state)).observe(viewport, {
      childList: true,
      subtree: true,
    });
  }

  atualizarPosicaoBarra(state);
  requestAnimationFrame(() => atualizarPosicaoBarra(state));
  setTimeout(() => atualizarPosicaoBarra(state), 80);
  setTimeout(() => atualizarPosicaoBarra(state), 400);
}

function coletarViewports(raiz = document) {
  const lista = [];
  if (!raiz?.querySelectorAll) return lista;
  raiz.querySelectorAll(SELECTOR).forEach((el) => lista.push(el));
  return lista;
}

function atualizarTodas() {
  coletarViewports(document).forEach((el) => {
    if (!enhanced.has(el) && el.parentElement?.classList.contains('sgp-table-scroll')) {
      const state = estadoFromDom(el);
      if (state) {
        registrar(state);
        bindSync(state);
      }
    }
  });

  instances.forEach((state) => {
    if (state.dragging) return;
    atualizarPosicaoBarra(state);
  });
}

export function aplicarScrollHorizontalTabelas(raiz = document) {
  coletarViewports(raiz).forEach((el) => enhance(el));
  atualizarTodas();
}

let iniciado = false;

export function initScrollHorizontalTabelas() {
  if (typeof document === 'undefined') {
    return () => {};
  }

  iniciado = true;

  let timer = null;
  const aplicar = () => {
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => aplicarScrollHorizontalTabelas(document), 40);
  };

  aplicarScrollHorizontalTabelas(document);

  const mo = typeof MutationObserver !== 'undefined'
    ? new MutationObserver(() => aplicar())
    : null;

  if (mo) {
    mo.observe(document.body, { childList: true, subtree: true });
  }

  const onLayout = (event) => {
    const t = event.target;
    if (t instanceof Element && t.closest('.sgp-table-scroll__sticky-bar')) {
      return;
    }
    atualizarTodas();
  };

  window.addEventListener('resize', atualizarTodas, { passive: true });
  document.addEventListener('scroll', onLayout, { passive: true, capture: true });

  const bindMain = () => {
    const main = document.querySelector('.app-main');
    if (main && main.dataset.sgpHscrollBound !== '1') {
      main.dataset.sgpHscrollBound = '1';
      main.addEventListener('scroll', atualizarTodas, { passive: true });
    }
  };
  bindMain();
  setTimeout(bindMain, 300);
  setTimeout(() => aplicarScrollHorizontalTabelas(document), 800);
  window.addEventListener('load', () => aplicarScrollHorizontalTabelas(document));

  if (typeof window !== 'undefined') {
    window.__sgpRefreshTableScroll = () => aplicarScrollHorizontalTabelas(document);
  }

  return () => {
    iniciado = false;
    if (mo) mo.disconnect();
    if (timer) clearTimeout(timer);
  };
}

export function refreshTableScroll() {
  if (!iniciado) initScrollHorizontalTabelas();
  else aplicarScrollHorizontalTabelas(document);
}
