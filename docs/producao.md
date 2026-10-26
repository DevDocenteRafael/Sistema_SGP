# Checklist de produção e homologação

Este checklist complementa o comando `php artisan siped:verificar-producao`. O comando é somente uma verificação automática e não aprova publicação; avisos e itens manuais precisam ser avaliados pela equipe responsável.

## Antes de preparar o ambiente

- Definir responsáveis, janela de implantação, plano de reversão e canal de suporte.
- Confirmar que o domínio, DNS, TLS, proxy reverso e servidor web são autorizados e controlados pela equipe.
- Criar banco e credenciais dedicadas com privilégio mínimo. Não reutilizar `.env` local nem credenciais de demonstração.
- Manter `.env`, `APP_KEY`, senha do banco e token SVT fora do Git e de logs.
- Configurar `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `LOG_LEVEL=warning` ou `error`, `FORCE_HTTPS=true` e `SESSION_SECURE_COOKIE=true`.
- Definir `CORS_ALLOWED_ORIGINS` apenas com origens HTTPS aprovadas do frontend. Não usar origem curinga.
- Configurar `TRUSTED_PROXIES` somente se houver proxy conhecido e com endereços confirmados.
- Definir `DB_*`, filesystem, acesso de escrita em `storage/` e `bootstrap/cache/`, limites de API, `BACKUP_PASTA` fora de `public/` e o caminho de `BACKUP_MYSQLDUMP` quando necessário.
- Habilitar o token do SVT somente se a integração estiver formalmente aprovada; use token aleatório longo, compartilhado por canal seguro.

## Preparação da versão

1. Revisar branch/commit e aprovações de código.
2. Instalar dependências conforme lockfiles: `composer install --no-dev --optimize-autoloader` no backend e `npm ci` no frontend.
3. Executar a suíte PHP (`php artisan test`) e o build (`npm run build`) em ambiente isolado.
4. Configurar `.env` no alvo autorizado e confirmar o destino antes de qualquer migração.
5. Criar backup pré-implantação em destino restrito, fora do diretório público.
6. Aplicar migrations em janela aprovada com `php artisan migrate --force`.
7. Publicar `Back_SIPED/public` como document root. Nunca publicar a raiz do repositório.
8. Garantir HTTPS, permissões mínimas para o usuário do servidor, storage link e acesso aos assets de `public/build/siped`.
9. Criar o primeiro Root com `php artisan siped:criar-root email@dominio --gerar-senha`; não usar senha compartilhada.
10. Configurar agendamento Laravel, filas/serviços se habilitados, monitoração, rotação de logs e cópias de backup.

## Verificação automática

No alvo de homologação aprovado, execute:

```cmd
php artisan siped:verificar-producao
php artisan siped:verificar-producao --json
```

O comando verifica ambiente e debug, chave, logs, HTTPS, CORS, cookie seguro, conexão e migrations do banco, Roots ativos, contas de demonstração, build, permissões, localização de documentos e presença de backup recente. Código de saída zero indica ausência de falhas automáticas, não aprovação completa. `AVISO` requer revisão; `FALHA` bloqueia o go-live até correção.

Após a publicação, validar de forma controlada `GET /up`, login/logout, permissões por perfil, operações essenciais, anexos, relatórios e logs. Use contas e dados de homologação; não rode seeders de demonstração em produção.

## Backup e restauração

O comando `php artisan siped:backup` gera dump MySQL/MariaDB usando `mysqldump` ou cópia SQLite e remove arquivos além da retenção configurada. Destino padrão: `storage/app/backups`; configure uma localização protegida, com espaço, acesso restrito e política de cópia externa aprovada. O agendamento usa `BACKUP_AGENDADO` e `BACKUP_HORARIO`; o scheduler Laravel deve ser invocado pelo agendador do servidor, normalmente a cada minuto.

A equipe deve confirmar que o arquivo gerado é íntegro e que a restauração foi ensaiada em banco isolado, nunca sobre o banco original. Para MySQL, um ensaio de restauração pode usar `mysql` contra um banco descartável e vazio, com credenciais de teste e sem expor senha na linha de comando. Validar contagens e operações da aplicação antes de considerar o ensaio concluído.

## Evidências e aprovação

Registrar commit implantado, saída do verificador, resultado de testes, versão PHP/Node, status de migrations, teste de backup/restauração, verificações funcionais e responsáveis/aprovadores. Não registrar segredos ou dados pessoais nesses artefatos.

A aprovação para homologação/produção, janela, infraestrutura, política de retenção e recuperação de desastre são decisões internas e não podem ser concluídas apenas pelo repositório.
