<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Database\Seeders\UsuarioSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Throwable;

/**
 * Checklist automático de GO/NO-GO para produção (SPEC 08).
 * FALHA bloqueia o go-live; AVISO precisa ser conferido. Itens manuais: docs/producao.md.
 */
class VerificarProducao extends Command
{
    protected $signature = 'siped:verificar-producao {--json : Saída em JSON}';

    protected $description = 'Confere configuração, banco, acessos e operação antes de colocar o SIPED em produção';

    private const OK = 'OK';

    private const AVISO = 'AVISO';

    private const FALHA = 'FALHA';

    /** @var list<array{situacao: string, item: string, detalhe: string}> */
    private array $itens = [];

    public function handle(): int
    {
        $this->ambiente();
        $this->https();
        $this->banco();
        $this->acessos();
        $this->operacao();

        $falhas = collect($this->itens)->where('situacao', self::FALHA)->count();
        $avisos = collect($this->itens)->where('situacao', self::AVISO)->count();

        if ($this->option('json')) {
            $this->line(json_encode(['go' => $falhas === 0, 'falhas' => $falhas, 'avisos' => $avisos, 'itens' => $this->itens],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Situação', 'Item', 'Detalhe'], array_map(fn ($i) => array_values($i), $this->itens));
            $falhas === 0
                ? $this->info("GO: nenhuma falha ({$avisos} aviso(s) para conferir).")
                : $this->error("NO-GO: {$falhas} falha(s) e {$avisos} aviso(s). Corrija as falhas antes do go-live.");
        }

        return $falhas === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function item(string $situacao, string $item, string $detalhe = ''): void
    {
        $this->itens[] = ['situacao' => $situacao, 'item' => $item, 'detalhe' => $detalhe];
    }

    private function checar(bool $ok, string $item, string $falha, string $situacaoSeFalhar = self::FALHA): void
    {
        $this->item($ok ? self::OK : $situacaoSeFalhar, $item, $ok ? '' : $falha);
    }

    private function ambiente(): void
    {
        $this->checar(app()->isProduction(), 'APP_ENV=production', 'APP_ENV está "'.config('app.env').'".');
        $this->checar(! config('app.debug'), 'APP_DEBUG=false', 'Com APP_DEBUG=true a API expõe detalhes internos dos erros.');
        $this->checar(filled(config('app.key')), 'APP_KEY definida', 'Rode php artisan key:generate.');

        $nivel = (string) config('logging.channels.'.config('logging.default').'.level', config('logging.channels.daily.level', 'debug'));
        $canais = (array) config('logging.channels.stack.channels', []);
        $diario = config('logging.default') === 'daily' || in_array('daily', $canais, true);
        $this->checar($diario, 'Log com rotação diária', 'Use LOG_CHANNEL=stack e LOG_STACK=daily.', self::AVISO);
        $nivelDaily = (string) config('logging.channels.daily.level', $nivel);
        $this->checar(! in_array($nivelDaily, ['debug', 'info'], true), 'LOG_LEVEL de produção', "LOG_LEVEL está \"{$nivelDaily}\"; use warning ou error.", self::AVISO);
    }

    private function https(): void
    {
        $https = fn ($url) => is_string($url) && str_starts_with(mb_strtolower($url), 'https://');
        $this->checar($https(config('app.url')), 'APP_URL com https', 'APP_URL: '.config('app.url'));
        $this->checar((bool) config('producao.forcar_https'), 'HTTPS obrigatório (FORCE_HTTPS)', 'FORCE_HTTPS está desligado.');

        $origens = (array) config('cors.allowed_origins', []);
        $locais = array_filter($origens, fn ($o) => preg_match('#localhost|127\.0\.0\.1#i', (string) $o));
        $this->checar($origens !== [] && $locais === [], 'CORS só com o domínio real',
            $origens === [] ? 'Nenhuma origem liberada.' : 'Origens locais liberadas: '.implode(', ', $locais).'. Defina CORS_ALLOWED_ORIGINS.', self::AVISO);
        $this->checar((bool) config('session.secure'), 'Cookie de sessão seguro', 'Defina SESSION_SECURE_COOKIE=true.', self::AVISO);
    }

    private function banco(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->item(self::FALHA, 'Conexão com o banco', mb_substr($e->getMessage(), 0, 160));

            return;
        }
        $this->checar(DB::connection()->getDriverName() !== 'sqlite', 'Banco de produção (não SQLite)', 'O banco configurado é SQLite.');

        $migrator = app('migrator');
        $pendentes = [];
        if ($migrator->repositoryExists()) {
            $feitas = $migrator->getRepository()->getRan();
            $arquivos = array_keys($migrator->getMigrationFiles([database_path('migrations')]));
            $pendentes = array_values(array_diff($arquivos, $feitas));
        }
        $this->checar($migrator->repositoryExists() && $pendentes === [], 'Migrations aplicadas',
            $migrator->repositoryExists() ? count($pendentes).' pendente(s). Rode php artisan migrate --force.' : 'Tabela de migrations não existe.');
    }

    private function acessos(): void
    {
        try {
            $roots = Usuario::query()->where('perfil', Usuario::PERFIL_ROOT)->where('status', true)->count();
        } catch (Throwable) {
            return;
        }
        $this->checar($roots > 0, 'Usuário Root ativo', 'Crie com php artisan siped:criar-root email@df.senac.br.');
        if ($roots > 0) {
            // Só o Root gerencia usuários: com um único Root, férias ou senha esquecida travam os acessos.
            $this->checar($roots >= 2, 'Pelo menos dois Roots ativos',
                'Há só 1 Root ativo. Cadastre um segundo para não depender de uma única pessoa.', self::AVISO);
        }

        $comSenhaDemo = [];
        foreach (UsuarioSeeder::demonstracao() as $demo) {
            $usuario = Usuario::query()->where('email', $demo['email'])->where('status', true)->first();
            if ($usuario && Hash::check($demo['senha'], (string) $usuario->senha)) {
                $comSenhaDemo[] = $demo['email'];
            }
        }
        $this->checar($comSenhaDemo === [], 'Sem usuários de demonstração com senha conhecida',
            'Inative ou troque a senha de: '.implode(', ', $comSenhaDemo).'.');

        $ficticios = Usuario::query()->where('email', 'like', 'usuario.seed%@df.senac.br')->where('status', true)->count();
        $this->checar($ficticios === 0, 'Sem usuários fictícios do seeder', "{$ficticios} usuário(s) usuario.seedN@df.senac.br ativos.");

        $token = config('svt.token');
        if (filled($token)) {
            $this->checar(mb_strlen((string) $token) >= 32, 'Token do SVT forte', 'SVT_INTEGRACAO_TOKEN com menos de 32 caracteres.');
        }
    }

    private function operacao(): void
    {
        $this->checar(is_file(public_path('build/siped/index.html')), 'Front-end compilado', 'Rode npm run build em Front_SIPED.');
        $this->checar(is_writable(storage_path('logs')) && is_writable(storage_path('app')), 'storage/ com permissão de escrita',
            'O PHP não consegue gravar em storage/.');
        $this->checar(! is_dir(public_path('storage/documentos')), 'Documentos fora da pasta pública',
            'Há documentos em public/storage/documentos.');

        $pasta = (string) config('producao.backup.pasta');
        $recente = is_dir($pasta) ? collect(File::files($pasta))
            ->filter(fn ($f) => str_starts_with($f->getFilename(), 'siped-'))
            ->contains(fn ($f) => $f->getMTime() >= now()->subDays(2)->getTimestamp()) : false;
        $this->checar($recente, 'Backup recente (até 2 dias)', "Nenhum backup em {$pasta}. Rode php artisan siped:backup.", self::AVISO);
    }
}
