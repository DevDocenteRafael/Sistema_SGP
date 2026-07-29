import { podeImportarDados } from './auth';

export default {
  name: 'Importacoes',

  data() {
    return {
      etapa: 'catalogo',
      catalogo: [],
      moduloAtivo: null,
      arquivo: null,
      processando: false,
      erro: '',
      mensagem: '',
      previa: {
        aba: '',
        total: 0,
        ignoradas: 0,
        erros: [],
        linhas: [],
        colunas_preview: [],
        label: '',
      },
    };
  },

  computed: {
    podeImportar() {
      return podeImportarDados();
    },
  },

  async created() {
    await this.carregarCatalogo();
  },

  methods: {
    async carregarCatalogo() {
      if (!this.podeImportar) {
        this.catalogo = [];
        return;
      }

      try {
        const { data } = await window.axios.get('/api/importacoes');
        this.catalogo = data.data || [];
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar os módulos de importação.';
      }
    },

    iniciarModulo(item) {
      if (!this.podeImportar) return;
      this.moduloAtivo = item;
      this.erro = '';
      this.mensagem = '';
      this.arquivo = null;
      this.previa = {
        aba: '',
        total: 0,
        ignoradas: 0,
        erros: [],
        linhas: [],
        colunas_preview: item.preview_columns || [],
        label: item.label,
      };
      this.etapa = 'upload';
    },

    voltarCatalogo() {
      this.etapa = 'catalogo';
      this.moduloAtivo = null;
      this.arquivo = null;
      this.previa = {
        aba: '',
        total: 0,
        ignoradas: 0,
        erros: [],
        linhas: [],
        colunas_preview: [],
        label: '',
      };
      this.erro = '';
      if (this.$refs.inputArquivo) {
        this.$refs.inputArquivo.value = '';
      }
    },

    onArquivoSelecionado(event) {
      const file = event.target.files?.[0] || null;
      this.arquivo = file;
      this.erro = '';
      this.mensagem = '';
    },

    formComArquivo() {
      const form = new FormData();
      form.append('arquivo', this.arquivo);
      return form;
    },

    celula(linha, key) {
      const valor = linha?.[key];
      return valor === null || valor === undefined || valor === '' ? '—' : valor;
    },

    async gerarPrevia() {
      if (!this.arquivo || this.processando || !this.moduloAtivo) return;

      this.processando = true;
      this.erro = '';
      this.mensagem = '';

      try {
        const { data } = await window.axios.post(
          `/api/importacoes/${this.moduloAtivo.key}/preview`,
          this.formComArquivo(),
          { headers: { 'Content-Type': 'multipart/form-data' } },
        );

        this.previa = {
          aba: data.aba || '',
          total: data.total || 0,
          ignoradas: data.ignoradas || 0,
          erros: data.erros || [],
          linhas: data.linhas || [],
          colunas_preview: data.colunas_preview || this.moduloAtivo.preview_columns || [],
          label: data.label || this.moduloAtivo.label,
        };
        this.etapa = 'previa';

        if (!this.previa.total) {
          this.erro = `Nenhuma linha válida encontrada para ${this.previa.label}.`;
        }
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível gerar a prévia.';
      } finally {
        this.processando = false;
      }
    },

    async confirmarImportacao() {
      if (!this.arquivo || this.processando || !this.previa.total || !this.moduloAtivo) return;

      const ok = window.confirm(
        `Isso vai APAGAR todos os registros atuais de ${this.previa.label || this.moduloAtivo.label} e importar ${this.previa.total} linha(s).\n\nAntes da substituição, o sistema grava um backup automático dos dados atuais.\n\nDeseja continuar?`,
      );
      if (!ok) return;

      this.processando = true;
      this.erro = '';
      this.mensagem = '';

      try {
        const { data } = await window.axios.post(
          `/api/importacoes/${this.moduloAtivo.key}/commit`,
          this.formComArquivo(),
          { headers: { 'Content-Type': 'multipart/form-data' } },
        );

        const backupTotal = data.backup?.total;
        const backupPath = data.backup?.path;
        let msg = data.message || `Importação concluída: ${data.importados} registro(s).`;

        if (backupPath) {
          msg += ` Backup prévio: ${backupTotal ?? 0} registro(s) em ${backupPath}.`;
        }

        this.mensagem = msg;
        this.voltarCatalogo();
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível concluir a importação.';
      } finally {
        this.processando = false;
      }
    },
  },
};
