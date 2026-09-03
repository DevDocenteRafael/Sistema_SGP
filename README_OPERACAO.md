# Operação do SGP (estabilidade e disco)

Documento operacional alinhado à especificação de estabilidade Laravel + MySQL (sem Docker).

## Stack

| Camada | Tecnologia |
|--------|------------|
| API | Laravel 12 + PHP 8.2, MySQL |
| Auth | Sanctum (token Bearer no MySQL) |
| Front | Vue 3 + Vite (dev) / `npm run build` (homolog/produção) |

## Desenvolvimento local

1. XAMPP: MySQL iniciado, banco `SGP` criado.
2. Na raiz do repositório: `local-start.cmd`
3. Front: http://127.0.0.1:5173/login  
   API: http://127.0.0.1:8000

O script valida espaço livre no disco da aplicação **antes** de subir os serviços. Em aviso/crítico ele informa e pergunta se deseja continuar; **não apaga arquivos**.

### Login demo (seed)

Ver mensagens do `local-start.cmd` após o seed (ex.: administrador demo).

## Configurações recomendadas (`.env`)

Espelhe o [`.env.example`](SGP_Back/.env.example):

```env
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DAILY_DAYS=7
CACHE_STORE=database
```

- **Logs diários:** evita `laravel.log` único sem retenção. Arquivos antigos além de `LOG_DAILY_DAYS` são descartados pelo canal `daily`.
- **Cache no MySQL:** reduz escrita em `storage/framework/cache` no disco C:. A tabela `cache` já existe via migration.
- **Sessão:** permanece `SESSION_DRIVER=file` — a API autentica por token Sanctum; não é necessário `database` para o login funcionar.
- **Produção:** `APP_DEBUG=false` e `LOG_LEVEL=warning` (ou `error`).

Após alterar o `.env`:

```bat
cd SGP_Back
php artisan config:clear
```

## Health check

```http
GET /up
```

- `200` — aplicação operacional (endpoint minimalista do Laravel).
- `5xx` — indisponível.

Não expõe host de banco, `.env`, caminhos ou stack trace.

Complemento (local/interno):

```bat
cd SGP_Back
php artisan sgp:check-disk
php artisan sgp:check-disk --json
php artisan sgp:check-disk --warn-gb=100 --crit-gb=50
```

## Monitor externo de disco (Windows)

O Laravel pode já estar morto quando o disco enche. Use o script **fora** da aplicação:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\windows\check-disk.ps1
powershell -ExecutionPolicy Bypass -File scripts\windows\check-disk.ps1 -Drives C:,D: -Json
```

| Exit code | Significado |
|-----------|-------------|
| 0 | OK |
| 1 | AVISO (padrão: menos de 15% **ou** menos de 15 GB livres) |
| 2 | CRÍTICO (padrão: menos de 8% **ou** menos de 8 GB) / erro de leitura |

Limiares configuráveis via parâmetros do script ou variáveis `SGP_DISK_*` / `config/system_health.php`.

**Não** use limpeza automática agressiva. Em alerta: liberar Temp/Downloads/caches manualmente e investigar MySQL/logs.

Agendar no Agendador de Tarefas do Windows é recomendado (ação humana ao falhar).

## Homologação / produção

- Não dependa de `npm run dev` nem de `php artisan serve` como servidor definitivo.
- Front: `cd SGP_Front && npm run build` e servir os estáticos pelo servidor web.
- API: IIS/Apache/Nginx + PHP-FPM (ou equivalente Windows), apontando para `SGP_Back/public`.

## MySQL e disco

Trocar cache/logs **não** resolve se o datadir do MySQL estiver no C: cheio.

Diagnóstico (manual, no cliente MySQL):

```sql
SHOW VARIABLES LIKE 'datadir';
```

Se o datadir estiver no C: e existir outra unidade com espaço (ex.: D:), planejar migração com **backup verificado**, janela e rollback. **Não** mover datadir automaticamente por script desta aplicação.

## Rollback das mudanças de configuração

| Mudança | Rollback |
|---------|----------|
| `LOG_STACK=daily` | Voltar `LOG_STACK=single` no `.env` + `config:clear` |
| `CACHE_STORE=database` | Voltar `CACHE_STORE=file` + `config:clear` |
| Comando / script de disco | Remover agendamento; arquivos em `scripts/windows` e `app/Console/Commands` são só leitura de métricas |

Nenhuma migration destrutiva foi adicionada neste pacote de estabilidade (tabelas `cache`/`sessions` já existiam).

## Riscos remanescentes

1. Disco C: quase cheio continua sendo o risco principal (MySQL + SO + processos).
2. Processos de desenvolvimento caem se o SO ficar sem espaço.
3. PDFs/relatórios grandes ainda usam memória/CPU; exports devem evitar acumular em `storage` sem política.

## Melhorias opcionais (fora do escopo atual)

- Mover projeto / `vendor` / `node_modules` / datadir MySQL para D:.
- Deploy com build do Vite e servidor web real.
- Alertas por e-mail/webhook a partir do exit code do `check-disk.ps1`.
