<?php

namespace App\Services;

use App\Models\Cadastro;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CadastroAuditoriaService
{
    public const ACAO_CRIAR = 'criar';

    public const ACAO_EDITAR = 'editar';

    public const ACAO_EXCLUIR = 'excluir';

    public const ACAO_IMPORTAR = 'importar';

    /**
     * @param  array<string, mixed>|null  $dados
     */
    public function registrar(
        string $acao,
        string $modulo,
        ?Model $registro = null,
        ?string $resumo = null,
        ?array $dados = null,
        ?Usuario $usuario = null,
    ): Cadastro {
        $usuario = $usuario ?? Auth::user();
        $request = request();

        return Cadastro::query()->create([
            'usuario_id' => $usuario?->id,
            'acao' => $acao,
            'modulo' => $modulo,
            'registro_tipo' => $registro ? $registro::class : null,
            'registro_id' => $registro?->getKey(),
            'resumo' => $resumo ?? $this->resumoPadrao($acao, $modulo, $registro),
            'dados' => $dados,
            'ip' => $request instanceof Request ? $request->ip() : null,
            'user_agent' => $request instanceof Request
                ? mb_substr((string) $request->userAgent(), 0, 500)
                : null,
        ]);
    }

    public function registrarModelo(string $acao, Model $registro, ?string $resumo = null, ?array $dados = null): Cadastro
    {
        return $this->registrar(
            $acao,
            $this->moduloDoModelo($registro),
            $registro,
            $resumo,
            $dados,
        );
    }

    public function moduloDoModelo(Model $registro): string
    {
        if (property_exists($registro, 'moduloAuditoria') && is_string($registro->moduloAuditoria)) {
            return $registro->moduloAuditoria;
        }

        return match ($registro::class) {
            \App\Models\Curso::class => 'cursos',
            \App\Models\PlanoDeMeta::class => 'plano-de-metas',
            \App\Models\Pca::class => 'pcas',
            \App\Models\CursoPorEixo::class => 'eixos',
            \App\Models\VisitaTecnica::class => 'visitas-tecnicas',
            \App\Models\HoraPedagogica::class => 'horas-pedagogicas',
            \App\Models\AcaoExtensiva::class => 'acoes-extensivas',
            \App\Models\Evento::class => 'eventos',
            \App\Models\TermoReferencia::class => 'termos-referencia',
            \App\Models\Resolucao::class => 'resolucoes',
            \App\Models\CpedEquipe::class => 'cped-equipes',
            \App\Models\Fluxograma::class => 'fluxogramas',
            \App\Models\KanbanCartao::class => 'kanban',
            \App\Models\KanbanQuadro::class => 'kanban',
            \App\Models\Usuario::class => 'usuarios',
            default => class_basename($registro),
        };
    }

    public function rotuloDoModelo(Model $registro): string
    {
        foreach (['titulo', 'nome', 'assunto', 'curso', 'email', 'slug'] as $campo) {
            $valor = $registro->getAttribute($campo);
            if (is_string($valor) && trim($valor) !== '') {
                return trim($valor);
            }
        }

        return class_basename($registro).' #'.$registro->getKey();
    }

    private function resumoPadrao(string $acao, string $modulo, ?Model $registro): string
    {
        $rotulo = $registro ? $this->rotuloDoModelo($registro) : $modulo;

        return match ($acao) {
            self::ACAO_CRIAR => "Cadastrou {$rotulo}",
            self::ACAO_EDITAR => "Atualizou {$rotulo}",
            self::ACAO_EXCLUIR => "Excluiu {$rotulo}",
            self::ACAO_IMPORTAR => "Importou dados de {$modulo}",
            default => ucfirst($acao).' em '.$modulo,
        };
    }
}
