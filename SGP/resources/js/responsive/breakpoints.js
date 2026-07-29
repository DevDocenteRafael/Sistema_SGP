/**
 * Breakpoints JS espelhando resources/css/responsive/breakpoints.css
 * Uso: import { BREAKPOINTS, isMobile } from '../responsive/breakpoints';
 */
export const BREAKPOINTS = {
  sm: 480,
  md: 768,
  lg: 1024,
  xl: 1280,
};

export function matchesMaxWidth(px) {
  if (typeof window === 'undefined' || !window.matchMedia) {
    return false;
  }

  return window.matchMedia(`(max-width: ${px}px)`).matches;
}

export function isMobile() {
  return matchesMaxWidth(BREAKPOINTS.md);
}

export function isTabletDown() {
  return matchesMaxWidth(BREAKPOINTS.lg);
}
