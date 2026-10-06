<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') — SIPED</title>
    <style>
        :root { --fundo: #f3f6fa; --cartao: #fff; --texto: #1f2937; --suave: #6b7280; --marca: #003f7d; }
        @media (prefers-color-scheme: dark) {
            :root { --fundo: #0f172a; --cartao: #1e293b; --texto: #e5e7eb; --suave: #94a3b8; --marca: #7cb4f0; }
        }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; box-sizing: border-box;
               background: var(--fundo); color: var(--texto); font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { max-width: 440px; width: 100%; padding: 2rem; border-radius: 12px; background: var(--cartao);
               box-shadow: 0 10px 30px rgba(0, 0, 0, .08); text-align: center; }
        .codigo { margin: 0; color: var(--marca); font-size: 2.6rem; font-weight: 800; }
        h1 { margin: .4rem 0; font-size: 1.2rem; }
        p { margin: .4rem 0 1.2rem; color: var(--suave); line-height: 1.5; }
        a { display: inline-block; padding: .6rem 1.1rem; border-radius: 8px; background: var(--marca); color: #fff;
            text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<main>
    <p class="codigo">@yield('codigo')</p>
    <h1>@yield('titulo')</h1>
    <p>@yield('mensagem')</p>
    <a href="{{ url('/') }}">Voltar ao SIPED</a>
</main>
</body>
</html>
