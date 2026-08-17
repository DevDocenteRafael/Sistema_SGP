<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        @page { margin: 18px 22px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2937;
        }
        .header {
            border-bottom: 2px solid #003F7D;
            padding-bottom: 10px;
            margin-bottom: 14px;
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
            margin: 0 0 12px;
            padding: 8px 10px;
            background: #f3f4f6;
            border-radius: 4px;
        }
        .meta span {
            display: inline-block;
            margin-right: 14px;
            color: #374151;
        }
        .meta strong {
            color: #111827;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #003F7D;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
        }
        tr:nth-child(even) td {
            background: #f9fafb;
        }
        .empty {
            text-align: center;
            color: #6b7280;
            padding: 24px;
        }
        .footer {
            margin-top: 12px;
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
        <h1>{{ $titulo }}</h1>
        <p>{{ $descricao }}</p>
    </div>

    <div class="meta">
        <span><strong>Total:</strong> {{ $total }} registro(s)</span>
        <span><strong>Emitido em:</strong> {{ $emitidoEm }}</span>
        @if (!empty($usuario))
            <span><strong>Por:</strong> {{ $usuario }}</span>
        @endif
        @forelse ($filtros as $chave => $valor)
            <span><strong>{{ ucfirst($chave) }}:</strong> {{ $valor }}</span>
        @empty
            <span><strong>Filtros:</strong> nenhum</span>
        @endforelse
    </div>

    @if ($registros->isEmpty())
        <p class="empty">Nenhum registro encontrado para os filtros selecionados.</p>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($colunas as $coluna)
                        <th>{{ $coluna['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($registros as $linha)
                    <tr>
                        @foreach ($colunas as $coluna)
                            <td>{{ $linha[$coluna['key']] ?? '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Documento gerado automaticamente pelo Sistema de Gerenciamento de Portfólio (SGP).
    </div>
</body>
</html>
