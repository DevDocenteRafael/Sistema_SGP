const fs = require('fs');
const path = require('path');
const js = fs.readFileSync(path.join(require('os').tmpdir(), 'sgp-proto.js'), 'utf8');

const markers = [
  'Cadastrar Novo Curso',
  'function _Ne',
  'segmento:',
  'turmas:',
  'instrutor:',
  'bolsa:',
  'comercial:',
  'pcn:',
  'pcr:',
  'descricao:',
  'dataInicio:',
  'unidades:',
  '"basico"',
  '"tecnico"',
  '"comercial"',
];

const i = js.indexOf('Cadastrar Novo Curso');
console.log('=== FORM AREA ===');
console.log(js.slice(i - 200, i + 15000));
