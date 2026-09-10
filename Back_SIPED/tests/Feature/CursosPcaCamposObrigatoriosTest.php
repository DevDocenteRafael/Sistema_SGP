<?php

namespace Tests\Feature;

use App\Http\Requests\CursoRequest;
use App\Http\Requests\PcaRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CursosPcaCamposObrigatoriosTest extends TestCase
{
    use RefreshDatabase;

    public function test_curso_rejeita_todos_os_campos_do_formulario_vazios(): void
    {
        $request = new CursoRequest;
        $campos = [
            'ciclo_id', 'titulo', 'eixo', 'segmento', 'programa', 'modalidade', 'carga_horaria',
            'turmas', 'codigo_processo', 'alunos', 'instrutor', 'descricao', 'codigo_dn', 'codigo_sig',
            'identificacao', 'status', 'ultima_revisao', 'processo_sei', 'data_inicio', 'data_fim',
            'unidade', 'unidades_oferta', 'observacoes', 'valores', 'compativel_bolsa', 'comercial', 'pcn', 'pcr',
        ];
        $this->verificarCamposVazios($request, $campos);
    }

    public function test_pca_rejeita_todos_os_campos_do_formulario_vazios(): void
    {
        $request = new PcaRequest;
        $campos = [
            'titulo', 'semestre', 'numero_sei', 'codigo_sig', 'eixo', 'unidade', 'carga_horaria',
            'precificacao', 'valor_primeiro_modulo', 'valor', 'parcelas_boleto', 'valor_parcela_boleto',
            'parcelas_cartao', 'valor_cartao', 'parcela_desc_20', 'parcela_desc_15', 'status', 'observacao', 'ano',
        ];
        $this->verificarCamposVazios($request, $campos);
    }

    private function verificarCamposVazios($request, array $campos): void
    {
        foreach ([[], array_fill_keys($campos, ''), array_fill_keys($campos, null)] as $dados) {
            $validator = Validator::make($dados, $request->rules(), $request->messages());
            $this->assertTrue($validator->fails());
            foreach ($campos as $campo) {
                $this->assertArrayHasKey('Required', $validator->failed()[$campo], $campo);
                $this->assertSame($request->messages()[$campo.'.required'], $validator->errors()->first($campo));
            }
        }
    }
}
