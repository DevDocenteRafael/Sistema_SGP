import assert from 'node:assert/strict';
import {
  somenteAlfanumericoProcesso,
  somenteNumeros,
  mascaraCpf,
  cpfValido,
  validarProcessoSei,
  validarInteiro,
  validarDecimal,
  validarCpf,
  validarOrdemDatas,
  combinarValidacoes,
  normalizarTextoMonetario,
} from '../resources/js/utils/validacao.js';

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

console.log('validacao.js — máscaras e validadores\n');

let ok = 0;
let total = 0;

const run = (nome, fn) => {
  total += 1;
  if (test(nome, fn)) ok += 1;
};

run('somenteAlfanumericoProcesso remove letras e símbolos inválidos', () => {
  assert.equal(somenteAlfanumericoProcesso('ABC2026.000001381-46@!'), '2026.000001381-46');
});

run('somenteNumeros mantém apenas dígitos', () => {
  assert.equal(somenteNumeros('12a3b4'), '1234');
});

run('mascaraCpf formata parcialmente', () => {
  assert.equal(mascaraCpf('52998224725'), '529.982.247-25');
});

run('cpfValido aceita CPF válido e rejeita inválido', () => {
  assert.equal(cpfValido('529.982.247-25'), true);
  assert.equal(cpfValido('111.111.111-11'), false);
});

run('validarCpf exige formato correto', () => {
  assert.match(validarCpf('111.111.111-11'), /inválido/i);
  assert.equal(validarCpf('529.982.247-25'), '');
});

run('validarProcessoSei rejeita caracteres inválidos e ausência de números', () => {
  assert.match(validarProcessoSei('SEI@123', { obrigatorio: true }), /apenas números/i);
  assert.match(validarProcessoSei('---', { obrigatorio: true }), /ao menos um número/i);
  assert.equal(validarProcessoSei('2026.000001381-46', { obrigatorio: true }), '');
});

run('validarInteiro rejeita letras', () => {
  assert.match(validarInteiro('12a3', { rotulo: 'Turmas' }), /apenas números/i);
  assert.equal(validarInteiro('120', { rotulo: 'CH', min: 1 }), '');
});

run('validarInteiro aceita matrícula longa por maxDigitos', () => {
  const matricula = '123456789012345';
  assert.equal(validarInteiro(matricula, { rotulo: 'Matrícula', min: 1, maxDigitos: 50 }), '');
  assert.match(
    validarInteiro(`${matricula}999999999999999999999999999999999999`, { rotulo: 'Matrícula', maxDigitos: 50 }),
    /máximo 50 dígitos/i,
  );
});

run('validarDecimal aceita valores com R$ e rejeita inválidos', () => {
  assert.equal(validarDecimal('R$ 4.800,00'), '');
  assert.equal(validarDecimal('-'), '');
  assert.match(validarDecimal('abc', { rotulo: 'Valor' }), /número válido/i);
});

run('normalizarTextoMonetario remove moeda e placeholders', () => {
  assert.equal(normalizarTextoMonetario('R$ 1.200,50'), '1.200,50');
  assert.equal(normalizarTextoMonetario('-'), '');
});

run('validarOrdemDatas rejeita fim anterior ao início', () => {
  assert.match(
    validarOrdemDatas('2026-08-10', '2026-08-01'),
    /posterior/i,
  );
});

run('combinarValidacoes retorna primeiro erro', () => {
  assert.equal(
    combinarValidacoes('', 'Primeiro erro', 'Segundo erro'),
    'Primeiro erro',
  );
});

console.log(`\n${ok}/${total} testes passaram`);

if (ok !== total) {
  process.exit(1);
}
