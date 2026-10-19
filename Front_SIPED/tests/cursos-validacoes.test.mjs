import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const script = readFileSync(new URL('../resources/js/scripts/Cursos.js', import.meta.url), 'utf8');
const page = readFileSync(new URL('../resources/js/pages/Cursos.vue', import.meta.url), 'utf8');
const estilos = readFileSync(new URL('../resources/css/Cursos.css', import.meta.url), 'utf8');
const estilosResponsivos = readFileSync(new URL('../resources/css/responsive/pages.css', import.meta.url), 'utf8');

function carregarMetodo(metodo) {
  const match = script.match(new RegExp(`^( +)${metodo}\\([^)]*\\) \\{[\\s\\S]*?^\\1\\},`, 'm'));
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
  ['1.000.000,00', 'R$ 1.000.000,00'],
  ['1.000.000.000,00', 'R$ 1.000.000.000,00'],
  ['9.999.999.999,99', 'R$ 9.999.999.999,99'],
  ['1000000000.00', 'R$ 1.000.000.000,00'],
]) {
  assert.equal(formatarMoeda.call({
    normalizarValorMonetario: carregarMetodo('normalizarValorMonetario'),
    formatarValorMonetario: carregarMetodo('formatarValorMonetario'),
  }, entrada), esperado, `Formatação de ${entrada}`);
}

const normalizarValor = carregarMetodo('normalizarValorMonetario');
assert.equal(normalizarValor('9.999.999.999,99'), '9999999999.99');
assert.equal(normalizarValor('1.000.000.000,00'), '1000000000.00');
assert.equal(normalizarValor('1.533,'), '');
assert.equal(normalizarValor('1.533,999'), '');
assert.equal(normalizarValor('10.000.000.000.000,00'), '');

const valorPersistido = carregarMetodo('valorMonetarioPersistido');
assert.equal(valorPersistido.call({ normalizarValorMonetario: normalizarValor }, '9.999.999.999,99'), '9999999999.99');

const editarValor = carregarMetodo('aoEditarValor');
const finalizarValor = carregarMetodo('finalizarEdicaoValor');
const contextoValor = {
  form: { valores: 'R$ 1.533,75' },
  valorEmEdicao: '1.533,75',
  valoresLegado: false,
  errosCampo: { valores: '' },
  normalizarValorMonetario: normalizarValor,
  formatarValorMonetario: carregarMetodo('formatarValorMonetario'),
  $nextTick: (callback) => callback(),
};
const editarTexto = (valor) => {
  const target = {
    value: valor,
    selectionStart: valor.length,
    setSelectionRange(inicio) {
      this.selectionStart = inicio;
    },
  };
  editarValor.call(contextoValor, { target });
  return target.value;
};
assert.equal(editarTexto('1.533,'), '1.533,');
assert.equal(contextoValor.errosCampo.valores, '', 'valor incompleto não gera erro enquanto está em edição');
assert.equal(finalizarValor.call(contextoValor), false, 'valor incompleto é rejeitado no blur');
assert.match(contextoValor.errosCampo.valores, /valor monetário brasileiro/);
assert.equal(editarTexto('1.533,50'), '1.533,50');
assert.equal(contextoValor.errosCampo.valores, '');
assert.equal(finalizarValor.call(contextoValor), true);
assert.equal(contextoValor.form.valores, 'R$ 1.533,50');
const moedaComPrefixo = {
  value: 'R$ 9.999.999.999,99',
  selectionStart: 19,
  setSelectionRange(inicio) {
    this.selectionStart = inicio;
  },
};
editarValor.call(contextoValor, { target: moedaComPrefixo });
assert.equal(contextoValor.valorEmEdicao, '9.999.999.999,99');
assert.equal(contextoValor.errosCampo.valores, '');

const edicaoLegada = {
  form: { valores: 'valor antigo' },
  valorEmEdicao: 'valor antigo',
  valoresLegado: true,
  errosCampo: { valores: '' },
  $nextTick: (callback) => callback(),
};
editarValor.call(edicaoLegada, {
  target: {
    value: '5x',
    selectionStart: 2,
    setSelectionRange(inicio) {
      this.selectionStart = inicio;
    },
  },
});
assert.equal(edicaoLegada.valorEmEdicao, '5');
assert.equal(edicaoLegada.valoresLegado, false, 'editar um legado com caracteres incompatíveis não preserva o valor anterior');

