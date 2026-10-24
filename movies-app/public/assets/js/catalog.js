/* Cinemateca — interface do catálogo. Mantém os endpoints da API Android. */
(() => {
  'use strict';

  const API = '/api';
  const IMAGE_BASE = 'https://image.tmdb.org/t/p/';
  const $ = (id) => document.getElementById(id);
  const state = {
    user: null,
    authMode: 'entrar',
    tab: 'filmes',
    language: 'pt',
    genres: { filmes: null, series: null },
    selected: { filmes: 0, series: 0 },
    favorites: { filmes: new Set(), series: new Set() },
    list: [], page: 1, total: 1,
    search: '', sort: 'popularidade', request: 0,
  };

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  })[character]);
  const imageUrl = (path, size = 'w500') => typeof path === 'string' && /^\/[\w.-]+\.(?:jpg|jpeg|png|webp)$/i.test(path)
    ? `${IMAGE_BASE}${size}${path}` : '';
  const titleOf = (item) => item.title || item.name || 'Sem título';
  const isSeries = (item) => item.__kind === 'series' || (!item.title && !!item.name);
  const kindOf = (item) => isSeries(item) ? 'series' : 'filmes';
  const yearOf = (item) => (item.release_date || item.first_air_date || '').slice(0, 4) || '—';
  const scoreOf = (item) => Number.isFinite(Number(item.vote_average)) ? Number(item.vote_average) : 0;
  const summaryOf = (item) => item.overview || 'Ainda não existe uma sinopse disponível neste idioma.';
  const listOfGenres = (item) => {
    const ids = Array.isArray(item.genre_ids) ? item.genre_ids.map(Number) : Array.isArray(item.genres) ? item.genres.map(g => Number(g.id)) : [];
    const genres = state.genres[kindOf(item)] || [];
    return ids.map((id) => genres.find((g) => Number(g.id) === id)).filter(Boolean);
  };
  let toastTimer;

  function toast(message) {
    const area = $('toast'); area.textContent = message; area.hidden = false;
    clearTimeout(toastTimer); toastTimer = setTimeout(() => { area.hidden = true; }, 3700);
  }

  
  async function request(path, data) {
    try {
      const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

      const response = await fetch(`${API}${path}`, {
        method: data !== undefined ? 'POST' : 'GET',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          ...(data !== undefined
            ? {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
              }
            : {})
        },
        body: data !== undefined
          ? JSON.stringify(data)
          : undefined
      });

      const body = await response.json().catch(() => ({}));

      return {
        ok: response.ok,
        status: response.status,
        data: body
      };
    } catch {
      return {
        ok: false,
        data: {
          message: 'Não foi possível ligar ao servidor Laravel.'
        }
      };
    }
  }


  // Autenticação: conserva o contrato original de login/registo.
  function setAuthMode(mode) {
    state.authMode = mode;
    const creating = mode === 'criar';
    $('aba-entrar').setAttribute('aria-selected', String(!creating));
    $('aba-criar').setAttribute('aria-selected', String(creating));
    $('l-email').hidden = !creating;
    $('l-user').textContent = creating ? 'Nome de utilizador' : 'Nome de utilizador ou email';
    $('submeter').innerHTML = creating ? 'Criar conta <span aria-hidden="true">→</span>' : 'Entrar <span aria-hidden="true">→</span>';
    $('password').autocomplete = creating ? 'new-password' : 'current-password';
    $('erro-auth').textContent = '';
  }
  function showAuth() { $('auth').hidden = false; $('app').hidden = true; }
  async function showApp() {
    $('auth').hidden = true; $('app').hidden = false;
    const username = state.user?.username || 'Utilizador';
    $('nome').textContent = username;
    $('avatar').textContent = username.slice(0, 1).toUpperCase();
    // Consultas de géneros em paralelo permitem etiquetas completas também nos favoritos.
    await Promise.all([loadGenres('filmes'), loadGenres('series')]);
    refreshFavorites();
    await loadCatalog();
  }

  async function refreshFavorites() {
    if (!state.user) return;
    const [movies, series] = await Promise.all([
      request(`/user/favorites?language=${language}`),
      request(`/user/favorites/series?language=${language}`)
    ]);
    const language = encodeURIComponent(state.language);
    if (movies.ok) state.favorites.filmes = new Set((movies.data.results || []).map(x => Number(x.id)));
    if (series.ok) state.favorites.series = new Set((series.data.results || []).map(x => Number(x.id)));
  }

  async function loadGenres(tab) {
    if (state.genres[tab]) return state.genres[tab];
    const path = tab === 'filmes' ? '/genres' : '/series/genres';
    const result = await request(`${path}?language=${encodeURIComponent(state.language)}`);
    state.genres[tab] = result.ok && Array.isArray(result.data.genres) ? result.data.genres : [];
    return state.genres[tab];
  }

  function selectTab(tab) {
    state.tab = tab;
    state.page = 1;
    state.search = '';
    $('pesquisa').value = '';
    ['filmes', 'series', 'favoritos'].forEach((name) => {
      const element = $(`aba-${name}`);
      if (name === tab) element.setAttribute('aria-current', 'page');
      else element.removeAttribute('aria-current');
    });
    loadCatalog();
  }

  function changeGenre(tab, id) {
    state.selected[tab] = Number(id);
    state.page = 1;
    // Ao selecionar um género no detalhe, abre o respetivo separador.
    const dialog = $('detalhe');
    if (dialog.open) dialog.close();
    if (state.tab !== tab) selectTab(tab);
    else loadCatalog();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function renderGenres(tab) {
    if (tab === 'favoritos') { $('catalog-controls').hidden = true; return; }
    $('catalog-controls').hidden = false;
    const genres = state.genres[tab] || [];
    const options = [{ id: 0, name: '✦ Todos' }, ...genres];
    $('filtros').innerHTML = options.map(g => `<button type="button" class="genre-chip" data-genre="${Number(g.id)}" aria-pressed="${Number(g.id) === state.selected[tab]}">${escapeHtml(g.name)}</button>`).join('');
    $('genre-hint').textContent = genres.length ? `${genres.length} categorias disponíveis` : 'Sem categorias carregadas';
  }

  function renderHeading() {
    const tab = state.tab;
    const data = {
      filmes: ['A TUA SESSÃO COMEÇA AQUI', 'Explorar filmes', 'De grandes aventuras a histórias inesquecíveis: encontra o teu género.'],
      series: ['HISTÓRIAS QUE CONTINUAM', 'Explorar séries', 'Uma série para cada momento. Descobre por género e encontra a próxima obsessão.'],
      favoritos: ['A TUA SELEÇÃO PESSOAL', 'A minha lista', 'Todas as histórias que guardaste, reunidas num só lugar.'],
    }[tab];
    $('section-kicker').textContent = data[0];
    $('section-title').textContent = data[1];
    $('section-description').textContent = data[2];
    $('sort-area').hidden = tab === 'favoritos';
    $('paginacao').hidden = tab === 'favoritos';
  }

  const loadingMarkup = Array.from({ length: 12 }, () => '<div class="loading-card" aria-hidden="true"></div>').join('');
  async function loadCatalog() {
    const requestId = ++state.request;
    const { tab, page, language } = state;
    renderHeading();
    $('hero').hidden = true;
    $('grelha').innerHTML = loadingMarkup;
    $('contagem').textContent = 'A carregar títulos…';
    await loadGenres(tab === 'favoritos' ? 'filmes' : tab);
    if (requestId !== state.request) return;
    renderGenres(tab);
    let result;
    if (tab === 'favoritos') {
      const userId = encodeURIComponent(state.user.id);
      const lang = encodeURIComponent(language);
      const [movies, series] = await Promise.all([
        request(`/user/favorites?language=${lang}`),
        request(`/user/favorites/series?language=${lang}`)
      ]);
      if (requestId !== state.request) return;
      if (!movies.ok && !series.ok) { displayError(movies.data.message || 'Não foi possível carregar os favoritos.'); return; }
      state.favorites.filmes = new Set((movies.data.results || []).map(x => Number(x.id)));
      state.favorites.series = new Set((series.data.results || []).map(x => Number(x.id)));
      state.list = [...(movies.data.results || []).map(x => ({ ...x, __kind: 'filmes' })), ...(series.data.results || []).map(x => ({ ...x, __kind: 'series' }))];
      state.total = 1;
    } else {
      const genre = state.selected[tab] || 0;
      result = await request(`${tab === 'filmes' ? '/movie' : '/series'}?language=${encodeURIComponent(language)}&page=${page}&with_genres=${genre}`);
      if (requestId !== state.request) return;
      if (!result.ok) { displayError(result.data.message || 'Não foi possível carregar os títulos.'); return; }
      state.list = (result.data.results || []).map(x => ({ ...x, __kind: tab }));
      state.total = Math.max(1, Number(result.data.total_pages) || 1);
      renderFeature();
    }
    renderCards();
  }

  function displayError(message) {
    $('grelha').innerHTML = `<div class="empty-state"><div class="empty-state__icon" aria-hidden="true">⚠</div><h3>Ocorreu um problema</h3><p>${escapeHtml(message)}</p></div>`;
    $('contagem').textContent = 'Não foi possível apresentar resultados';
    $('paginacao').hidden = true;
  }

  function currentList() {
    let items = [...state.list];
    if (state.search.trim()) {
      const needle = state.search.trim().toLocaleLowerCase();
      items = items.filter((item) => titleOf(item).toLocaleLowerCase().includes(needle));
    }
    if (state.sort === 'avaliacao') items.sort((a, b) => scoreOf(b) - scoreOf(a));
    if (state.sort === 'titulo') items.sort((a, b) => titleOf(a).localeCompare(titleOf(b), state.language));
    return items;
  }

  function renderFeature() {
    const hero = $('hero');
    if (state.page !== 1) { hero.hidden = true; return; }
    const featured = state.list.find(x => imageUrl(x.backdrop_path, 'w1280'));
    if (!featured) { hero.hidden = true; return; }
    const index = state.list.indexOf(featured);
    hero.style.backgroundImage = `url("${imageUrl(featured.backdrop_path, 'w1280')}")`;
    $('hero-content').innerHTML = `
      <div class="feature__badge">✦ EM DESTAQUE · ${isSeries(featured) ? 'SÉRIE' : 'FILME'}</div>
      <h2>${escapeHtml(titleOf(featured))}</h2>
      <div class="feature__meta"><span>${escapeHtml(yearOf(featured))}</span><span>★ ${scoreOf(featured) ? scoreOf(featured).toFixed(1) : '—'}/10</span><span>${escapeHtml(listOfGenres(featured).slice(0, 2).map(g => g.name).join(' · '))}</span></div>
      <p>${escapeHtml(summaryOf(featured))}</p>
      <div class="feature__buttons"><button type="button" class="btn btn--primary" data-open="${index}">▶ Ver detalhes</button><button type="button" class="btn btn--dark" data-feature-fav="${index}">♡ Guardar</button></div>`;
    hero.hidden = false;
  }

  function renderCards() {
    const items = currentList();
    $('contagem').innerHTML = `<strong>${items.length}</strong> ${items.length === 1 ? 'título' : 'títulos'} ${state.search ? 'encontrados nesta página' : 'nesta seleção'}${state.tab === 'favoritos' ? ' guardados' : ''}`;
    $('grelha').innerHTML = items.length ? items.map((item) => {
      const index = state.list.indexOf(item);
      const picture = imageUrl(item.poster_path, 'w500');
      const cardImage = picture ? `<img src="${picture}" alt="" loading="lazy" decoding="async">` : '<div class="poster-placeholder"><span aria-hidden="true">◈</span>Sem cartaz</div>';
      return `<button type="button" class="movie-card" data-open="${index}" aria-label="Ver detalhes de ${escapeHtml(titleOf(item))}">
        <div class="movie-card__poster">${cardImage}<span class="movie-card__hover" aria-hidden="true">▶</span>
          <span class="movie-card__kind">${isSeries(item) ? 'SÉRIE' : 'FILME'}</span>
          ${scoreOf(item) ? `<span class="movie-card__rating">★ ${scoreOf(item).toFixed(1)}</span>` : ''}</div>
        <div class="movie-card__title">${escapeHtml(titleOf(item))}</div><div class="movie-card__sub">${escapeHtml(yearOf(item))}${listOfGenres(item)[0] ? ` · ${escapeHtml(listOfGenres(item)[0].name)}` : ''}</div>
      </button>`;
    }).join('') : `<div class="empty-state"><div class="empty-state__icon" aria-hidden="true">${state.tab === 'favoritos' ? '♡' : '⌕'}</div><h3>${state.search ? 'Nenhum título encontrado' : state.tab === 'favoritos' ? 'A tua lista ainda está vazia' : 'Sem resultados nesta categoria'}</h3><p>${state.search ? 'Experimenta outro termo de pesquisa ou muda de página.' : state.tab === 'favoritos' ? 'Abre um filme ou uma série e adiciona-o aos favoritos para aparecer aqui.' : 'Experimenta selecionar outro género ou consultar mais tarde.'}</p></div>`;
    $('pag').textContent = `Página ${state.page} de ${state.total}`;
    $('ant').disabled = state.page <= 1;
    $('seg').disabled = state.page >= state.total;
    $('paginacao').hidden = state.tab === 'favoritos';
  }

  function openDetail(item) {
    if (!item) return;
    const genres = listOfGenres(item);
    const type = kindOf(item);
    const saved = state.favorites[type].has(Number(item.id));
    const poster = imageUrl(item.poster_path, 'w500');
    const backdrop = imageUrl(item.backdrop_path, 'w1280');
    const genreHtml = genres.length ? genres.map(g => `<button class="detail-genre" type="button" data-genre-detail="${Number(g.id)}" data-type="${type}" title="Explorar ${escapeHtml(g.name)}">${escapeHtml(g.name)} ↗</button>`).join('') : '<span class="detail-genre">Género não disponível</span>';
    $('detalhe-corpo').innerHTML = `<div class="detail-cover" ${backdrop ? `style="background-image:url('${backdrop}')"` : ''}></div>
      <div class="detail-inner">${poster ? `<img class="detail-poster" src="${poster}" alt="Cartaz de ${escapeHtml(titleOf(item))}">` : '<div class="detail-poster detail-poster--empty">Sem cartaz</div>'}
        <div class="detail-info"><div class="detail-type">${type === 'series' ? '◉ SÉRIE DE TELEVISÃO' : '✦ LONGA-METRAGEM'}</div>
          <h2>${escapeHtml(titleOf(item))}</h2>
          <div class="detail-meta"><span>${escapeHtml(yearOf(item))}</span><span class="detail-score">★ ${scoreOf(item) ? scoreOf(item).toFixed(1) + '/10' : 'Sem avaliação'}</span>${item.original_language ? `<span>Idioma original: ${escapeHtml(String(item.original_language).toUpperCase())}</span>` : ''}</div>
          <div class="detail-genres" aria-label="Géneros deste título">${genreHtml}</div>
          <p class="detail-overview">${escapeHtml(summaryOf(item))}</p>
          <div class="detail-actions"><button type="button" id="detail-favorite" class="btn ${saved ? 'btn--dark' : 'btn--primary'}">${saved ? '♥ Remover dos favoritos' : '♡ Adicionar aos favoritos'}</button></div>
        </div>
      </div>`;
    $('detail-favorite').addEventListener('click', () => toggleFavorite(item, $('detail-favorite')));
    $('detalhe').showModal();
  }

  async function toggleFavorite(item, button) {
    if (!state.user) return;
    const type = kindOf(item);
    const id = Number(item.id);
    const set = state.favorites[type];
    const isSaved = set.has(id);
    const endpoint = type === 'series' ? `/user/favorites/series/${isSaved ? 'delete' : 'save'}` : `/user/favorites/${isSaved ? 'delete' : 'save'}`;
    const payload = type === 'series'? { series_id: id }: { movie_id: id };
    if (button) button.disabled = true;
    const result = await request(endpoint, payload);
    if (button) button.disabled = false;
    if (!result.ok) { toast(result.data.message || 'Não foi possível atualizar os favoritos.'); return; }
    if (isSaved) set.delete(id); else set.add(id);
    toast(isSaved ? 'Removido dos favoritos.' : 'Adicionado à tua lista!');
    if ($('detalhe').open) {
      $('detail-favorite').textContent = isSaved ? '♡ Adicionar aos favoritos' : '♥ Remover dos favoritos';
      $('detail-favorite').className = `btn ${isSaved ? 'btn--primary' : 'btn--dark'}`;
    }
    if (state.tab === 'favoritos') {
      if ($('detalhe').open) $('detalhe').close();
      loadCatalog();
    }
  }

  // Eventos delegados para itens renderizados dinamicamente.
  $('form-auth').addEventListener('submit', async (event) => {
    event.preventDefault();
    const creating = state.authMode === 'criar';
    const username = $('username').value.trim();
    const password = $('password').value;
    const email = $('email').value.trim();
    if (!username || !password || (creating && !email)) { $('erro-auth').textContent = 'Preenche os campos obrigatórios.'; return; }
    $('erro-auth').textContent = '';
    $('submeter').disabled = true;
    const response = await request(creating ? '/user/register' : '/user/login', { username, password, ...(creating ? { email } : {}) });
    $('submeter').disabled = false;
    if (!response.ok || !response.data.user) { $('erro-auth').textContent = response.data.message || 'Não foi possível continuar.'; return; }
    window.location.reload();
  });
  $('aba-entrar').addEventListener('click', () => setAuthMode('entrar'));
  $('aba-criar').addEventListener('click', () => setAuthMode('criar'));
  $('sair').addEventListener('click', async () => {
    const result = await request('/user/logout', {});

    if (!result.ok) {
      toast('Não foi possível terminar a sessão.');
      return;
    }

    localStorage.removeItem('cinemateca_user');
    localStorage.removeItem('user');

    window.location.reload();
  });
  ['filmes', 'series', 'favoritos'].forEach(tab => $(`aba-${tab}`).addEventListener('click', () => selectTab(tab)));
  $('idioma').addEventListener('change', (event) => {
    state.language = event.target.value;
    state.genres = { filmes: null, series: null };
    state.selected = { filmes: 0, series: 0 };
    state.page = 1;
    Promise.all([loadGenres('filmes'), loadGenres('series')]).then(() => { refreshFavorites(); loadCatalog(); });
  });
  $('filtros').addEventListener('click', (event) => {
    const button = event.target.closest('[data-genre]');
    if (button) changeGenre(state.tab, button.dataset.genre);
  });
  $('grelha').addEventListener('click', (event) => {
    const button = event.target.closest('[data-open]');
    if (button) openDetail(state.list[Number(button.dataset.open)]);
  });
  $('hero').addEventListener('click', (event) => {
    const favorite = event.target.closest('[data-feature-fav]');
    if (favorite) { toggleFavorite(state.list[Number(favorite.dataset.featureFav)], favorite); return; }
    const details = event.target.closest('[data-open]');
    if (details) openDetail(state.list[Number(details.dataset.open)]);
  });
  $('detalhe-corpo').addEventListener('click', (event) => {
    const button = event.target.closest('[data-genre-detail]');
    if (button) changeGenre(button.dataset.type, button.dataset.genreDetail);
  });
  document.querySelector('.detail-close').addEventListener('click', () => $('detalhe').close());
  $('detalhe').addEventListener('click', (event) => { if (event.target === $('detalhe')) $('detalhe').close(); });
  $('pesquisa').addEventListener('input', (event) => { state.search = event.target.value; renderCards(); });
  $('ordenar').addEventListener('change', (event) => { state.sort = event.target.value; renderCards(); });
  $('ant').addEventListener('click', () => { if (state.page > 1) { state.page--; loadCatalog(); } });
  $('seg').addEventListener('click', () => { if (state.page < state.total) { state.page++; loadCatalog(); } });

  async function initializeApp() {
    const response = await request('/user/me');

    if (response.ok && response.data.user) {
      state.user = response.data.user;
      await showApp();
    } else {
      state.user = null;
      showAuth();
    }
  }

  initializeApp();
})();
