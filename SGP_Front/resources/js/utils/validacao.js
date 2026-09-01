/**
 * Utilitários compartilhados de validação e máscaras para formulários.
 */

export function extrairErroApi(error, fallback = 'Não foi possível concluir a operação.') {
  if (error?.response?.data?.message) {
    return error.response.data.message;
  }

  const errors = error?.response?.data?.errors;

  if (errors) {
    const primeiro = Object.values(errors)[0];
    return Array.isArray(primeiro) ? primeiro[0] : fallback;
  }

  return fallback;
}

export function somenteNumeros(valor) {
  return String(valor ?? '').replace(/\D/g, '');
}

export function somenteAlfanumericoProcesso(valor) {
  return String(valor ?? '').replace(/[^0-9./-]/gi, '');
}

export function somenteDecimal(valor) {
  return String(valor ?? '').replace(/[^\d.,]/g, '');
}

export function mascaraCpf(valor) {
  const numeros = somenteNumeros(valor).slice(0, 11);

  if (numeros.length <= 3) return numeros;
  if (numeros.length <= 6) return `${numeros.slice(0, 3)}.${numeros.slice(3)}`;
  if (numeros.length <= 9) {
    return `${numeros.slice(0, 3)}.${numeros.slice(3, 6)}.${numeros.slice(6)}`;
  }

  return `${numeros.slice(0, 3)}.${numeros.slice(3, 6)}.${numeros.slice(6, 9)}-${numeros.slice(9)}`;
}

export function mascaraTelefone(valor) {
  const numeros = somenteNumeros(valor).slice(0, 11);

  if (numeros.length <= 2) return numeros.length ? `(${numeros}` : '';
  if (numeros.length <= 6) return `(${numeros.slice(0, 2)}) ${numeros.slice(2)}`;
  if (numeros.length <= 10) {
    return `(${numeros.slice(0, 2)}) ${numeros.slice(2, 6)}-${numeros.slice(6)}`;
  }

  return `(${numeros.slice(0, 2)}) ${numeros.slice(2, 7)}-${numeros.slice(7)}`;
}

export function mascaraCep(valor) {
  const numeros = somenteNumeros(valor).slice(0, 8);

  if (numeros.length <= 5) return numeros;
  return `${numeros.slice(0, 5)}-${numeros.slice(5)}`;
}

export function cpfValido(cpf) {
  const numeros = somenteNumeros(cpf);

  if (numeros.length !== 11 || /^(\d)\1{10}$/.test(numeros)) {
    return false;
  }

  const calcularDigito = (base, pesoInicial) => {
    let soma = 0;

    for (let i = 0; i < base.length; i += 1) {
      soma += Number(base[i]) * (pesoInicial - i);
    }

    const resto = (soma * 10) % 11;
    return resto === 10 ? 0 : resto;
  };

  const digito1 = calcularDigito(numeros.slice(0, 9), 10);
  const digito2 = calcularDigito(numeros.slice(0, 10), 11);

  return digito1 === Number(numeros[9]) && digito2 === Number(numeros[10]);
}

export function emailValido(valor) {
  const texto = String(valor ?? '').trim();
  if (!texto) return false;
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(texto);
}

export function textoObrigatorio(valor, mensagem) {
  if (!String(valor ?? '').trim()) {
    return mensagem;
  }
  return '';
}

export function tamanhoMaximo(valor, maximo, mensagem) {
  if (String(valor ?? '').length > maximo) {
    return mensagem;
  }
  return '';
}

export function validarCpf(cpf, { obrigatorio = false, rotulo = 'CPF' } = {}) {
  const texto = String(cpf ?? '').trim();

  if (!texto) {
    return obrigatorio ? `${rotulo} é obrigatório.` : '';
  }

  if (!cpfValido(texto)) {
    return `${rotulo} inválido.`;
  }

  return '';
}

