import { podeEditarDados } from './auth';
import { invalidarCacheCiclos, salvarCicloContexto } from './cicloContexto';
import CrudPageHeader from '../components/crud/CrudPageHeader.vue';
import CrudAlerts from '../components/crud/CrudAlerts.vue';
import CrudFormShell from '../components/crud/CrudFormShell.vue';
import PageTableCard from '../components/crud/PageTableCard.vue';
import { mixinHistoricoFormulario } from './formularioHistorico';
import {
  combinarValidacoes,
  extrairErroApi,
  tamanhoMaximo,
  textoObrigatorio,
} from '../utils/validacao';

const ENDPOINT = '/api/portfolio-ciclos';

function formVazio() {
  return {
    nome: '',
    observacao: '',
    atual: false,
    origem_id: '',
    marcar_atual: true,
    copiar_cursos: true,
  };
}

export default {
  name: 'CiclosPortfolio',
  mixins: [mixinHistoricoFormulario],
  components: {
    CrudPageHeader,
    CrudAlerts,
    CrudFormShell,
    PageTableCard,
  },
  data() {
    return {
      modo: 'lista',
      carregando: false,
      salvando: false,
      mensagemSucesso: '',
      mensagemErro: '',
      erroFormulario: '',
      filtros: {
        busca: '',
      },
      registros: [],
      registroDetalhe: null,
      editandoId: null,
      form: formVazio(),
      buscaTimeout: null,
    };
  },
  computed: {
    podeEditar() {
      return podeEditarDados();
    },
    temFiltro() {
      return Object.values(this.filtros).some(Boolean);
    },
    totalRegistros() {
      return this.registros.length;
    },
    tituloForm() {
      if (this.modo === 'gerar') {
        return 'Gerar próximo ciclo';
      }

      return this.modo === 'editar' ? 'Editar ciclo' : 'Cadastrar ciclo';
    },
    subtituloForm() {
      if (this.modo === 'gerar') {
        return 'Copia os cursos do ciclo de origem para um ciclo novo. Metas, PCA e Eixos entram pelos anos do nome (ex.: 2028).';
      }

      return this.modo === 'editar'
        ? 'Atualize o nome e as observações deste ciclo.'
        : 'Crie um ciclo vazio. Use anos no nome (2028 ou 2028-2029) para ligar Metas, PCA e Eixos.';
    },
    textoBotaoSalvar() {
      if (this.salvando) {
        return this.modo === 'gerar' ? 'Gerando...' : 'Salvando...';
      }

      if (this.modo === 'gerar') {
        return 'Gerar ciclo';
      }

      return this.modo === 'editar' ? 'Salvar Alterações' : 'Cadastrar';
    },
    destinoVoltar() {
      const bruto = this.$route.query.voltar;
      if (typeof bruto === 'string' && bruto.startsWith('/app/') && !bruto.startsWith('/app/ciclos-portfolio')) {
        return bruto;
      }

      return '/app/cursos';
    },
    origemSelecionada() {
      if (!this.form.origem_id) {
        return null;
      }
      return this.registros.find((item) => String(item.id) === String(this.form.origem_id)) || null;
    },
    cursosOrigemCount() {
      const origem = this.origemSelecionada;
      if (!origem) {
        return 0;
      }
      return Number(origem.composicao?.cursos ?? origem.cursos_count ?? 0);
    },
    avisoCopiaCursos() {
      if (this.modo !== 'gerar') {
        return '';
      }
      if (!this.form.copiar_cursos) {
        return 'O ciclo será criado vazio (sem copiar cursos). Metas, PCA e Eixos entram pelos anos do nome.';
      }
      const total = this.cursosOrigemCount;
      const nome = this.origemSelecionada?.nome || 'origem';
      if (total === 1) {
        return `Será copiado 1 curso do ciclo ${nome}. Metas, PCA e Eixos não são clonados.`;
      }
      return `Serão copiados ${total} cursos do ciclo ${nome}. Metas, PCA e Eixos não são clonados.`;
    },
  },
  mounted() {
    this.carregarRegistros();
  },
  methods: {
    limparFiltros() {
      this.filtros = { busca: '' };
      this.aplicarFiltros();
    },
    aplicarFiltros() {
      clearTimeout(this.buscaTimeout);
      this.buscaTimeout = setTimeout(() => this.carregarRegistros(), 200);
    },

    async carregarRegistros() {
      this.carregando = true;
      this.mensagemErro = '';

      try {
        const params = {};
        Object.entries(this.filtros).forEach(([chave, valor]) => {
          if (valor) {
            params[chave] = valor;
          }
        });

        const { data } = await window.axios.get(ENDPOINT, { params });
        this.registros = data.data ?? [];
      } catch (error) {
        this.mensagemErro = extrairErroApi(error, 'Não foi possível carregar os ciclos.');
        this.registros = [];
      } finally {
        this.carregando = false;
      }
    },

    abrirNovo() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para cadastrar ciclos.';
        return;
      }

      this.aplicarEstadoNovoLocal();
      this.empilharHistoricoFormulario('novo');
    },

    aplicarEstadoNovoLocal() {
      this.modo = 'novo';
      this.editandoId = null;
      this.form = formVazio();
      this.erroFormulario = '';
      this.registroDetalhe = null;
    },

    abrirGerar() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para gerar ciclos.';
        return;
      }

      this.aplicarEstadoGerarLocal();
      this.empilharHistoricoFormulario('gerar');
    },

    aplicarEstadoGerarLocal() {
      const atual = this.registros.find((ciclo) => ciclo.atual) || this.registros[0];
      this.modo = 'gerar';
      this.editandoId = null;
      this.form = {
        ...formVazio(),
        origem_id: atual ? String(atual.id) : '',
        marcar_atual: true,
      };
      this.erroFormulario = '';
      this.registroDetalhe = null;
    },

    abrirEdicao(item) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para editar ciclos.';
        return;
      }

      this.aplicarEstadoEdicaoLocal(item);
      this.empilharHistoricoFormulario('editar', item.id);
    },

    aplicarEstadoEdicaoLocal(item) {
      this.modo = 'editar';
      this.editandoId = item.id;
      this.form = {
        ...formVazio(),
        nome: item.nome ?? '',
        observacao: item.observacao ?? '',
        atual: Boolean(item.atual),
      };
      this.erroFormulario = '';
      this.registroDetalhe = null;
    },

    async aplicarEstadoEdicaoPorId(id) {
      let item = this.registros.find((registro) => String(registro.id) === String(id));

      if (!item) {
        try {
          const { data } = await window.axios.get(`${ENDPOINT}/${id}`);
          item = data.ciclo || data.data || null;
        } catch {
          item = null;
        }
      }

      if (!item) {
        this.aplicarEstadoListaLocal();
        this.limparHistoricoFormulario();
        return;
      }

      this.aplicarEstadoEdicaoLocal(item);
    },

    voltarLista() {
      this.aplicarEstadoListaLocal();
      this.limparHistoricoFormulario();
    },

    aplicarEstadoListaLocal() {
      this.modo = 'lista';
      this.editandoId = null;
      this.form = formVazio();
      this.erroFormulario = '';
    },

    abrirDetalhes(item) {
      this.registroDetalhe = item;
    },

    fecharDetalhes() {
      this.registroDetalhe = null;
    },

    escolherCiclo(item) {
      salvarCicloContexto(item);
      this.mensagemSucesso = `Ciclo "${item.nome}" definido como contexto global.`;
      this.fecharDetalhes();
    },

    abrirPortfolio(item) {
      salvarCicloContexto(item);
      this.irPara('/app/cursos', { ciclo_id: String(item.id) });
    },

    abrirModulo(item, path) {
      salvarCicloContexto(item);
      this.irPara(`/app/${path}`, { ciclo_id: String(item.id) });
    },

    irPara(path, query = {}) {
      this.$router.push({ path, query }).catch(() => {
        window.location.assign(query && Object.keys(query).length
          ? `${path}?${new URLSearchParams(query).toString()}`
          : path);
      });
    },

    sairGerenciar() {
      const destino = this.destinoVoltar;
      this.$router.push(destino).catch(() => {
        window.location.assign(typeof destino === 'string' ? destino : destino.path || '/app/cursos');
      });
    },

    validarFormulario() {
      return combinarValidacoes(
        textoObrigatorio(this.form.nome, 'Informe o nome do ciclo.'),
        tamanhoMaximo(this.form.nome, 80, 'O nome deve ter no máximo 80 caracteres.'),
        this.form.observacao
          ? tamanhoMaximo(this.form.observacao, 2000, 'A observação deve ter no máximo 2000 caracteres.')
          : '',
        this.modo === 'gerar' && !this.form.origem_id
          ? 'Selecione o ciclo de origem para gerar o próximo ciclo.'
          : '',
      );
    },

    async salvarRegistro() {
      if (!this.podeEditar) {
        this.erroFormulario = 'Seu perfil não tem permissão para alterar ciclos.';
        return;
      }

      const erro = this.validarFormulario();
      if (erro) {
        this.erroFormulario = erro;
        return;
      }

      this.salvando = true;
      this.erroFormulario = '';

      try {
        let data;

        if (this.modo === 'gerar') {
          const response = await window.axios.post(`${ENDPOINT}/gerar-proximo`, {
            origem_id: this.form.origem_id || null,
            nome: this.form.nome.trim(),
            observacao: this.form.observacao.trim() || null,
            marcar_atual: this.form.marcar_atual,
            copiar_cursos: this.form.copiar_cursos,
          });
          data = response.data;
        } else if (this.editandoId) {
          const response = await window.axios.put(`${ENDPOINT}/${this.editandoId}`, {
            nome: this.form.nome.trim(),
            observacao: this.form.observacao.trim() || null,
            atual: this.form.atual,
          });
          data = response.data;
        } else {
          const response = await window.axios.post(ENDPOINT, {
            nome: this.form.nome.trim(),
            observacao: this.form.observacao.trim() || null,
            atual: this.form.atual,
          });
          data = response.data;
        }

        this.mensagemSucesso = data.message;
        invalidarCacheCiclos();

        if ((this.modo === 'novo' || this.modo === 'gerar') && data.ciclo) {
          salvarCicloContexto(data.ciclo);
          this.voltarLista();
          await this.carregarRegistros();
          return;
        }

        if (data.ciclo) {
          salvarCicloContexto(data.ciclo);
        }

        this.voltarLista();
        await this.carregarRegistros();
      } catch (error) {
        this.erroFormulario = extrairErroApi(error, 'Não foi possível salvar o ciclo.');
      } finally {
        this.salvando = false;
      }
    },

    async marcarComoAtual(item) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para alterar ciclos.';
        return;
      }

      if (item.atual) {
        return;
      }

      try {
        const { data } = await window.axios.post(`${ENDPOINT}/${item.id}/marcar-atual`);
        this.mensagemSucesso = data.message;
        invalidarCacheCiclos();
        if (data.ciclo) {
          salvarCicloContexto(data.ciclo);
        } else {
          salvarCicloContexto({ ...item, atual: true });
        }
        this.fecharDetalhes();
        await this.carregarRegistros();
      } catch (error) {
        this.mensagemErro = extrairErroApi(error, 'Não foi possível definir o ciclo atual.');
      }
    },

    async excluirRegistro(item) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para excluir ciclos.';
        return;
      }

      const cursos = Number(item.composicao?.cursos ?? item.cursos_count ?? 0);
      const metas = Number(item.composicao?.plano_de_metas ?? 0);
      const pca = Number(item.composicao?.pca ?? 0);
      const eixos = Number(item.composicao?.eixos ?? 0);
      const temRegistros = cursos + metas + pca + eixos > 0;

      let limparRegistros = false;

      if (temRegistros) {
        const resumo = [
          this.textoQuantidade(cursos, 'curso', 'cursos'),
          this.textoQuantidade(metas, 'meta', 'metas'),
          this.textoQuantidade(pca, 'PCA', 'PCAs'),
          this.textoQuantidade(eixos, 'eixo', 'eixos'),
        ].join(', ');

        const confirmar = window.confirm(
          `O ciclo "${item.nome}" ainda tem registros (${resumo}).\n\n`
          + 'OK = excluir o ciclo E apagar esses registros deste ciclo.\n'
          + 'Cancelar = manter tudo.',
        );

        if (!confirmar) {
          return;
        }

        limparRegistros = true;
      } else if (!window.confirm(`Excluir o ciclo "${item.nome}"? Esta ação não pode ser desfeita.`)) {
        return;
      }

      try {
        const { data } = await window.axios.delete(`${ENDPOINT}/${item.id}`, {
          params: limparRegistros ? { limpar_registros: 1 } : undefined,
        });
        this.mensagemSucesso = data.message;
        invalidarCacheCiclos();
        this.fecharDetalhes();
        await this.carregarRegistros();
      } catch (error) {
        const payload = error?.response?.data;
        if (payload?.exige_limpeza && !limparRegistros) {
          const comp = payload.composicao || {};
          const resumo = [
            this.textoQuantidade(comp.cursos, 'curso', 'cursos'),
            this.textoQuantidade(comp.plano_de_metas, 'meta', 'metas'),
            this.textoQuantidade(comp.pca, 'PCA', 'PCAs'),
            this.textoQuantidade(comp.eixos, 'eixo', 'eixos'),
          ].join(', ');

          const forcar = window.confirm(
            `${payload.message || 'Este ciclo ainda possui registros.'}\n\n`
            + `Registros: ${resumo}.\n\n`
            + 'OK = excluir com limpeza (apaga os registros deste ciclo).\n'
            + 'Cancelar = manter.',
          );

          if (forcar) {
            try {
              const { data } = await window.axios.delete(`${ENDPOINT}/${item.id}`, {
                params: { limpar_registros: 1 },
              });
              this.mensagemSucesso = data.message;
              invalidarCacheCiclos();
              this.fecharDetalhes();
              await this.carregarRegistros();
              return;
            } catch (erroLimpeza) {
              this.mensagemErro = extrairErroApi(erroLimpeza, 'Não foi possível excluir o ciclo com limpeza.');
              return;
            }
          }
        }

        this.mensagemErro = extrairErroApi(error, 'Não foi possível excluir o ciclo.');
      }
    },

    textoQuantidade(valor, singular, plural) {
      const total = Number(valor || 0);

      return total === 1 ? `1 ${singular}` : `${total} ${plural}`;
    },
  },
};
