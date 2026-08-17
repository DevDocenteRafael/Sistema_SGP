<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Jornada Pedagógica — {{ $jornada['titulo'] }}</title>
    <style>
        @page { margin: 22px 24px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
        }
        .header {
            border-bottom: 2px solid #003F7D;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .header-kicker {
            color: #003F7D;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            margin: 0 0 4px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #003F7D;
        }
        .header p {
            margin: 4px 0 0;
            color: #4b5563;
        }
        .meta {
            margin: 0 0 14px;
            padding: 8px 10px;
            background: #f3f4f6;
            border-radius: 4px;
        }
        .meta span {
            display: inline-block;
            margin-right: 14px;
            color: #374151;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            width: 28%;
            background: #eff6ff;
            color: #003F7D;
            font-size: 10px;
            text-transform: uppercase;
        }
        .bloco {
            margin-top: 12px;
        }
        .bloco h2 {
            margin: 0 0 6px;
            font-size: 12px;
            color: #003F7D;
        }
        .bloco p {
            margin: 0;
            white-space: pre-wrap;
            line-height: 1.45;
        }
        .footer {
            margin-top: 16px;
            font-size: 9px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="header-kicker">SENAC DF · CPED · SGP</p>
        <h1>{{ $jornada['titulo'] }}</h1>
        <p>Plano documental da Jornada Pedagógica</p>
    </div>

    <div class="meta">
        <span><strong>Status:</strong> {{ $jornada['status'] ?? '—' }}</span>
        <span><strong>Emitido em:</strong> {{ $emitidoEm }}</span>
        @if (!empty($usuario))
            <span><strong>Por:</strong> {{ $usuario }}</span>
        @endif
    </div>

    <table>
        <tr>
            <th>Período</th>
            <td>
                {{ $jornada['data_inicio'] ? \Carbon\Carbon::parse($jornada['data_inicio'])->format('d/m/Y') : '—' }}
                até
                {{ $jornada['data_fim'] ? \Carbon\Carbon::parse($jornada['data_fim'])->format('d/m/Y') : '—' }}
            </td>
        </tr>
        <tr>
            <th>Pré-jornada</th>
            <td>
                {{ $jornada['tem_pre_jornada'] ?? 'Não' }}
                @if (!empty($jornada['data_pre_jornada']))
                    — {{ \Carbon\Carbon::parse($jornada['data_pre_jornada'])->format('d/m/Y') }}
                @endif
            </td>
        </tr>
        <tr>
            <th>Local</th>
            <td>{{ $jornada['local'] ?: '—' }}</td>
        </tr>
        <tr>
            <th>Espaço</th>
            <td>{{ $jornada['espaco'] ?: '—' }}</td>
        </tr>
        <tr>
            <th>Verba</th>
            <td>{{ $jornada['verba'] ?: '—' }}</td>
        </tr>
        <tr>
            <th>Setores</th>
            <td>{{ $jornada['setores'] ?: '—' }}</td>
        </tr>
    </table>

    <div class="bloco">
        <h2>Custos</h2>
        <p>{{ $jornada['custos'] ?: '—' }}</p>
    </div>

    <div class="bloco">
        <h2>Programação</h2>
        <p>{{ $jornada['programacao'] ?: '—' }}</p>
    </div>

    <div class="bloco">
        <h2>Observações</h2>
        <p>{{ $jornada['observacoes'] ?: '—' }}</p>
    </div>

    <div class="footer">
        Documento gerado automaticamente pelo Sistema de Gerenciamento de Portfólio (SGP).
    </div>
</body>
</html>
