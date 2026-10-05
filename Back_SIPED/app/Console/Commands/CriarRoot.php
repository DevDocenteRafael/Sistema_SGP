<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use App\Services\CadastroAuditoriaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Cria (ou promove) o usuário Root por procedimento seguro: sem senha fixa em seed
 * ou no repositório. A senha é digitada no terminal ou gerada e exibida uma única vez.
 */
class CriarRoot extends Command
{
    protected $signature = 'siped:criar-root
        {email : E-mail do Root}
        {--nome= : Nome completo}
        {--cpf= : CPF (somente números)}
        {--unidade= : Estrutura institucional}
        {--gerar-senha : Gera uma senha aleatória em vez de perguntar}';

    protected $description = 'Cria ou promove o usuário Root do SIPED (sem senha fixa no código)';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Informe um e-mail válido.');

            return self::FAILURE;
        }

        $existente = Usuario::query()->where('email', $email)->first();

        if ($this->option('gerar-senha')) {
            $senha = Str::password(16, symbols: false);
        } else {
            $senha = (string) $this->secret('Senha do Root (mínimo 12 caracteres)');
            if (mb_strlen($senha) < 12 || $senha !== (string) $this->secret('Confirme a senha')) {
                $this->error('Senha não confere ou tem menos de 12 caracteres.');

                return self::FAILURE;
            }
        }

        $dados = [
            'perfil' => Usuario::PERFIL_ROOT,
            'status' => true,
            'senha' => Hash::make($senha),
        ];

        if ($existente) {
            $existente->forceFill($dados)->save();
            $existente->tokens()->delete();
            $usuario = $existente;
            $this->info("Usuário {$email} promovido a Root.");
        } else {
            $usuario = Usuario::query()->forceCreate($dados + [
                'email' => $email,
                'nome' => (string) ($this->option('nome') ?: 'Root SIPED'),
                'cpf' => $this->option('cpf') ? preg_replace('/\D/', '', (string) $this->option('cpf')) : null,
                'unidade' => $this->option('unidade') ?: null,
                'area' => 'Administração do sistema',
            ]);
            $this->info("Usuário Root {$email} criado.");
        }

        app(CadastroAuditoriaService::class)->registrar(
            CadastroAuditoriaService::ACAO_CRIAR,
            'usuarios',
            $usuario,
            'Root definido pelo comando siped:criar-root',
            null,
            null,
        );

        if ($this->option('gerar-senha')) {
            $this->warn('Senha gerada (exibida só agora, guarde em local seguro e troque no primeiro acesso):');
            $this->line($senha);
        }

        return self::SUCCESS;
    }
}