const valorComBilhoes = {
  form: { valores: '' },
  valorEmEdicao: '9.999.999.999,99',
  valoresLegado: false,
  errosCampo: {},
  normalizarValorMonetario: normalizarValor,
  formatarValorMonetario: carregarMetodo('formatarValorMonetario'),
};
assert.equal(finalizarValor.call(valorComBilhoes), true);
assert.equal(valorComBilhoes.form.valores, 'R$ 9.999.999.999,99');

const validarIdentificacao = carregarMetodo('validarIdentificacaoInput');
const identificacaoContext = { form: {}, errosCampo: {} };
for (const identificacao of ['1234', '12345', '001234']) {
  const eventoIdentificacao = { target: { value: identificacao } };
  validarIdentificacao.call(identificacaoContext, eventoIdentificacao);
  assert.equal(identificacaoContext.form.identificacao, identificacao);
  assert.equal(identificacaoContext.errosCampo.identificacao, '', `${identificacao} deve ser válido`);
}
const identificacaoCurta = { target: { value: '123' } };
validarIdentificacao.call(identificacaoContext, identificacaoCurta);
assert.equal(identificacaoContext.form.identificacao, '123');
assert.match(identificacaoContext.errosCampo.identificacao, /4 a 6 algarismos/);
const identificacaoInvalida = { target: { value: '12345a7' } };
validarIdentificacao.call(identificacaoContext, identificacaoInvalida);
assert.equal(identificacaoInvalida.target.value, '123457');
assert.match(identificacaoContext.errosCampo.identificacao, /somente números/);
const identificacaoLonga = { target: { value: '1234567' } };
validarIdentificacao.call(identificacaoContext, identificacaoLonga);
assert.equal(identificacaoLonga.target.value, '123456');
assert.match(identificacaoContext.errosCampo.identificacao, /somente números/);

const alternarTodas = carregarMetodo('alternarTodasEstruturas');
const alternarUnidade = carregarMetodo('toggleUnidade');
const definirUnidade = carregarMetodo('definirUnidade');
const todasSelecionadas = carregarMetodo('todasEstruturasSelecionadas');
const algumasSelecionadas = carregarMetodo('algumasEstruturasSelecionadas');
const resumoSelecionadas = carregarMetodo('resumoEstruturasSelecionadas');
const selecaoEstruturas = {
  unidades: ['Unidade A', 'Unidade B', 'Unidade C'],
  form: { unidades_oferta: [], unidade: '' },
};
Object.defineProperty(selecaoEstruturas, 'unidadesSelecionadasResumo', {
  get() {
    return [...new Set(this.form.unidades_oferta)];
  },
});
Object.defineProperty(selecaoEstruturas, 'todasEstruturasSelecionadas', {
  get() {
    return this.unidades.length > 0
      && this.unidades.every((nome) => this.form.unidades_oferta.includes(nome));
  },
});
assert.equal(resumoSelecionadas.call(selecaoEstruturas), '');
selecaoEstruturas.form.unidades_oferta = ['Unidade B', 'Unidade B'];
assert.equal(resumoSelecionadas.call(selecaoEstruturas), 'Unidade B', 'o resumo não conta estruturas duplicadas');
selecaoEstruturas.form.unidades_oferta = [];
alternarTodas.call(selecaoEstruturas);
assert.deepEqual(Array.from(selecaoEstruturas.form.unidades_oferta), ['Unidade A', 'Unidade B', 'Unidade C']);
assert.equal(todasSelecionadas.call(selecaoEstruturas), true);
assert.equal(resumoSelecionadas.call(selecaoEstruturas), 'Todas as estruturas selecionadas');
alternarUnidade.call(selecaoEstruturas, 'Unidade B');
assert.equal(todasSelecionadas.call(selecaoEstruturas), false);
assert.equal(algumasSelecionadas.call(selecaoEstruturas), true);
assert.equal(resumoSelecionadas.call(selecaoEstruturas), '2 estruturas selecionadas');
alternarUnidade.call(selecaoEstruturas, 'Unidade B');
assert.equal(todasSelecionadas.call(selecaoEstruturas), true);
alternarTodas.call(selecaoEstruturas);
assert.deepEqual(Array.from(selecaoEstruturas.form.unidades_oferta), []);
definirUnidade.call(selecaoEstruturas, 'Unidade B', true);
definirUnidade.call(selecaoEstruturas, 'Unidade B', true);
assert.deepEqual(Array.from(selecaoEstruturas.form.unidades_oferta), ['Unidade B'], 'checkboxes mantêm a seleção sem duplicar estruturas');
assert.equal(resumoSelecionadas.call(selecaoEstruturas), 'Unidade B', 'uma seleção mostra o nome da estrutura');
definirUnidade.call(selecaoEstruturas, 'Unidade B', false);
assert.deepEqual(Array.from(selecaoEstruturas.form.unidades_oferta), []);

