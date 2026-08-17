<template>
  <div class="controle-resolucoes-page">
    <header class="crud-top">
      <div class="crud-top-row">
        <div>
          <h1>Controle de Resoluções</h1>
          <p class="crud-subtitle">Acompanhamento da vigência, status e vencimentos das resoluções institucionais</p>
        </div>

        <div class="header-actions">
          <button type="button" class="btn-novo" @click="abrirNovaResolucao">
            <span class="btn-novo-icon">+</span>
            Nova resolução
          </button>
        </div>
      </div>

      <div class="crud-info">Atualização diária com base na vigência atual e no status de acompanhamento.</div>
    </header>

    <section class="filtros-panel">
      <div class="filtros-row">
        <div class="filtro-busca">
          <span class="filtro-busca-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          </span>
          <input
            v-model="filtros.busca"
            type="search"
            placeholder="Buscar por número, assunto ou responsável"
            aria-label="Buscar autorização"
          />
        </div>

        <div class="filtro-campo filtro-dropdown">
          <label for="filtro-resumo">Resumo</label>
          <select id="filtro-resumo" v-model="filtroResumo" @change="aplicarResumoFiltro">
            <option v-for="option in resumoOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>

        <div class="filtro-campo">
          <label for="filtro-unidade">Unidade</label>
          <select id="filtro-unidade" v-model="filtros.unidade">
            <option value="">Todas</option>
            <option value="CPED">CPED</option>
            <option value="Coordenação">Coordenação</option>
            <option value="Gabinete">Gabinete</option>
          </select>
        </div>

        <div class="filtro-campo">
          <label for="filtro-status">Status</label>
          <select id="filtro-status" v-model="filtros.status">
            <option value="">Todos</option>
            <option value="vigente">Vigente</option>
            <option value="proximo">Próximo do vencimento</option>
            <option value="vencida">Vencida</option>
          </select>
        </div>

        <div class="filtro-campo">
          <label for="filtro-ano">Ano</label>
          <select id="filtro-ano" v-model="filtros.ano">
            <option value="">Todos</option>
            <option value="2024">2024</option>
            <option value="2025">2025</option>
            <option value="2026">2026</option>
          </select>
        </div>
      </div>

      <div class="filtros-rodape">
        <div class="filtros-resumo" aria-live="polite">
          <span v-if="filtros.unidade" class="resumo-chip">Unidade: {{ filtros.unidade }}</span>
          <span v-if="filtros.status" class="resumo-chip">Status: {{ filtros.status }}</span>
          <span v-if="filtros.ano" class="resumo-chip">Ano: {{ filtros.ano }}</span>
        </div>

        <button v-if="temFiltroAtivo" type="button" class="btn-limpar-filtros" @click="limparFiltros">
          Limpar filtros
        </button>
      </div>
    </section>

    <section class="tabela-card">
      <div class="tabela-header">
        <span>{{ registrosFiltrados.length }} {{ registrosFiltrados.length === 1 ? 'resolução' : 'resoluções' }}</span>
        <span class="tabela-header-meta">{{ filtros.status || 'Todos os status' }}</span>
      </div>

      <div v-if="registrosFiltrados.length === 0" class="tabela-vazia">
        Nenhuma resolução encontrada para os filtros selecionados.
      </div>

      <div v-else class="tabela-wrap">
        <table class="crud-table">
          <thead>
            <tr>
              <th>Resolução</th>
              <th>Assunto</th>
              <th>Unidade</th>
              <th>Vigência</th>
              <th>Status</th>
              <th>Responsável</th>
              <th class="text-center">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in registrosFiltrados" :key="item.id">
              <td>
                <div class="resolucao-numero">{{ item.numero }}</div>
                <small class="meta-muted">{{ item.ano }}</small>
              </td>
              <td>
                <div class="assunto-texto">{{ item.assunto }}</div>
              </td>
              <td>
                <span class="tag-unidade">{{ item.unidade }}</span>
              </td>
              <td>
                <div class="vigencia-data">{{ item.inicio }}</div>
                <small class="meta-muted">{{ item.fim }}</small>
              </td>
              <td>
                <span class="status-badge" :class="statusClasse(item.status)">{{ item.statusLabel }}</span>
              </td>
              <td>
                <div class="responsavel-texto">{{ item.responsavel }}</div>
              </td>
              <td class="text-center acoes">
                <button type="button" class="btn-icon btn-view" title="Ver detalhes" @click="abrirDetalhes(item)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                <button type="button" class="btn-icon btn-edit" title="Editar resolução" @click="abrirEdicao(item)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </button>
                <button type="button" class="btn-icon btn-delete" title="Excluir resolução" @click="excluirResolucao(item)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <div v-if="modalAberto" class="modal-overlay" @click.self="fecharModal">
      <div class="modal-detalhes" role="dialog" aria-modal="true" :aria-label="modalTitulo">
        <div class="modal-detalhes-header">
          <h2>{{ modalTitulo }}</h2>
          <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharModal">×</button>
        </div>

        <div v-if="modalModo === 'detalhe' && resolucaoEmEdicao" class="modal-detalhes-content">
          <div class="detalhe-grid">
            <div class="detalhe-campo">
              <span class="detalhe-label">Número</span>
              <span class="detalhe-valor">{{ resolucaoEmEdicao.numero }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Ano</span>
              <span class="detalhe-valor">{{ resolucaoEmEdicao.ano }}</span>
            </div>
            <div class="detalhe-campo detalhe-campo-full">
              <span class="detalhe-label">Assunto</span>
              <span class="detalhe-valor">{{ resolucaoEmEdicao.assunto }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Unidade</span>
              <span class="detalhe-valor">{{ resolucaoEmEdicao.unidade }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Responsável</span>
              <span class="detalhe-valor">{{ resolucaoEmEdicao.responsavel }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Status</span>
              <span class="detalhe-valor"><span class="status-badge" :class="statusClasse(resolucaoEmEdicao.status)">{{ resolucaoEmEdicao.statusLabel }}</span></span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Início</span>
              <span class="detalhe-valor">{{ resolucaoEmEdicao.inicio }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Fim</span>
              <span class="detalhe-valor">{{ resolucaoEmEdicao.fim }}</span>
            </div>
            <div v-if="resolucaoEmEdicao.observacao" class="detalhe-campo detalhe-campo-full">
              <span class="detalhe-label">Observação</span>
              <span class="detalhe-valor">{{ resolucaoEmEdicao.observacao }}</span>
            </div>
          </div>

          <div class="modal-detalhes-actions">
            <button type="button" class="btn-secondary" @click="fecharModal">Fechar</button>
            <button type="button" class="btn-novo" @click="abrirEdicao(resolucaoEmEdicao)">Editar</button>
          </div>
        </div>

        <form v-else class="modal-form" @submit.prevent="salvarResolucao">
          <div class="form-grid">
            <div class="form-group">
              <label for="resolucao-numero">Número</label>
              <input id="resolucao-numero" v-model="form.numero" type="text" placeholder="Ex: RES-2026/010" required />
            </div>

            <div class="form-group">
              <label for="resolucao-ano">Ano</label>
              <input id="resolucao-ano" v-model.number="form.ano" type="number" min="2020" max="2100" required />
            </div>

            <div class="form-group form-group-wide">
              <label for="resolucao-assunto">Assunto</label>
              <input id="resolucao-assunto" v-model="form.assunto" type="text" placeholder="Descreva o assunto da resolução" required />
            </div>

            <div class="form-group">
              <label for="resolucao-unidade">Unidade</label>
              <select id="resolucao-unidade" v-model="form.unidade" required>
                <option value="">Selecione...</option>
                <option value="CPED">CPED</option>
                <option value="Coordenação">Coordenação</option>
                <option value="Gabinete">Gabinete</option>
              </select>
            </div>

            <div class="form-group">
              <label for="resolucao-responsavel">Responsável</label>
              <input id="resolucao-responsavel" v-model="form.responsavel" type="text" placeholder="Ex: Diretoria" required />
            </div>

            <div class="form-group">
              <label for="resolucao-inicio">Início da vigência</label>
              <input id="resolucao-inicio" v-model="form.inicio" type="text" placeholder="dd/mm/aaaa" required />
            </div>

            <div class="form-group">
              <label for="resolucao-fim">Fim da vigência</label>
              <input id="resolucao-fim" v-model="form.fim" type="text" placeholder="dd/mm/aaaa" required />
            </div>

            <div class="form-group form-group-wide">
              <label for="resolucao-observacao">Observação</label>
              <textarea id="resolucao-observacao" v-model="form.observacao" rows="3" placeholder="Informações complementares da resolução"></textarea>
            </div>
          </div>

          <div class="modal-detalhes-actions">
            <button type="button" class="btn-secondary" @click="fecharModal">Cancelar</button>
            <button type="submit" class="btn-novo">Salvar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ControleDeResolucao',
  data() {
    return {
      filtros: {
        busca: '',
        unidade: '',
        status: '',
        ano: '',
      },
      filtroResumo: 'todos',
      modalAberto: false,
      modalModo: 'novo',
      resolucaoEmEdicao: null,
      form: this.criarForm(),
      registros: [
        {
          id: 1,
          numero: 'RES-2026/001',
          ano: 2026,
          assunto: 'Calendário anual de atividades do CPED',
          unidade: 'CPED',
          inicio: '01/01/2026',
          fim: '31/12/2026',
          responsavel: 'Diretoria',
          validadeAnos: 1,
        },
        {
          id: 2,
          numero: 'RES-2025/018',
          ano: 2025,
          assunto: 'Aprovação de cursos internos e ações de extensão',
          unidade: 'Coordenação',
          inicio: '15/03/2025',
          fim: '14/03/2026',
          responsavel: 'Coordenação Pedagógica',
          validadeAnos: 1,
        },
        {
          id: 3,
          numero: 'RES-2024/042',
          ano: 2024,
          assunto: 'Reestruturação da carga horária da equipe técnica',
          unidade: 'Gabinete',
          inicio: '01/04/2024',
          fim: '31/03/2025',
          responsavel: 'Gabinete',
          validadeAnos: 1,
        },
        {
          id: 4,
          numero: 'RES-2026/009',
          ano: 2026,
          assunto: 'Fluxo de aprovação de visitas técnicas',
          unidade: 'CPED',
          inicio: '10/06/2026',
          fim: '09/06/2027',
          responsavel: 'Supervisor de campo',
          validadeAnos: 1,
        },
      ],
    };
  },
  computed: {
    modalTitulo() {
      if (this.modalModo === 'novo') {
        return 'Nova resolução';
      }

      if (this.modalModo === 'editar') {
        return 'Editar resolução';
      }

      return 'Detalhes da resolução';
    },
    resolucoesComStatus() {
      return this.registros.map((item) => {
        const status = this.calcularStatus(item);

        return {
          ...item,
          status,
          statusLabel: this.labelStatus(status),
        };
      });
    },
    registrosFiltrados() {
      const busca = this.filtros.busca.trim().toLowerCase();

      return this.resolucoesComStatus.filter((item) => {
        const atendeBusca = !busca || [
          item.numero,
          item.assunto,
          item.unidade,
          item.responsavel,
        ].some((campo) => String(campo).toLowerCase().includes(busca));

        const atendeUnidade = !this.filtros.unidade || item.unidade === this.filtros.unidade;
        const atendeStatus = !this.filtros.status || item.status === this.filtros.status;
        const atendeAno = !this.filtros.ano || String(item.ano) === String(this.filtros.ano);

        return atendeBusca && atendeUnidade && atendeStatus && atendeAno;
      });
    },
    temFiltroAtivo() {
      return Boolean(
        this.filtros.busca
        || this.filtros.unidade
        || this.filtros.status
        || this.filtros.ano,
      );
    },
    resumoOptions() {
      const totais = {
        vigente: this.resolucoesComStatus.filter((item) => item.status === 'vigente').length,
        proximo: this.resolucoesComStatus.filter((item) => item.status === 'proximo').length,
        vencida: this.resolucoesComStatus.filter((item) => item.status === 'vencida').length,
      };

      return [
        { value: 'todos', label: `Todos (${this.registros.length})` },
        { value: 'vigente', label: `Vigentes (${totais.vigente})` },
        { value: 'proximo', label: `Próximos (${totais.proximo})` },
        { value: 'vencida', label: `Vencidas (${totais.vencida})` },
      ];
    },
  },
  methods: {
    criarForm() {
      return {
        numero: '',
        ano: new Date().getFullYear(),
        assunto: '',
        unidade: '',
        responsavel: '',
        inicio: '',
        fim: '',
        observacao: '',
      };
    },
    limparFiltros() {
      this.filtros = {
        busca: '',
        unidade: '',
        status: '',
        ano: '',
      };
      this.filtroResumo = 'todos';
    },
    aplicarResumoFiltro() {
      if (this.filtroResumo === 'todos') {
        this.filtros.status = '';
        return;
      }

      this.filtros.status = this.filtroResumo;
    },
    abrirNovaResolucao() {
      this.modalModo = 'novo';
      this.resolucaoEmEdicao = null;
      this.form = this.criarForm();
      this.modalAberto = true;
    },
    abrirDetalhes(item) {
      this.modalModo = 'detalhe';
      this.resolucaoEmEdicao = item;
      this.modalAberto = true;
      this.form = { ...this.criarForm(), ...item };
    },
    abrirEdicao(item) {
      this.modalModo = 'editar';
      this.resolucaoEmEdicao = item;
      this.form = { ...this.criarForm(), ...item };
      this.modalAberto = true;
    },
    fecharModal() {
      this.modalAberto = false;
      this.modalModo = 'novo';
      this.resolucaoEmEdicao = null;
      this.form = this.criarForm();
    },
    salvarResolucao() {
      const payload = {
        ...this.form,
        validadeAnos: 1,
      };

      if (this.modalModo === 'editar' && this.resolucaoEmEdicao) {
        this.registros = this.registros.map((item) => item.id === this.resolucaoEmEdicao.id ? {
          ...item,
          ...payload,
        } : item);
      } else {
        this.registros.unshift({
          id: Date.now(),
          ...payload,
        });
      }

      this.fecharModal();
    },
    excluirResolucao(item) {
      if (!item) {
        return;
      }

      const confirmado = window.confirm(`Deseja excluir a resolução ${item.numero}?`);

      if (!confirmado) {
        return;
      }

      this.registros = this.registros.filter((registro) => registro.id !== item.id);

      if (this.resolucaoEmEdicao && this.resolucaoEmEdicao.id === item.id) {
        this.fecharModal();
      }
    },
    parseDataBrasil(data) {
      if (!data) {
        return null;
      }

      const [dia, mes, ano] = String(data).split('/');
      const numeroAno = Number(ano);
      const numeroMes = Number(mes) - 1;
      const numeroDia = Number(dia);

      if (!numeroAno || !numeroMes && numeroMes !== 0 || !numeroDia) {
        return null;
      }

      return new Date(numeroAno, numeroMes, numeroDia);
    },
    diferencaDias(dataInicio, dataFim) {
      const inicio = this.parseDataBrasil(dataInicio);
      const fim = this.parseDataBrasil(dataFim);

      if (!inicio || !fim) {
        return 0;
      }

      return Math.ceil((fim - inicio) / (1000 * 60 * 60 * 24));
    },
    calcularStatus(item) {
      const dataHoje = new Date();
      const dataFim = this.parseDataBrasil(item.fim);

      if (!dataFim) {
        return 'vigente';
      }

      const diasRestantes = Math.ceil((dataFim - dataHoje) / (1000 * 60 * 60 * 24));
      const vigenciaMaximaDias = (Number(item.validadeAnos ?? 5) * 365) + 1;
      const vigenciaEmDias = this.diferencaDias(item.inicio, item.fim);

      if (diasRestantes < 0) {
        return 'vencida';
      }

      if (diasRestantes <= 90 || vigenciaEmDias > vigenciaMaximaDias) {
        return 'proximo';
      }

      return 'vigente';
    },
    labelStatus(status) {
      if (status === 'proximo') {
        return 'Próximo do vencimento';
      }

      if (status === 'vencida') {
        return 'Vencida';
      }

      return 'Vigente';
    },
    statusClasse(status) {
      if (status === 'vigente') {
        return 'status-vigente';
      }

      if (status === 'proximo') {
        return 'status-proximo';
      }

      return 'status-vencida';
    },
  },
};
</script>

<style scoped>
.controle-resolucoes-page {
  min-height: calc(100vh - 4rem);
  background: #f5f7fa;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 2.75rem;
  padding: 0 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  background: #fff;
  color: #374151;
  font-weight: 600;
  cursor: pointer;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 30;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background: rgba(17, 24, 39, 0.5);
}

.modal-detalhes {
  width: min(800px, 100%);
  max-height: 90vh;
  overflow: auto;
  border-radius: 1rem;
  background: #fff;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
}

.modal-detalhes-content {
  padding: 1.5rem;
}

.detalhe-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.detalhe-campo {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  padding: 0.8rem 0.9rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.7rem;
  background: #f9fafb;
}

.detalhe-campo-full {
  grid-column: 1 / -1;
}

.detalhe-label {
  color: #6b7280;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.detalhe-valor {
  color: #111827;
  font-size: 0.92rem;
  line-height: 1.5;
}

.modal-detalhes-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-detalhes-header h2 {
  margin: 0;
  color: #003f7d;
  font-size: 1.2rem;
}

.btn-fechar-x {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border: none;
  border-radius: 999px;
  background: #f3f4f6;
  color: #374151;
  font-size: 1.3rem;
  cursor: pointer;
}

.modal-form {
  padding: 1.5rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-group-wide {
  grid-column: 1 / -1;
}

.form-group label {
  color: #4b5563;
  font-size: 0.75rem;
  font-weight: 700;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.7rem 0.8rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  background: #fff;
  color: #111827;
  font-size: 0.875rem;
}

.form-group textarea {
  resize: vertical;
  min-height: 100px;
}

.modal-detalhes-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.25rem;
}

.filtros-panel {
  margin: 0 2rem 1.5rem;
}

.filtros-rodape {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-top: 0.9rem;
}

.filtros-resumo {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.resumo-chip {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.65rem;
  border-radius: 999px;
  background: #eef6ff;
  color: #003f7d;
  font-size: 0.72rem;
  font-weight: 600;
}

.btn-limpar-filtros {
  border: 1px solid #d1d5db;
  background: #fff;
  color: #374151;
  border-radius: 0.5rem;
  padding: 0.55rem 0.9rem;
  font-weight: 600;
  cursor: pointer;
}

.filtros-row {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 0.75rem;
}

.filtro-busca {
  position: relative;
  flex: 1 1 260px;
  min-width: 220px;
}

.filtro-busca-icon {
  position: absolute;
  top: 50%;
  left: 0.8rem;
  display: inline-flex;
  color: #9ca3af;
  transform: translateY(-50%);
  pointer-events: none;
}

.filtro-busca input {
  width: 100%;
  height: 2.5rem;
  padding: 0 0.75rem 0 2.4rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  background: #fff;
  color: #374151;
  font-size: 0.875rem;
}

.filtro-campo {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  min-width: 9.5rem;
}

.filtro-dropdown {
  min-width: 12rem;
}

.filtro-campo label {
  color: #6b7280;
  font-size: 0.72rem;
  font-weight: 600;
}

.filtro-campo select {
  height: 2.5rem;
  padding: 0 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  background: #fff;
  color: #374151;
  font-size: 0.875rem;
}

.tabela-card {
  margin: 0 2rem 2rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  background: #fff;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
  overflow: hidden;
}

.tabela-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.9rem 1.25rem;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
  color: #374151;
  font-size: 0.875rem;
  font-weight: 600;
}

.tabela-header-meta {
  color: #003f7d;
}

.tabela-vazia {
  padding: 3rem 1rem;
  color: #9ca3af;
  text-align: center;
  font-size: 0.9rem;
}

.tabela-wrap {
  overflow-x: auto;
}

.crud-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.crud-table thead {
  background: #003f7d;
  color: #fff;
}

.crud-table th {
  padding: 0.85rem 1rem;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  text-align: left;
  white-space: nowrap;
}

.crud-table td {
  padding: 0.9rem 1rem;
  border-bottom: 1px solid #eef2f7;
  color: #374151;
  vertical-align: top;
}

.crud-table tbody tr:hover {
  background: #f9fafb;
}

.meta-muted {
  color: #6b7280;
}

.assunto-texto,
.responsavel-texto {
  line-height: 1.45;
  color: #374151;
}

.tag-unidade {
  display: inline-flex;
  align-items: center;
  padding: 0.28rem 0.6rem;
  border-radius: 999px;
  background: #f3f4f6;
  color: #374151;
  font-size: 0.72rem;
  font-weight: 600;
}

.resolucao-numero {
  font-weight: 700;
  color: #003f7d;
}

.vigencia-data {
  font-weight: 600;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 1.8rem;
  padding: 0.3rem 0.65rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.status-vigente {
  background: #dcfce7;
  color: #166534;
}

.status-proximo {
  background: #fef3c7;
  color: #92400e;
}

.status-vencida {
  background: #fee2e2;
  color: #991b1b;
}

.acoes-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-link {
  border: none;
  background: transparent;
  color: #003f7d;
  font-weight: 600;
  cursor: pointer;
}

.btn-link.warning {
  color: #b45309;
}

.text-center {
  text-align: center;
}

@media (max-width: 720px) {
  .crud-top,
  .resolucoes-metrics,
  .filtros-panel,
  .tabela-card {
    margin-left: 1rem;
    margin-right: 1rem;
  }

  .header-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
