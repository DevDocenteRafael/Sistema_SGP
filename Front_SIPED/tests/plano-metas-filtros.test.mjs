import assert from 'node:assert/strict';
import {
  formatFilterOptionLabel,
  formatTableTypeLabel,
  sortYearsDescending,
} from '../resources/js/utils/selectOptions.js';

function test(nome, fn) {
  try {
    fn();
    console.log(`  ✓ ${nome}`);
    return true;
  } catch (error) {
    console.error(`  ✗ ${nome}`);
    console.error(`    ${error.message}`);
    return false;
  }
}

console.log('Plano de Metas — opções dos filtros\n');

let ok = 0;
let total = 0;

const run = (nome, fn) => {
  total += 1;
  if (test(nome, fn)) ok += 1;
};

run('ordena os anos do mais recente para o mais antigo sem alterar a lista original', () => {
  const anos = ['2024', '2027', '2025', '2026'];
  assert.deepEqual(sortYearsDescending(anos), ['2027', '2026', '2025', '2024']);
  assert.deepEqual(anos, ['2024', '2027', '2025', '2026']);
});

run('formata status e situações em caixa de frase', () => {
  assert.equal(formatFilterOptionLabel('EM ANÁLISE'), 'Em análise');
  assert.equal(formatFilterOptionLabel('CONCLUÍDO'), 'Concluído');
  assert.equal(formatFilterOptionLabel('PUBLICADO'), 'Publicado');
  assert.equal(formatFilterOptionLabel('PENDENTE'), 'Pendente');
});

run('formata tipos preservando a sigla oficial EAD', () => {
  assert.equal(formatFilterOptionLabel('PRESENCIAL'), 'Presencial');
  assert.equal(formatFilterOptionLabel('HÍBRIDO'), 'Híbrido');
  assert.equal(formatFilterOptionLabel('EAD'), 'EAD');
  assert.equal(formatFilterOptionLabel('QUALIFICAÇÃO'), 'Qualificação');
});

run('formata os tipos na tabela com somente a primeira letra maiúscula', () => {
  assert.equal(formatTableTypeLabel('PRESENCIAL'), 'Presencial');
  assert.equal(formatTableTypeLabel('HÍBRIDO'), 'Híbrido');
  assert.equal(formatTableTypeLabel('EAD'), 'Ead');
  assert.equal(formatTableTypeLabel('QUALIFICAÇÃO'), 'Qualificação');
});

run('formata status do registro na tabela com somente a primeira letra maiúscula', () => {
  assert.equal(formatTableTypeLabel('EM ANÁLISE'), 'Em análise');
  assert.equal(formatTableTypeLabel('CANCELADO'), 'Cancelado');
  assert.equal(formatTableTypeLabel('CONCLUÍDO'), 'Concluído');
  assert.equal(formatTableTypeLabel('EM ANDAMENTO'), 'Em andamento');
});

run('formata situação final na tabela com somente a primeira letra maiúscula', () => {
  assert.equal(formatTableTypeLabel('ENTREGUE'), 'Entregue');
  assert.equal(formatTableTypeLabel('PUBLICADO'), 'Publicado');
  assert.equal(formatTableTypeLabel('PENDENTE'), 'Pendente');
});

console.log(`\n${ok}/${total} testes passaram.`);
if (ok !== total) process.exitCode = 1;
