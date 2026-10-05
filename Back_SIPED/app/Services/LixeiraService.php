<?php

namespace App\Services;

use App\Models\Cadastro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Liga a auditoria aos registros que continuam excluídos (lixeira), para restaurar
 * direto da tela de Auditoria.
 */
class LixeiraService
{
    /**
     * @return array<string, array{label: string, model: class-string<Model>, titulo: string, rota: string}>
     */
    public function modulos(): array
    {
        return config('exclusao.modulos', []);
    }

    /** Chave do módulo da lixeira para uma classe gravada em cadastros.registro_tipo. */
    public function moduloDoTipo(?string $tipo): ?string
    {
        if (! $tipo || ! class_exists($tipo)) {
            return null;
        }

        foreach ($this->modulos() as $chave => $config) {
            if (is_a($tipo, $config['model'], true)) {
                return $chave;
            }
        }

        return null;
    }

    /**
     * Registros hoje excluídos, por classe gravada na auditoria.
     *
     * @return Collection<string, list<int>> registro_tipo => ids
     */
    public function excluidosPorTipo(): Collection
    {
        $tipos = Cadastro::query()
            ->where('acao', CadastroAuditoriaService::ACAO_EXCLUIR)
            ->whereNotNull('registro_tipo')
            ->distinct()
            ->pluck('registro_tipo');

        $resultado = collect();
        foreach ($tipos as $tipo) {
            $modulo = $this->moduloDoTipo($tipo);
            if (! $modulo) {
                continue;
            }
            /** @var class-string<Model> $model */
            $model = $this->modulos()[$modulo]['model'];
            $resultado[$tipo] = $model::query()->onlyTrashed()->pluck((new $model)->getKeyName())->map(fn ($id) => (int) $id)->all();
        }

        return $resultado;
    }

    /**
     * Restringe a auditoria ao último evento "excluir" de cada registro que segue excluído.
     *
     * @param  Builder<Cadastro>  $query
     */
    public function filtrarARestaurar(Builder $query): void
    {
        $excluidos = $this->excluidosPorTipo()->filter(fn (array $ids) => $ids !== []);

        $query->where('acao', CadastroAuditoriaService::ACAO_EXCLUIR)
            ->whereIn('id', $this->ultimosEventosDeExclusao())
            ->where(function (Builder $q) use ($excluidos) {
                if ($excluidos->isEmpty()) {
                    $q->whereRaw('1 = 0');

                    return;
                }
                foreach ($excluidos as $tipo => $ids) {
                    $q->orWhere(fn (Builder $par) => $par->where('registro_tipo', $tipo)->whereIn('registro_id', $ids));
                }
            });
    }

    /**
     * Marca, nos itens da página, quais podem ser restaurados agora.
     *
     * @param  iterable<Cadastro>  $itens
     * @return list<array<string, mixed>>
     */
    public function anotar(iterable $itens): array
    {
        $itens = collect($itens);
        $exclusoes = $itens->where('acao', CadastroAuditoriaService::ACAO_EXCLUIR);
        $excluidos = $exclusoes->isEmpty() ? collect() : $this->excluidosPorTipo();
        $ultimos = $exclusoes->isEmpty()
            ? []
            : array_flip(Cadastro::query()
                ->whereIn('id', $this->ultimosEventosDeExclusao())
                ->whereIn('id', $exclusoes->pluck('id'))
                ->pluck('id')
                ->all());

        return $itens->map(function (Cadastro $item) use ($excluidos, $ultimos) {
            $dados = $item->toArray();
            $modulo = $this->moduloDoTipo($item->registro_tipo);
            $dados['restauravel'] = $item->acao === CadastroAuditoriaService::ACAO_EXCLUIR
                && $modulo !== null
                && isset($ultimos[$item->id])
                && in_array((int) $item->registro_id, $excluidos[$item->registro_tipo] ?? [], true);
            $dados['modulo_lixeira'] = $dados['restauravel'] ? $modulo : null;

            return $dados;
        })->values()->all();
    }

    /** Subconsulta: id do evento de exclusão mais recente de cada registro. */
    private function ultimosEventosDeExclusao(): Builder
    {
        return Cadastro::query()
            ->selectRaw('MAX(id)')
            ->where('acao', CadastroAuditoriaService::ACAO_EXCLUIR)
            ->whereNotNull('registro_id')
            ->groupBy('registro_tipo', 'registro_id');
    }
}
