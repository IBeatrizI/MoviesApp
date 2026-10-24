<!doctype html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b0d14">
    <meta name="description" content="Cinemateca — descobre filmes e séries por género, explora sinopses e guarda os teus favoritos.">
    <title>Cinemateca | O teu universo de filmes e séries</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/catalog.css') }}">
    <script src="{{ asset('assets/js/catalog.js') }}" defer></script>
</head>
<body>
    <section id="auth" class="auth-layout" aria-labelledby="auth-title">
        <div class="auth-art">
            <div class="auth-art__inner">
                <a class="brand brand--large" href="/" aria-label="Cinemateca, página inicial"><span class="brand__icon" aria-hidden="true">▶</span><span>CINEMA<span class="brand__accent">TECA</span></span></a>
                <span class="eyebrow"><span class="live-dot"></span> A tua próxima história começa aqui</span>
                <h1 id="auth-title">Tudo o que adoras ver.<br><em>Num só lugar.</em></h1>
                <p>Descobre novas histórias, explora géneros e cria a tua lista de favoritos. Cinema e televisão à tua maneira.</p>
                <div class="auth-art__tags" aria-hidden="true"><span>✦ Cinema</span><span>◉ Séries</span><span>♡ Favoritos</span></div>
            </div>
            <div class="auth-orb auth-orb--one"></div><div class="auth-orb auth-orb--two"></div>
            <div class="auth-reel" aria-hidden="true">✳</div>
            <small class="auth-credit">A tua coleção. O teu cinema.</small>
        </div>
        <div class="auth-side">
            <div class="auth-panel">
                <div class="auth-welcome"><span class="eyebrow">BEM-VINDO À CINEMATECA</span><h2>Entra no teu universo.</h2><p>Acede à tua lista de filmes e séries preferidos.</p></div>
                <div class="auth-tabs" role="tablist" aria-label="Autenticação">
                    <button type="button" id="aba-entrar" role="tab" aria-selected="true">Iniciar sessão</button>
                    <button type="button" id="aba-criar" role="tab" aria-selected="false">Criar conta</button>
                </div>
                <form id="form-auth" novalidate>
                    <label id="l-email" hidden>Email<input id="email" type="email" autocomplete="email" placeholder="exemplo@email.com"></label>
                    <label><span id="l-user">Nome de utilizador ou email</span><input id="username" type="text" autocomplete="username" required placeholder="O teu utilizador"></label>
                    <label>Palavra-passe<input id="password" type="password" autocomplete="current-password" required placeholder="Introduz a palavra-passe"></label>
                    <p id="erro-auth" class="form-error" role="alert" aria-live="polite"></p>
                    <button class="btn btn--primary btn--full" id="submeter" type="submit">Entrar <span aria-hidden="true">→</span></button>
                </form>
                <p class="auth-footnote">A Cinemateca usa dados e imagens da TMDB, sem afiliação com este serviço.</p>
            </div>
        </div>
    </section>

    <div id="app" hidden>
        <header class="site-header">
            <div class="header-inner">
                <div class="header-first"><a class="brand" href="/" aria-label="Cinemateca"><span class="brand__icon" aria-hidden="true">▶</span><span>CINEMA<span class="brand__accent">TECA</span></span></a>
                    <nav class="main-nav" aria-label="Navegação principal"><button id="aba-filmes" class="nav-link" aria-current="page" type="button">Filmes</button><button id="aba-series" class="nav-link" type="button">Séries</button><button id="aba-favoritos" class="nav-link" type="button">A minha lista</button></nav>
                </div>
                <div class="header-actions">
                    <label class="language-select"><span class="sr-only">Idioma do catálogo</span><select id="idioma"><option value="pt">PT</option><option value="en">EN</option><option value="es">ES</option></select></label>
                    <span class="user-pill" title="Sessão atual"><span class="user-pill__avatar" id="avatar" aria-hidden="true">U</span><span id="nome"></span></span>
                    <button id="sair" class="logout" type="button" title="Terminar sessão">Sair <span aria-hidden="true">↗</span></button>
                </div>
            </div>
        </header>
        <main class="page-wrap">
            <section class="feature" id="hero" aria-label="Em destaque" hidden>
                <div class="feature__shade"></div><div id="hero-content" class="feature__content"></div>
            </section>
            <section class="catalog" aria-label="Catálogo">
                <div class="section-head"><div><span class="eyebrow" id="section-kicker">DESCOBRE A TUA PRÓXIMA HISTÓRIA</span><h1 id="section-title">Explorar filmes</h1><p id="section-description">Escolhe um género e encontra algo especial para ver.</p></div><div class="catalog-search"><svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18"><circle cx="10.8" cy="10.8" r="6.6" fill="none" stroke="currentColor" stroke-width="2"/><path d="m16 16 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg><label class="sr-only" for="pesquisa">Pesquisar nos resultados desta página</label><input id="pesquisa" type="search" placeholder="Pesquisar nesta página…" autocomplete="off"></div></div>
                <div class="catalog-controls" id="catalog-controls"><div class="filters-heading"><span>Géneros</span><span id="genre-hint">Seleciona uma categoria</span></div><div id="filtros" class="genre-filters" role="group" aria-label="Filtrar por género"></div></div>
                <div class="results-toolbar"><div class="results-count" id="contagem" aria-live="polite"></div><label class="sort-label" id="sort-area">Ordenar por <select id="ordenar"><option value="popularidade">Popularidade</option><option value="avaliacao">Melhor avaliação</option><option value="titulo">Título (A–Z)</option></select></label></div>
                <div class="movie-grid" id="grelha" aria-live="polite"></div>
                <div class="pagination" id="paginacao"><button id="ant" type="button" class="page-btn">← Anterior</button><span id="pag"></span><button id="seg" type="button" class="page-btn">Seguinte →</button></div>
            </section>
        </main>
        <footer class="site-footer"><div class="footer-inner"><span class="brand brand--footer"><span class="brand__icon" aria-hidden="true">▶</span><span>CINEMA<span class="brand__accent">TECA</span></span></span><p>Feito para quem vive histórias. Dados fornecidos por <a href="https://www.themoviedb.org/" target="_blank" rel="noopener noreferrer">TMDB</a>.</p><span>Filmes · Séries · Favoritos</span></div></footer>
    </div>

    <dialog id="detalhe" class="detail-dialog" aria-label="Detalhes do título"><button type="button" class="detail-close" aria-label="Fechar detalhes">×</button><div id="detalhe-corpo"></div></dialog>
    <div id="toast" class="toast" role="status" aria-live="polite" hidden></div>
</body>
</html>