const gruposOferta = carregarMetodo('gruposOferta').call({
  unidades: ['Unidade A', 'Unidade B', 'Faculdade C'],
  unidadesOpcoes: [
    {
      id: 1,
      nome: 'Região Central',
      grupos: [
        { tipo: 'unidades', label: 'Unidades', unidades: [{ id: 10, nome: 'Unidade A' }, { id: 11, nome: 'Unidade B' }] },
      ],
    },
    { id: 12, nome: 'Faculdade C', tipo: 'Faculdades' },
  ],
});
assert.deepEqual(
  Array.from(gruposOferta.flatMap((grupo) => grupo.unidades.map((unidade) => unidade.nome))),
  ['Unidade A', 'Unidade B', 'Faculdade C'],
  'a lista reúne estruturas agrupadas, opções diretas e não duplica nomes',
);

assert.match(page, /Selecione a região\.\.\./);
assert.match(page, /Todas as estruturas/);
assert.match(page, /type="checkbox"/);
assert.match(page, /Pesquisar estruturas/);
assert.match(page, /estrutura-opcao-todas/);
assert.ok(page.indexOf('estrutura-opcao-todas') < page.indexOf('v-for="grupo in gruposOfertaFiltrados"'));
assert.match(page, /aria-checked=.*mixed/);
assert.match(page, /v-if="!resumoEstruturasSelecionadas" class="estruturas-dropdown-placeholder">Selecione a região\.\.\./);
assert.match(estilos, /\.estruturas-dropdown-placeholder\s*\{[^}]*color:\s*#6b7280/s);
assert.match(estilos, /\.estruturas-dropdown-resumo\s*\{[^}]*font-weight:\s*700/s);
assert.match(estilos, /\.estruturas-dropdown-resumo\s*\{[^}]*color:\s*#111827/s);
assert.match(page, /<div class="form-group">\s*<label for="eixo">/);
assert.match(page, /<div class="form-group">\s*<label for="segmento">/);
assert.match(page, /<div class="form-group">\s*<label for="alunos">/);
assert.match(page, /<div class="form-group">\s*<label for="instrutor">/);
assert.match(estilosResponsivos, /@media \(min-width: 768px\)[\s\S]*?\.cursos-page \.form-grid[\s\S]*?grid-template-columns:\s*1fr 1fr/);
assert.match(estilosResponsivos, /@media \(max-width: 768px\)[\s\S]*?\.cursos-page \.form-grid[\s\S]*?grid-template-columns:\s*1fr/);
assert.match(estilos, /\.estruturas-dropdown\s*\{[^}]*position:\s*absolute/s);
assert.match(estilos, /\.estruturas-dropdown\s*\{[^}]*overflow-y:\s*auto/s);
assert.doesNotMatch(page, /Selecionar estruturas/);
assert.match(page, /<label for="programa">Tipo de Curso<\/label>[\s\S]*?id="programa"[\s\S]*?v-model="form\.programa"/);
assert.match(page, /id="segmento"[\s\S]*?type="text"/);
assert.match(page, /id="modalidade"[\s\S]*?type="text"/);

console.log('Validações de Ident., estruturas, segmentos, modalidade e valores monetários passaram.');
