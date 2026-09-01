/**
 * Sincroniza telas lista ↔ formulário com o botão Voltar do navegador.
 * Usa ?form=novo|editar|edicao e ?form_id=ID na rota atual.
 */

export function querySemFormulario(query = {}) {
  const next = { ...query };
  delete next.form;
  delete next.form_id;
  return next;
}

export const mixinHistoricoFormulario = {
  data() {
    return {
      _navFormSilenciosa: false,
    };
  },

  watch: {
    '$route.query.form'() {
      this.sincronizarModoComHistorico();
    },
    '$route.query.form_id'() {
      this.sincronizarModoComHistorico();
    },
  },

  mounted() {
    this.$nextTick(() => {
      this.sincronizarModoComHistorico();
    });
  },

  methods: {
    /**
     * Páginas podem sobrescrever para resetar estado local sem mexer na rota.
     */
    aplicarEstadoListaLocal() {
      this.modo = 'lista';
      if (Object.prototype.hasOwnProperty.call(this, 'editandoId')) {
        this.editandoId = null;
      }
    },

    bloquearFormularioSemPermissao() {
      if (this.podeEditar === false) {
        if (typeof this.bloquearSemPermissao === 'function') {
          this.bloquearSemPermissao();
        } else {
          if (typeof this.mensagemErro === 'string') {
            this.mensagemErro = 'Seu perfil não tem permissão para alterar estes registros.';
          }
          this.aplicarEstadoListaLocal();
          this.limparHistoricoFormulario();
        }

        return true;
      }

      return false;
    },

    async sincronizarModoComHistorico() {
      if (this._navFormSilenciosa || !this.$route) {
        return;
      }

      const form = this.$route.query.form;
      const id = this.$route.query.form_id;

      if (!form) {
        if (this.modo && this.modo !== 'lista') {
          this.aplicarEstadoListaLocal();
        }
        return;
      }

      if (form === 'novo') {
        if (this.modo === 'novo') {
          return;
        }
        if (this.bloquearFormularioSemPermissao()) {
          return;
        }
        if (typeof this.aplicarEstadoNovoLocal === 'function') {
          this.aplicarEstadoNovoLocal();
        }
        return;
      }

      if (form === 'gerar') {
        if (this.modo === 'gerar') {
          return;
        }
        if (this.bloquearFormularioSemPermissao()) {
          return;
        }
        if (typeof this.aplicarEstadoGerarLocal === 'function') {
          this.aplicarEstadoGerarLocal();
        }
        return;
      }

      if ((form === 'editar' || form === 'edicao') && id) {
        const modoAlvo = form === 'edicao' ? 'edicao' : 'editar';
        const idAtual = this.editandoId
          ?? this.resolucaoEmEdicao?.id
          ?? null;

        if (this.modo === modoAlvo && String(idAtual) === String(id)) {
          return;
        }

        if (this.bloquearFormularioSemPermissao()) {
          return;
        }

        if (typeof this.aplicarEstadoEdicaoPorId === 'function') {
          await this.aplicarEstadoEdicaoPorId(id);
        }
      }
    },

    async empilharHistoricoFormulario(form, id = null) {
      if (!this.$router || !this.$route) {
        return;
      }

      const formAtual = this.$route.query.form;
      const idAtual = this.$route.query.form_id;

      if (formAtual === form && String(idAtual || '') === String(id || '')) {
        return;
      }

      const query = { ...this.$route.query, form };
      if (id != null && id !== '') {
        query.form_id = String(id);
      } else {
        delete query.form_id;
      }

      this._navFormSilenciosa = true;
      try {
        await this.$router.push({ query });
      } catch {
        // navegação duplicada / cancelada
      } finally {
        this._navFormSilenciosa = false;
      }
    },

    async limparHistoricoFormulario() {
      if (!this.$router || !this.$route) {
        return;
      }

      if (!this.$route.query.form && !this.$route.query.form_id) {
        return;
      }

      this._navFormSilenciosa = true;
      try {
        await this.$router.replace({ query: querySemFormulario(this.$route.query) });
      } catch {
        // ignore
      } finally {
        this._navFormSilenciosa = false;
      }
    },
  },
};

/**
 * Histórico para telas catálogo → detalhe (Relatórios / Importações).
 * Query: ?view=CHAVE
 */
export const mixinHistoricoCatalogo = {
  data() {
    return {
      _navCatalogoSilenciosa: false,
      queryViewKey: 'view',
    };
  },

  watch: {
    '$route.query.view'() {
      this.sincronizarCatalogoComHistorico();
    },
  },

  mounted() {
    this.$nextTick(() => {
      this.sincronizarCatalogoComHistorico();
    });
  },

  methods: {
    async empilharHistoricoCatalogo(chave) {
      if (!this.$router || !this.$route || !chave) {
        return;
      }

      if (String(this.$route.query.view || '') === String(chave)) {
        return;
      }

      this._navCatalogoSilenciosa = true;
      try {
        await this.$router.push({
          query: { ...this.$route.query, view: String(chave) },
        });
      } catch {
        // ignore
      } finally {
        this._navCatalogoSilenciosa = false;
      }
    },

    async limparHistoricoCatalogo() {
      if (!this.$router || !this.$route || !this.$route.query.view) {
        return;
      }

      const query = { ...this.$route.query };
      delete query.view;

      this._navCatalogoSilenciosa = true;
      try {
        await this.$router.replace({ query });
      } catch {
        // ignore
      } finally {
        this._navCatalogoSilenciosa = false;
      }
    },

    sincronizarCatalogoComHistorico() {
      if (this._navCatalogoSilenciosa) {
        return;
      }
      if (typeof this.aplicarEstadoCatalogoDaRota === 'function') {
        this.aplicarEstadoCatalogoDaRota(this.$route?.query?.view || null);
      }
    },
  },
};
