import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { hrefCursoSig, hrefProcessoSei, seiLinkDireto } from '../resources/js/utils/processoSei.js';

// SPEC 04: SEI clicável e SIG só com padrão oficial, tudo vindo da configuração.
const semConfig = { sei: { base_url: null, processo_url: null }, sig: { base_url: null, curso_url: null } };
const basico = { sei: { base_url: 'https://sei.exemplo/sei/', processo_url: null }, sig: { base_url: 'https://sig.exemplo/', curso_url: null } };
const completo = {
  sei: { base_url: 'https://sei.exemplo/sei/', processo_url: 'https://sei.exemplo/p?n={processo}' },
  sig: { base_url: 'https://sig.exemplo/', curso_url: 'https://sig.exemplo/curso/{codigo}' },
};

let checks = 0;
assert.equal(hrefProcessoSei('0001.123/2026-01', semConfig), null, 'sem configuração não há link');
assert.equal(hrefProcessoSei('0001.123/2026-01', basico), 'https://sei.exemplo/sei/', 'sem modelo abre a página inicial do SEI');
assert.equal(seiLinkDireto('0001.123/2026-01', basico), false);
assert.equal(hrefProcessoSei('0001.123/2026-01', completo), 'https://sei.exemplo/p?n=0001.123%2F2026-01', 'com modelo abre o processo');
assert.equal(seiLinkDireto('0001.123/2026-01', completo), true);
assert.equal(hrefProcessoSei('https://sei.exemplo/x', semConfig), 'https://sei.exemplo/x', 'valor que já é URL abre direto');
assert.equal(hrefProcessoSei('', completo), null);
checks += 7;

assert.equal(hrefCursoSig('2437', basico), null, 'SIG sem padrão oficial não vira link');
assert.equal(hrefCursoSig('2437', completo), 'https://sig.exemplo/curso/2437');
assert.equal(hrefCursoSig('', completo), null);
checks += 3;

// Nenhuma URL institucional fixa nos componentes do front.
for (const arquivo of ['utils/processoSei.js', 'components/ciclo-vida/ProcessoSeiLink.vue', 'components/ciclo-vida/CodigoSig.vue']) {
  const fonte = readFileSync(new URL(`../resources/js/${arquivo}`, import.meta.url), 'utf8');
  assert(!/senac\.br|plataforma\.senac/.test(fonte), `${arquivo} não deve ter URL institucional fixa`);
  checks++;
}

console.log(`${checks} verificações passaram: links SEI/SIG configuráveis, sem URL fixa no front.`);
