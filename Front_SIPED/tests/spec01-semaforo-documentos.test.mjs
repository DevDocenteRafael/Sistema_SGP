import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { ehPdf, rotaDoRegistro } from '../resources/js/utils/documentos.js';

// SPEC 01 V2: semáforo com exatamente três estados (Vigente / Atenção / Vencida).
function carregarMetodo(pagina, metodo, contexto = {}) {
  const source = readFileSync(new URL(`../resources/js/scripts/${pagina}.js`, import.meta.url), 'utf8');
  const match = source.match(new RegExp(`^( +)${metodo}\\([^)]*\\) \\{[\\s\\S]*?^\\1\\},`, 'm'));
  assert(match, `${pagina}.${metodo} não encontrado`);
  return vm.runInNewContext(`({${match[0]}})`, { ALERTA_PREVENTIVO_MESES: 6, Date, Number, String, ...contexto })[metodo];
}

const iso = (data) => data.toISOString().slice(0, 10);
const emDias = (dias) => {
  const data = new Date();
  data.setHours(12, 0, 0, 0);
  data.setDate(data.getDate() + dias);
  return iso(data);
};

let checks = 0;
const previsto = carregarMetodo('ControleDeResolucao', 'calcularStatusPrevisto');
assert.equal(previsto(emDias(-1)), 'vencida', 'ontem => vencida');
assert.equal(previsto(emDias(15)), 'atencao', 'faltam 15 dias => atenção (nunca "crítico")');
assert.equal(previsto(emDias(150)), 'atencao', 'dentro de 6 meses => atenção');
assert.equal(previsto(emDias(400)), 'vigente', 'fora da janela => vigente');
checks += 4;

for (const arquivo of ['ControleDeResolucao', 'TermosReferencia', 'Dashboard']) {
  const source = readFileSync(new URL(`../resources/js/scripts/${arquivo}.js`, import.meta.url), 'utf8');
  assert(!/['"]critico['"]|Crítico/.test(source), `${arquivo} não deve expor "Crítico" no semáforo`);
  if (arquivo !== 'Dashboard') {
    assert(!/concluida/.test(source), `${arquivo} não deve ter "Concluída" como estado de prazo`);
  }
  checks++;
}

const importacoes = readFileSync(new URL('../resources/js/pages/Importacoes.vue', import.meta.url), 'utf8');
assert(!importacoes.includes('revisao-dados'), 'Importações não deve ter botão/link de Revisão de Dados');
assert(importacoes.includes('.pdf'), 'Importações aceita PDF no input');
checks += 2;

assert.equal(ehPdf({ name: 'oficio.PDF', type: '' }), true);
assert.equal(ehPdf({ name: 'planilha.xlsx', type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }), false);
assert.deepEqual(rotaDoRegistro('cursos', 7, 3), { path: '/app/cursos', query: { curso_id: '7', ciclo_id: '3' } });
assert.deepEqual(rotaDoRegistro('eventos', 9), { path: '/app/eventos', query: { id: '9' } });
assert.equal(rotaDoRegistro('desconhecido', 1), null);
checks += 5;

console.log(`${checks} verificações passaram: semáforo de 3 estados, Importações sem Revisão de Dados, PDF como documento.`);
