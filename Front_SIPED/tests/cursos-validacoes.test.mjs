import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function carregarMetodo(metodo) {
  const source = readFileSync(new URL('../resources/js/scripts/Cursos.js', import.meta.url), 'utf8');
  const match = source.match(new RegExp(`^( +)${metodo}\\([^)]*\\) \\{[\\s\\S]*?^\\1\\},`, 'm'));
  assert(match, `Cursos.${metodo} não encontrado`);
  return vm.runInNewContext(`({${match[0]}})`)[metodo];
}

const formatarMoeda = carregarMetodo('formatarMoedaBrasileira');
for (const [entrada, esperado] of [
  ['3', 'R$ 3,00'],
  ['10,50', 'R$ 10,50'],
  ['200', 'R$ 200,00'],
  ['1533', 'R$ 1.533,00'],
  ['1533,75', 'R$ 1.533,75'],
  ['R$ 1.533,75', 'R$ 1.533,75'],
]) {
  assert.equal(formatarMoeda(entrada), esperado, `Formatação de ${entrada}`);
}
assert.equal(formatarMoeda('R$ 2.405,00'), 'R$ 2.405,00');
assert.equal(formatarMoeda('letras'), '');

const valorPersistido = carregarMetodo('valorMonetarioPersistido');
assert.equal(valorPersistido.call({ formatarMoedaBrasileira: formatarMoeda }, 'R$ 1.533,75'), '1533.75');

const formatarValorInput = carregarMetodo('formatarValorInput');
const formatarValorContexto = {
  form: { valores: '' },
  errosCampo: {},
  valoresLegado: false,
  ultimoValorMonetarioValido: '',
  formatarMoedaBrasileira: formatarMoeda,
  $nextTick: (callback) => callback(),
};
const entradaMoeda = {
  target: {
    value: '1533,75',
    selectionStart: 7,
    setSelectionRange(inicio, fim) {
      this.selectionStart = inicio;
      this.selectionEnd = fim;
    },
  },
};
formatarValorInput.call(formatarValorContexto, entradaMoeda);
assert.equal(formatarValorContexto.form.valores, 'R$ 1.533,75');
assert.equal(entradaMoeda.target.value, 'R$ 1.533,75');
const moedaInvalida = {
  target: {
    value: 'R$ 1a',
    selectionStart: 5,
    setSelectionRange() {},
  },
};
formatarValorInput.call(formatarValorContexto, moedaInvalida);
assert.equal(moedaInvalida.target.value, 'R$ 1.533,75');
assert.match(formatarValorContexto.errosCampo.valores, /somente números/);

const prepararEntradaValor = carregarMetodo('prepararEntradaValor');
const digitarCentavos = (contexto, digito) => {
  const virgula = contexto.form.valores.indexOf(',');
  const entrada = contexto.form.valores.slice(0, virgula + 1)
    + contexto.form.valores.slice(virgula + 1, virgula + 2)
    + digito
    + contexto.form.valores.slice(virgula + 2);
  const target = {
    value: entrada,
    selectionStart: virgula + 3,
    setSelectionRange(inicio) {
      this.selectionStart = inicio;
    },
  };
  formatarValorInput.call(contexto, { target, inputType: 'insertText', data: digito });
};
const contextoCentavos = {
  form: { valores: 'R$ 153,00' },
  errosCampo: {},
  valoresLegado: false,
  ultimoValorMonetarioValido: 'R$ 153,00',
  digitosCentavosDigitados: 0,
  formatarMoedaBrasileira: formatarMoeda,
  $nextTick: (callback) => callback(),
};
const eventoSeparador = {
  key: ',',
  target: {
    value: contextoCentavos.form.valores,
    setSelectionRange(inicio) {
      this.selectionStart = inicio;
    },
  },
  preventDefault() {},
};
prepararEntradaValor.call(contextoCentavos, eventoSeparador);
contextoCentavos.form.valores = eventoSeparador.target.value;
contextoCentavos.ultimoValorMonetarioValido = eventoSeparador.target.value;
digitarCentavos(contextoCentavos, '7');
digitarCentavos(contextoCentavos, '5');
assert.equal(contextoCentavos.form.valores, 'R$ 153,75');
let terceiroCentavoBloqueado = false;
prepararEntradaValor.call(contextoCentavos, {
  key: '9',
  target: {
    value: contextoCentavos.form.valores,
    selectionStart: contextoCentavos.form.valores.length,
  },
  preventDefault() {
    terceiroCentavoBloqueado = true;
  },
});
assert.equal(terceiroCentavoBloqueado, true);

const padronizar = carregarMetodo('textoPadronizado');
assert.equal(padronizar('ATIVO'), 'Ativo');
assert.equal(padronizar('EM ANDAMENTO'), 'Em andamento');
const opcoesStatus = carregarMetodo('opcoesStatus');
assert.equal(
  JSON.stringify(opcoesStatus.call({ meta: { status: ['ATIVO', 'EM ANDAMENTO'] }, textoPadronizado: padronizar })),
  JSON.stringify(
  [{ value: 'ATIVO', label: 'Ativo' }, { value: 'EM ANDAMENTO', label: 'Em andamento' }],
  ),
);
const opcoesSimNao = carregarMetodo('opcoesSimNao');
assert.equal(
  JSON.stringify(opcoesSimNao.call({ meta: { sim_nao: ['SIM', 'NÃO'] }, textoPadronizado: padronizar })),
  JSON.stringify(
  [{ value: 'SIM', label: 'Sim' }, { value: 'NÃO', label: 'Não' }],
  ),
);

const valorSimNao = carregarMetodo('valorSimNao');
assert.equal(valorSimNao('sim'), 'SIM');
assert.equal(valorSimNao('NÃO'), 'NÃO');

const validarCodigo = carregarMetodo('validarCodigoInput');
for (const codigo of ['5645641651', '5645641651.4145445', '5645641651-4145445', '1.-2.3--4']) {
  const contexto = { form: {}, errosCampo: {} };
  const evento = { target: { value: codigo } };
  validarCodigo.call(contexto, evento, 'codigo_processo');
  assert.equal(contexto.form.codigo_processo, codigo);
  assert.equal(contexto.errosCampo.codigo_processo, '');
}
const contextoCodigo = { form: {}, errosCampo: {} };
const eventoCodigo = { target: { value: '12a/ 3' } };
validarCodigo.call(contextoCodigo, eventoCodigo, 'codigo_dn');
assert.equal(eventoCodigo.target.value, '123');
assert.match(contextoCodigo.errosCampo.codigo_dn, /somente números/);

const validarUltimaAtualizacao = carregarMetodo('validarUltimaAtualizacaoInput');
const contextoAtualizacao = { form: {}, errosCampo: {} };
const eventoAtualizacao = { target: { value: '1234567a' } };
validarUltimaAtualizacao.call(contextoAtualizacao, eventoAtualizacao);
assert.equal(eventoAtualizacao.target.value, '123456');
assert.match(contextoAtualizacao.errosCampo.ultima_revisao, /6 algarismos/);
console.log('Validações de códigos, última atualização, moeda e textos passaram.');
