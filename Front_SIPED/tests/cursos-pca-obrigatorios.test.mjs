import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import * as validacao from '../resources/js/utils/validacao.js';

function carregarMetodo(pagina, metodo) {
  const source = readFileSync(new URL(`../resources/js/scripts/${pagina}.js`, import.meta.url), 'utf8');
  const match = source.match(new RegExp(`^( +)${metodo}\\([^)]*\\) \\{[\\s\\S]*?^\\1\\},`, 'm'));
  assert(match, `${pagina}.${metodo} não encontrado`);
  return vm.runInNewContext(`({${match[0]}})`, { ...validacao })[metodo];
}

const abas = {
  basico: {
    eixo: 'Gestão e Negócios', segmento: 'Administração', programa: '60+', ciclo_id: '1',
    titulo: 'Curso', carga_horaria: '80', turmas: '0', alunos: '0', codigo_processo: '123',
    instrutor: 'Instrutor', descricao: 'Descrição', unidades_oferta: ['Unidade'],
  },
  tecnico: {
    status: 'Ativo', modalidade: 'Presencial', codigo_dn: '123', codigo_sig: '456',
    identificacao: '2026', ultima_revisao: '2026', processo_sei: '123.456/2026-01',
    data_inicio: '2026-09-01', data_fim: '2026-09-30',
  },
  comercial: {
    valores: '0', compativel_bolsa: 'NÃO', comercial: 'NÃO', pcn: 'PCN', pcr: 'PCR', observacoes: 'Observações',
  },
};
const validarAba = carregarMetodo('Cursos', 'validarAba');
let checks = 0;
for (const [aba, values] of Object.entries(abas)) {
  // Uma aba preenchida deve permitir avançar, mesmo com as próximas abas vazias.
  const context = { form: { ...values }, regiaoOfertaSelecionada: '1' };
  assert.equal(validarAba.call(context, aba), '', `Cursos: ${aba} válida`);
  checks++;
  for (const [field, original] of Object.entries(values)) {
    for (const empty of ['', null, undefined, ...(Array.isArray(original) ? [[]] : [])]) {
      context.form[field] = empty;
      assert(validarAba.call(context, aba), `Cursos.${field} vazio deve ser rejeitado na aba ${aba}`);
      checks++;
    }
    context.form[field] = original;
  }
}
assert(validarAba.call({ form: abas.basico, regiaoOfertaSelecionada: '' }, 'basico'));
checks++;

const validarPca = carregarMetodo('Pca', 'validarFormulario');
const pca = {
  titulo: 'Curso', status: 'Vigente', ano: '2026', semestre: '2026/1', numero_sei: '123.456/2026-01',
  codigo_sig: '123', eixo: 'Eixo', unidade: 'Unidade', carga_horaria: '80', precificacao: '0',
  valor_primeiro_modulo: '0', valor: '0', parcelas_boleto: '1', valor_parcela_boleto: '0',
  parcelas_cartao: '1', valor_cartao: '0', parcela_desc_20: '0', parcela_desc_15: '0', observacao: 'Observação',
};
assert.equal(validarPca(pca), '', 'PCA válido, incluindo valores financeiros zero');
checks++;
for (const [field, original] of Object.entries(pca)) {
  for (const empty of ['', null, undefined]) {
    pca[field] = empty;
    assert(validarPca(pca), `PCA.${field} vazio deve ser rejeitado`);
    checks++;
  }
  pca[field] = original;
}
console.log(`${checks} verificações passaram: campos vazios, abas independentes, zero e NÃO.`);