export function validarEmail(campo, { obrigatorio = false, rotulo = 'E-mail' } = {}) {
  const texto = String(campo ?? '').trim();

  if (!texto) {
    return obrigatorio ? `${rotulo} é obrigatório.` : '';
  }

  if (!emailValido(texto)) {
    return `${rotulo} inválido.`;
  }

  return '';
}

export function validarInteiro(valor, { obrigatorio = false, min = 0, max = null, rotulo = 'Campo' } = {}) {
  const texto = String(valor ?? '').trim();

  if (!texto) {
    return obrigatorio ? `${rotulo} é obrigatório.` : '';
  }

  if (!/^\d+$/.test(texto)) {
    return `${rotulo} deve conter apenas números.`;
  }

  const numero = Number(texto);

  if (numero < min) {
    return `${rotulo} deve ser no mínimo ${min}.`;
  }

  if (max != null && numero > max) {
    return `${rotulo} deve ser no máximo ${max}.`;
  }

  return '';
}

export function validarDecimal(valor, { obrigatorio = false, rotulo = 'Valor' } = {}) {
  const texto = String(valor ?? '').trim();

  if (!texto) {
    return obrigatorio ? `${rotulo} é obrigatório.` : '';
  }

  const normalizado = texto.replace(/\./g, '').replace(',', '.');

  if (!/^\d+(\.\d+)?$/.test(normalizado)) {
    return `${rotulo} deve ser um número válido.`;
  }

  return '';
}

export function validarData(valor, { obrigatorio = false, rotulo = 'Data' } = {}) {
  const texto = String(valor ?? '').trim();

  if (!texto) {
    return obrigatorio ? `${rotulo} é obrigatória.` : '';
  }

  if (!/^\d{4}-\d{2}-\d{2}$/.test(texto)) {
    return `${rotulo} inválida.`;
  }

  const data = new Date(`${texto}T00:00:00`);

  if (Number.isNaN(data.getTime())) {
    return `${rotulo} inválida.`;
  }

  return '';
}

export function validarOrdemDatas(inicio, fim, mensagem = 'A data de término deve ser igual ou posterior à data de início.') {
  if (!inicio || !fim) {
    return '';
  }

  if (fim < inicio) {
    return mensagem;
  }

  return '';
}

export function validarProcessoSei(valor, { obrigatorio = false, rotulo = 'Processo SEI' } = {}) {
  const texto = String(valor ?? '').trim();

  if (!texto) {
    return obrigatorio ? `${rotulo} é obrigatório.` : '';
  }

  if (texto.length > 100) {
    return `${rotulo} deve ter no máximo 100 caracteres.`;
  }

  if (!/^[0-9./-]+$/.test(texto)) {
    return `${rotulo} deve conter apenas números, pontos, barras ou hífens.`;
  }

  if (!/\d/.test(texto)) {
    return `${rotulo} deve conter ao menos um número.`;
  }

  return '';
}

export function validarSenha(senha, { obrigatorio = false, min = 6, rotulo = 'Senha' } = {}) {
  const texto = String(senha ?? '');

  if (!texto) {
    return obrigatorio ? `${rotulo} é obrigatória.` : '';
  }

  if (texto.length < min) {
    return `${rotulo} deve ter no mínimo ${min} caracteres.`;
  }

  return '';
}

export function combinarValidacoes(...resultados) {
  return resultados.find(Boolean) || '';
}

/** Factory para @input em campos de processo SEI (v-model). */
export function formatarProcessoSeiInput(campo = 'processo_sei') {
  return function formatarProcessoSei(evento) {
    this.form[campo] = somenteAlfanumericoProcesso(evento.target.value);
  };
}

/** Factory para @input em campos numéricos inteiros (v-model). */
export function formatarInteiroInput(campo) {
  return function formatarInteiro(evento) {
    this.form[campo] = somenteNumeros(evento.target.value);
  };
}

/** Factory para @input em campos decimais/monetários (v-model). */
export function formatarDecimalInput(campo) {
  return function formatarDecimal(evento) {
    this.form[campo] = somenteDecimal(evento.target.value);
  };
}
