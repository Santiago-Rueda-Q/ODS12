import { ensureEcho } from './echo.js';
import {
    renderCard,
    heartSvgHtml,
    esc,
    formatStory,
    flashLikeAnimation,
    buildInteractionStatsHtml,
    countryPreviewMetaHtml,
    relativeTimeEs,
} from './social/postCard.js';
import { renderCommentsTreeHtml, setupCommentInteractions } from './social/commentThread.js';
import {
    bindEcoAnálisisFlip,
    buildEcoAnálisisFrontInteractionBar,
    buildWallModalFlipHtml,
} from './social/EcoAnálisisFlip.js';

const CLS = {
    secondary:
        'wall-chip wall-chip-sort shrink-0 rounded-full px-3 py-1.5 text-xs font-medium transition sm:px-3.5 sm:py-2 sm:text-sm bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/35 hover:bg-emerald-500/25',
    inactiveSort:
        'wall-chip wall-chip-sort shrink-0 rounded-full px-3 py-1.5 text-xs font-medium transition sm:px-3.5 sm:py-2 sm:text-sm bg-slate-800/90 text-slate-300 ring-1 ring-slate-700/80 hover:bg-slate-700 hover:text-white',
};

/** Etiquetas visibles y ayuda contextual (state.sort sigue siendo recent | popular | trending). */
const SORT_UX = {
    recent: {
        title: 'Recientes',
        hint: 'Un mix pensado para ti: ves primero lo que suele interesarte más.',
    },
    popular: {
        title: 'Populares',
        hint: 'Publicaciones que están generando más conversación y reacciones.',
    },
    trending: {
        title: 'Tendencia',
        hint: 'Temas y publicaciones que están destacando en los últimos días.',
    },
};

/** Ayuda cuando el feed es solo «Siguiendo» (evita repetir frases con el badge). */
const SORT_UX_FOLLOWING_HINT = {
    recent: 'Orden cronológico solo entre las personas que sigues.',
    popular: 'Lo que más conversación está generando entre tus cuentas seguidas.',
    trending: 'Publicaciones relevantes de los últimos días, solo entre quienes sigues.',
};

function showToast(message, variant = 'info') {
    const root = document.getElementById('wall-toast-root');
    if (!root) {
        return;
    }
    const el = document.createElement('div');
    const base =
        'pointer-events-auto rounded-xl px-4 py-3 text-sm shadow-lg border backdrop-blur-sm transition';
    const styles =
        variant === 'success'
            ? 'bg-emerald-950/95 text-emerald-50 border-emerald-500/40'
            : variant === 'error'
              ? 'bg-red-950/95 text-red-50 border-red-500/40'
              : 'bg-slate-900/95 text-slate-100 border-slate-600/80';
    el.className = `${base} ${styles}`;
    el.setAttribute('role', 'status');
    el.textContent = message;
    root.appendChild(el);
    window.setTimeout(() => {
        el.classList.add('opacity-0');
        window.setTimeout(() => el.remove(), 300);
    }, 4200);
}

export function initWall() {
    const axios = window.axios;
    if (!axios) {
        console.error('Axios no está disponible');

        return;
    }

    ensureEcho();

    const cfgEl = document.getElementById('wall-config');
    if (!cfgEl?.textContent) {
        return;
    }

    const config = JSON.parse(cfgEl.textContent);
    const feedEl = document.getElementById('posts-container');
    const scrollAnchor = document.getElementById('feed-scroll-anchor');
    const feedStatus = document.getElementById('feed-status');
    const skeleton = document.getElementById('wall-skeleton');
    const feedLoadingMore = document.getElementById('feed-loading-more');
    const modal = document.getElementById('wall-modal');
    const modalBody = document.getElementById('wall-modal-body');
    const modalBackdrop = document.getElementById('wall-modal-backdrop');

    const createModal = document.getElementById('create-post-modal');
    const createBackdrop = document.getElementById('create-post-backdrop');
    const createForm = document.getElementById('create-post-form');
    const createErrors = document.getElementById('create-post-errors');
    const fabCreatePost = document.getElementById('wall-fab-create-post');
    const createCloseBtn = document.getElementById('create-post-close');
    const createCancelBtn = document.getElementById('create-post-cancel');
    const createTitleEl = document.getElementById('create-post-title');
    const createSubmitBtn = document.getElementById('create-post-submit');
    const imageInput = document.getElementById('create-post-field-image');
    const imageZone = document.getElementById('create-post-image-zone');
    const imageEmpty = document.getElementById('create-post-image-empty');
    const imageFilled = document.getElementById('create-post-image-filled');
    const imagePreview = document.getElementById('create-post-image-preview');
    const removeImageBtn = document.getElementById('create-post-remove-image');
    const titleEditable = document.getElementById('create-post-editable-title');
    const descEditable = document.getElementById('create-post-editable-description');
    const hiddenTitle = document.getElementById('create-post-field-title');
    const hiddenDesc = document.getElementById('create-post-field-description');
    const composerModeInput = document.getElementById('create-post-composer-mode');
    const editPostIdInput = document.getElementById('create-post-edit-id');
    const foodInput = document.getElementById('create-post-field-food');
    const drinkInput = document.getElementById('create-post-field-drink');
    const tagPillsEl = document.getElementById('create-post-card-tag-pills');
    const tagPanel = document.getElementById('create-post-tag-panel');
    const tagPanelDismiss = document.getElementById('create-post-tag-panel-dismiss');
    const tagPanelCloseBtn = document.getElementById('create-post-tag-panel-close');
    const openTagsBtn = document.getElementById('create-post-open-tags');
    const searchInput = document.getElementById('wall-search-q');

    const state = {
        following: config.initialFollowing === true,
        sort: 'recent',
        q: '',
    };

    const params = new URLSearchParams(window.location.search);
    const urlSearch = (params.get('search') ?? params.get('q') ?? '').trim();
    if (urlSearch) {
        state.q = urlSearch;
    }
    const urlSort = params.get('sort');
    if (urlSort === 'popular' || urlSort === 'trending' || urlSort === 'recent') {
        state.sort = urlSort;
    }
    if (searchInput && urlSearch) {
        searchInput.value = urlSearch;
    }
    let searchDebounceTimer = null;
    let fetchDebounceTimer = null;
    let activeRequestController = null;
    const pagination = {
        page: 1,
        perPage: 12,
        hasMore: true,
        loadingMore: false,
    };

    const selectedCreateTags = new Set();
    /** @type {Map<number, { id: number, name: string, type: string, slug: string }>} */
    const selectedTagMeta = new Map();
    let createImageObjectUrl = null;
    let syncCardFrame = null;
    let modalCommentAbort = null;
    /** @type {(() => void) | null} */
    let modalFlipCleanup = null;
    const composerState = {
        mode: 'create',
        postId: null,
    };

    const Echo = window.Echo;
    if (Echo) {
        const moderationChannel = Echo.channel('posts.moderation');
        moderationChannel.listen('.post.moderation.updated', (event) => {
            const postId = Number(event?.post_id);
            if (!Number.isFinite(postId)) {
                return;
            }

            const postCards = document.querySelectorAll(`article[data-post-id="${postId}"]`);
            if ((event?.status || '') === 'rejected') {
                postCards.forEach((node) => node.remove());

                const modalPostId = Number(modalBody?.querySelector('[data-modal-post-id]')?.dataset?.modalPostId);
                if (Number.isFinite(modalPostId) && modalPostId === postId) {
                    closeModal();
                }

                showToast(
                    'Tu post fue retirado automáticamente por políticas de contenido.',
                    'error',
                );
                return;
            }

            if ((event?.status || '') === 'active') {
                showToast('Análisis completado: publicación activa.', 'success');
            } else if ((event?.analysis_status || '') === 'processing') {
                showToast('Analizando contenido...', 'info');
            }
        });
    }

    function revokeCreateImageUrl() {
        if (createImageObjectUrl) {
            URL.revokeObjectURL(createImageObjectUrl);
            createImageObjectUrl = null;
        }
    }

    function plainTextFromEditable(el) {
        return (el?.innerText || el?.textContent || '').trim();
    }

    function updateCeEmptyClass(el) {
        if (!el) {
            return;
        }
        const empty = plainTextFromEditable(el) === '';
        el.classList.toggle('ce-empty', empty);
    }

    function normalizeEditableBr(el) {
        if (!el) {
            return;
        }
        if (!plainTextFromEditable(el)) {
            el.innerHTML = '';
            el.classList.add('ce-empty');
        }
    }

    function syncHiddenFromEditables() {
        let title = plainTextFromEditable(titleEditable);
        if (title.length > 150) {
            title = title.slice(0, 150);
            if (titleEditable) {
                titleEditable.textContent = title;
            }
        }
        const desc = plainTextFromEditable(descEditable);
        if (hiddenTitle) {
            hiddenTitle.value = title;
        }
        if (hiddenDesc) {
            hiddenDesc.value = desc;
        }
    }

    function updateCardTagPills() {
        if (!tagPillsEl) {
            return;
        }
        tagPillsEl.innerHTML = '';
        selectedCreateTags.forEach((rawId) => {
            const id = Number(rawId);
            const meta = selectedTagMeta.get(id);
            if (!meta) {
                return;
            }
            const span = document.createElement('span');
            span.className = 'font-medium text-emerald-400/95';
            span.textContent = `#${meta.name}`;
            tagPillsEl.appendChild(span);
        });
    }

    function renderSelectedChipsBar() {
        const bar = document.getElementById('create-post-selected-chips-bar');
        if (!bar) {
            return;
        }
        bar.innerHTML = '';
        selectedCreateTags.forEach((rawId) => {
            const id = Number(rawId);
            const meta = selectedTagMeta.get(id);
            if (!meta) {
                return;
            }
            const chip = document.createElement('span');
            chip.className =
                'inline-flex items-center gap-1.5 rounded-full border border-emerald-500/35 bg-emerald-500/10 px-3 py-1.5 text-xs font-medium text-emerald-100';
            const label = document.createElement('span');
            label.textContent = `#${meta.name}`;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className =
                'rounded-full p-0.5 text-emerald-300/90 transition hover:bg-white/10 hover:text-white';
            btn.setAttribute('aria-label', `Quitar etiqueta ${meta.name}`);
            btn.innerHTML =
                '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
            btn.addEventListener('click', () => removeSelectedTag(id));
            chip.appendChild(label);
            chip.appendChild(btn);
            bar.appendChild(chip);
        });
    }

    function removeSelectedTag(id) {
        const n = Number(id);
        selectedCreateTags.delete(n);
        selectedTagMeta.delete(n);
        scheduleSyncEditableCard();
    }

    function addSelectedTag(meta) {
        const id = Number(meta.id);
        if (selectedCreateTags.has(id)) {
            return;
        }
        selectedCreateTags.add(id);
        selectedTagMeta.set(id, {
            id,
            name: meta.name,
            type: meta.type,
            slug: meta.slug,
        });
        scheduleSyncEditableCard();
    }

    const flatCatalogTags = (() => {
        const out = [];
        const bag = config.tagsByType || {};
        Object.keys(bag).forEach((typeKey) => {
            (bag[typeKey] || []).forEach((t) => {
                out.push({
                    id: t.id,
                    name: t.name,
                    slug: t.slug,
                    type: typeKey,
                });
            });
        });

        return out;
    })();

    let smartHintsTimer = null;

    function scheduleSmartHintsFromContent() {
        window.clearTimeout(smartHintsTimer);
        smartHintsTimer = window.setTimeout(() => runSmartHintsFromContent(), 450);
    }

    function runSmartHintsFromContent() {
        const wrap = document.getElementById('create-post-smart-hints');
        const labelEl = document.getElementById('create-post-smart-hints-label');
        if (!wrap || !labelEl) {
            return;
        }

        const raw = `${plainTextFromEditable(titleEditable)} ${plainTextFromEditable(descEditable)}`;
        const normalized = raw
            .toLowerCase()
            .normalize('NFD')
            .replace(/\p{M}/gu, '');
        const tokens = normalized.split(/[^\p{L}\p{N}]+/u).filter((w) => w.length >= 3);
        if (tokens.length === 0) {
            wrap.classList.add('hidden');
            labelEl.classList.add('hidden');
            wrap.innerHTML = '';

            return;
        }

        const selectedIds = new Set(selectedCreateTags);
        /** @type {Map<number, number>} */
        const scores = new Map();

        for (const tok of tokens) {
            for (const t of flatCatalogTags) {
                if (selectedIds.has(t.id)) {
                    continue;
                }
                const tn = t.name
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/\p{M}/gu, '');
                if (tn.includes(tok) || tok.includes(tn)) {
                    scores.set(t.id, (scores.get(t.id) || 0) + 1);
                }
            }
        }

        const ranked = [...scores.entries()]
            .sort((a, b) => b[1] - a[1])
            .slice(0, 8)
            .map(([tid]) => flatCatalogTags.find((x) => x.id === tid))
            .filter(Boolean);

        if (ranked.length === 0) {
            wrap.classList.add('hidden');
            labelEl.classList.add('hidden');
            wrap.innerHTML = '';

            return;
        }

        labelEl.classList.remove('hidden');
        wrap.classList.remove('hidden');
        wrap.innerHTML = '';
        ranked.forEach((t) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className =
                'rounded-full border border-emerald-500/30 bg-zinc-800/90 px-3 py-1.5 text-[11px] font-medium text-emerald-200 transition hover:bg-emerald-600/35';
            b.textContent = `+ #${t.name}`;
            b.addEventListener('click', () => {
                addSelectedTag(t);
                runSmartHintsFromContent();
            });
            wrap.appendChild(b);
        });
    }

    const tagInput = document.getElementById('create-post-tag-input');
    const tagDropdown = document.getElementById('create-post-tag-dropdown');
    let tagSearchTimer = null;

    function isTagPanelOpen() {
        return Boolean(tagPanel && !tagPanel.classList.contains('hidden'));
    }

    function openTagPanel() {
        if (!tagPanel) {
            return;
        }
        tagPanel.classList.remove('hidden');
        tagPanel.setAttribute('aria-hidden', 'false');
        window.requestAnimationFrame(() => {
            tagInput?.focus();
            runSmartHintsFromContent();
        });
    }

    function closeTagPanel() {
        if (!tagPanel) {
            return;
        }
        tagPanel.classList.add('hidden');
        tagPanel.setAttribute('aria-hidden', 'true');
        hideTagDropdown();
    }
    /** @type {AbortController | null} */
    let tagSearchAbort = null;

    function hideTagDropdown() {
        tagDropdown?.classList.add('hidden');
        if (tagDropdown) {
            tagDropdown.innerHTML = '';
        }
    }

    async function runTagSearchQuery() {
        const q = (tagInput?.value || '').trim();
        tagSearchAbort?.abort();
        if (!config.tagsSearchUrl || q.length < 1) {
            hideTagDropdown();

            return;
        }

        const ctrl = new AbortController();
        tagSearchAbort = ctrl;

        try {
            const url = `${config.tagsSearchUrl}?${new URLSearchParams({ q })}`;
            const { data } = await axios.get(url, { signal: ctrl.signal });
            const rawItems = data.tags || [];
            const items = rawItems.filter((t) => !selectedCreateTags.has(Number(t.id)));
            if (!tagDropdown) {
                return;
            }
            tagDropdown.innerHTML = '';
            if (items.length === 0) {
                const li = document.createElement('li');
                li.className = 'px-4 py-3 text-sm text-slate-500';
                li.textContent = 'Sin coincidencias';
                tagDropdown.appendChild(li);
                tagDropdown.classList.remove('hidden');

                return;
            }

            items.forEach((t) => {
                const li = document.createElement('li');
                li.setAttribute('role', 'option');
                li.className =
                    'cursor-pointer px-4 py-2.5 text-sm text-slate-100 transition hover:bg-emerald-600/25 active:bg-emerald-600/35';
                li.textContent = t.name;
                li.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                });
                li.addEventListener('click', () => {
                    addSelectedTag(t);
                    if (tagInput) {
                        tagInput.value = '';
                    }
                    hideTagDropdown();
                    tagInput?.focus();
                });
                tagDropdown.appendChild(li);
            });
            tagDropdown.classList.remove('hidden');
        } catch (e) {
            if (e?.name === 'CanceledError' || e?.name === 'AbortError') {
                return;
            }
            console.error(e);
        }
    }

    tagInput?.addEventListener('input', () => {
        window.clearTimeout(tagSearchTimer);
        tagSearchTimer = window.setTimeout(() => {
            void runTagSearchQuery();
        }, 300);
    });

    tagInput?.addEventListener('focus', () => {
        if ((tagInput.value || '').trim().length > 0) {
            void runTagSearchQuery();
        }
    });

    tagInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            hideTagDropdown();
        }
    });

    createModal?.addEventListener('click', (e) => {
        if (e.target === createBackdrop) {
            hideTagDropdown();
        }
    });

    openTagsBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        openTagPanel();
    });

    tagPanelDismiss?.addEventListener('click', (e) => {
        e.preventDefault();
        closeTagPanel();
    });

    tagPanelCloseBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        closeTagPanel();
    });

    function scheduleSyncEditableCard() {
        if (syncCardFrame) {
            cancelAnimationFrame(syncCardFrame);
        }
        syncCardFrame = requestAnimationFrame(() => {
            syncCardFrame = null;
            syncHiddenFromEditables();
            updateCardTagPills();
            renderSelectedChipsBar();
            updateCeEmptyClass(titleEditable);
            updateCeEmptyClass(descEditable);
            const validationHint = document.getElementById('create-post-validation-hint');
            if (validationHint) {
                validationHint.textContent =
                    selectedCreateTags.size === 0 ? 'Elige al menos una etiqueta antes de publicar.' : '';
            }
        });
    }

    function bindCreateComposerEditables() {
        const pastePlain = (e) => {
            e.preventDefault();
            const text = e.clipboardData?.getData('text/plain') ?? '';
            document.execCommand('insertText', false, text);
        };

        titleEditable?.addEventListener('paste', pastePlain);
        descEditable?.addEventListener('paste', pastePlain);

        titleEditable?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                descEditable?.focus();
            }
        });

        titleEditable?.addEventListener('input', () => {
            if (titleEditable && (titleEditable.textContent?.length ?? 0) > 150) {
                titleEditable.textContent = titleEditable.textContent.slice(0, 150);
            }
            updateCeEmptyClass(titleEditable);
            scheduleSyncEditableCard();
            scheduleSmartHintsFromContent();
        });

        descEditable?.addEventListener('input', () => {
            updateCeEmptyClass(descEditable);
            scheduleSyncEditableCard();
            scheduleSmartHintsFromContent();
        });

        titleEditable?.addEventListener('blur', () => {
            normalizeEditableBr(titleEditable);
            updateCeEmptyClass(titleEditable);
        });

        descEditable?.addEventListener('blur', () => {
            normalizeEditableBr(descEditable);
            updateCeEmptyClass(descEditable);
        });
    }

    bindCreateComposerEditables();

    function applyLikeState(postId, liked, likesCount) {
        document.querySelectorAll(`[data-like-post-id="${postId}"]`).forEach((btn) => {
            btn.dataset.liked = liked ? '1' : '0';
            btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
            btn.classList.toggle('text-rose-500', liked);
            btn.classList.toggle('text-slate-400', !liked);
            const span = btn.querySelector('[data-like-count]');
            if (span) {
                span.textContent = String(likesCount);
            }
            const wrap = btn.querySelector('.wall-like-svg-wrap');
            if (wrap) {
                wrap.innerHTML = heartSvgHtml(liked);
            }
        });
    }

    function setLikeButtonsBusy(postId, busy) {
        document.querySelectorAll(`[data-like-post-id="${postId}"]`).forEach((btn) => {
            btn.dataset.busy = busy ? '1' : '0';
            btn.toggleAttribute('disabled', busy);
            btn.classList.toggle('opacity-70', busy);
            btn.classList.toggle('cursor-wait', busy);
        });
    }

    function applyCommentsCount(postId, count) {
        document.querySelectorAll(`article[data-post-id="${postId}"] [data-comments-count]`).forEach((el) => {
            el.textContent = String(count);
        });
        const modalCount = document.getElementById('wall-modal-comments-count');
        if (modalCount) {
            modalCount.textContent = String(count);
        }
    }

    async function toggleLike(postId) {
        if (!config.isAuthenticated) {
            window.location.href = config.loginUrl;

            return;
        }

        const probeBtn = document.querySelector(`[data-like-post-id="${postId}"]`);
        if (!probeBtn || probeBtn.dataset.busy === '1') {
            return;
        }

        const previousLiked = probeBtn.dataset.liked === '1';
        const previousCount = Number(
            probeBtn.querySelector('[data-like-count]')?.textContent || 0,
        );
        const optimisticLiked = !previousLiked;
        const optimisticCount = Math.max(0, previousCount + (optimisticLiked ? 1 : -1));

        setLikeButtonsBusy(postId, true);
        applyLikeState(postId, optimisticLiked, optimisticCount);

        try {
            const { data } = await axios.post(`${config.postBaseUrl}/${postId}/likes/toggle`);
            applyLikeState(postId, data.liked === true, data.likes_count);
            if (data.liked === true) {
                flashLikeAnimation(postId);
            }
        } catch (e) {
            applyLikeState(postId, previousLiked, previousCount);
            console.error(e);
            showToast('No pudimos guardar tu «me gusta». Inténtalo de nuevo.', 'error');
        } finally {
            setLikeButtonsBusy(postId, false);
        }
    }

    function setFeedStatus(message) {
        if (feedStatus) {
            feedStatus.textContent = message;
        }
    }

    const sortFeedbackEl = document.getElementById('wall-sort-feedback');
    const sortActiveKindEl = document.getElementById('wall-sort-active-kind');
    const sortScopeBadgeEl = document.getElementById('wall-sort-scope-badge');
    const sortHintEl = document.getElementById('wall-sort-hint');

    function updateSortContextUi({ flash = false } = {}) {
        const ux = SORT_UX[state.sort] ?? SORT_UX.recent;
        if (sortActiveKindEl) {
            sortActiveKindEl.textContent = ux.title;
        }
        if (sortScopeBadgeEl) {
            if (state.following) {
                sortScopeBadgeEl.textContent = 'Siguiendo';
                sortScopeBadgeEl.classList.remove('hidden');
            } else {
                sortScopeBadgeEl.textContent = '';
                sortScopeBadgeEl.classList.add('hidden');
            }
        }
        if (sortHintEl) {
            sortHintEl.textContent = state.following
                ? (SORT_UX_FOLLOWING_HINT[state.sort] ?? SORT_UX_FOLLOWING_HINT.recent)
                : ux.hint;
        }
        if (flash && sortFeedbackEl) {
            sortFeedbackEl.classList.remove('wall-sort-context-flash');
            void sortFeedbackEl.offsetWidth;
            sortFeedbackEl.classList.add('wall-sort-context-flash');
            window.setTimeout(() => sortFeedbackEl.classList.remove('wall-sort-context-flash'), 480);
        }
    }

    function syncFeedQueryParam() {
        const url = new URL(window.location.href);
        if (state.following) {
            url.searchParams.set('following', '1');
        } else {
            url.searchParams.delete('following');
        }
        const qq = String(state.q || '').trim();
        if (qq) {
            url.searchParams.set('search', qq);
        } else {
            url.searchParams.delete('search');
        }
        url.searchParams.delete('q');
        if (state.sort && state.sort !== 'recent') {
            url.searchParams.set('sort', state.sort);
        } else {
            url.searchParams.delete('sort');
        }
        window.history.replaceState({}, '', `${url.pathname}${url.search}`);
    }

    function updateNavbarFeedUi() {
        const slider = document.getElementById('navbar-feed-slider');
        if (slider) {
            slider.style.transform = state.following ? 'translateX(100%)' : 'translateX(0%)';
        }

        document.querySelectorAll('[data-navbar-feed]').forEach((el) => {
            const tab = el.dataset.navbarFeed || '';
            const on =
                (tab === 'fyp' && !state.following) || (tab === 'following' && state.following);
            el.classList.toggle('active', on);
            el.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    function buildQuery(page = 1) {
        const p = new URLSearchParams();
        if (state.following) {
            p.set('following', '1');
        }
        if (state.sort) {
            p.set('sort', state.sort);
        }
        const qq = String(state.q || '').trim();
        if (qq) {
            p.set('search', qq);
        }
        p.set('page', String(page));
        p.set('per_page', String(pagination.perPage));

        return p.toString();
    }

    function applyFeedTagSearch(term) {
        const t = String(term || '').trim();
        state.q = t;
        if (searchInput) {
            searchInput.value = t;
        }
        state.following = false;
        pagination.page = 1;
        pagination.hasMore = true;
        updateNavbarFeedUi();
        updateFilterUi();
        scheduleFetch(0);
    }

    async function fetchBoard({ append = false } = {}) {
        if (!append && activeRequestController) {
            activeRequestController.abort();
        }
        const currentController = new AbortController();
        activeRequestController = currentController;

        if (!append) {
            skeleton?.classList.remove('hidden');
            feedEl?.classList.add('opacity-0', 'pointer-events-none');
            setFeedStatus('Cargando publicaciones...');
        } else {
            pagination.loadingMore = true;
            feedLoadingMore?.classList.remove('hidden');
            setFeedStatus('Cargando más publicaciones...');
        }

        try {
            const targetPage = append ? pagination.page + 1 : 1;
            const qs = buildQuery(targetPage);
            const url = qs ? `${config.filterUrl}?${qs}` : config.filterUrl;
            const { data } = await axios.get(url, { signal: currentController.signal });
            renderFeed(data.posts || [], data.meta, { append });
        } catch (e) {
            if (e?.name === 'CanceledError' || e?.name === 'AbortError') {
                return;
            }
            console.error(e);
            if (feedEl) {
                feedEl.innerHTML =
                    '<p class="text-red-400 col-span-full py-14 text-center text-sm">No se pudo cargar el muro. Intenta de nuevo.</p>';
            }
        } finally {
            if (!append) {
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        skeleton?.classList.add('hidden');
                        feedEl?.classList.remove('opacity-0', 'pointer-events-none');
                        feedEl?.classList.add('opacity-100');
                    });
                });
            } else {
                pagination.loadingMore = false;
                feedLoadingMore?.classList.add('hidden');
            }
            syncFeedQueryParam();
        }
    }

    function scheduleFetch(delayMs = 180) {
        if (fetchDebounceTimer) {
            window.clearTimeout(fetchDebounceTimer);
        }

        fetchDebounceTimer = window.setTimeout(() => {
            pagination.page = 1;
            pagination.hasMore = true;
            setFeedStatus('Actualizando publicaciones...');
            fetchBoard({ append: false });
        }, delayMs);
    }

    function renderFeed(posts, meta, { append = false } = {}) {
        if (!feedEl) {
            return;
        }

        if (!append) {
            feedEl.innerHTML = '';
        }

        if (meta?.guest_following) {
            feedEl.innerHTML = `
                <p class="col-span-full text-center text-slate-400 py-16 text-sm max-w-md mx-auto leading-relaxed">
                    <a href="${esc(config.loginUrl)}" class="font-medium text-emerald-400 hover:text-emerald-300 underline underline-offset-2">Inicia sesión</a> para ver publicaciones de quienes sigues.
                </p>
            `;
            pagination.hasMore = false;
            setFeedStatus('Inicia sesión para ver publicaciones de quienes sigues.');

            return;
        }

        if (!posts.length && !append) {
            feedEl.innerHTML =
                '<p class="col-span-full text-center text-slate-500 py-16 text-sm">No hay publicaciones con estos filtros.</p>';
            pagination.hasMore = false;
            setFeedStatus('No hay publicaciones con los filtros actuales.');

            return;
        }

        if (!posts.length && append) {
            pagination.hasMore = false;
            setFeedStatus('No hay más publicaciones para cargar.');
            return;
        }

        posts.forEach((post) => {
            feedEl.appendChild(
                renderCard(post, {
                    onOpenDetail: (id) => {
                        void openModal(id);
                    },
                }),
            );
        });

        pagination.page = Number(meta?.current_page || pagination.page || 1);
        pagination.hasMore = Boolean(meta?.has_more);
        setFeedStatus(
            pagination.hasMore
                ? 'Publicaciones cargadas. Sigue desplazándote para ver más.'
                : 'Publicaciones cargadas. Fin del feed.',
        );
    }

    feedEl?.addEventListener('click', (e) => {
        const pill = e.target.closest('.wall-feed-tag-pill');
        if (pill) {
            e.preventDefault();
            e.stopPropagation();
            const name = pill.getAttribute('data-tag-name') || '';
            applyFeedTagSearch(name);

            return;
        }
        const likeBtn = e.target.closest('.wall-like-btn');
        if (!likeBtn || !likeBtn.dataset.likePostId) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        toggleLike(Number(likeBtn.dataset.likePostId));
    });

    modalBody?.addEventListener('click', (e) => {
        const pill = e.target.closest('.wall-feed-tag-pill');
        if (pill) {
            e.preventDefault();
            e.stopPropagation();
            const name = pill.getAttribute('data-tag-name') || '';
            closeModal();
            applyFeedTagSearch(name);

            return;
        }
        const likeBtn = e.target.closest('.wall-like-btn');
        if (!likeBtn || !likeBtn.dataset.likePostId) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        toggleLike(Number(likeBtn.dataset.likePostId));
    });

    function setupInfiniteScroll() {
        if (!scrollAnchor || !('IntersectionObserver' in window)) {
            return;
        }

        const observer = new IntersectionObserver(
            async (entries) => {
                const entry = entries[0];
                if (!entry?.isIntersecting) {
                    return;
                }
                if (pagination.loadingMore || !pagination.hasMore) {
                    return;
                }
                await fetchBoard({ append: true });
            },
            { rootMargin: '400px 0px 200px 0px', threshold: 0.01 },
        );

        observer.observe(scrollAnchor);
    }

    async function openModal(postId) {
        if (!modal || !modalBody) {
            return;
        }

        modalFlipCleanup?.();
        modalFlipCleanup = null;

        modalBody.innerHTML =
            '<div class="flex justify-center py-12 text-slate-400">Cargando…</div>';
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        try {
            const { data } = await axios.get(`${config.postBaseUrl}/${postId}`);
            const p = data.post;
            const whenModal = relativeTimeEs(p.created_at);
            const metaPieces = [];
            const userCountry = p.user?.country ?? p.country;
            if (userCountry != null) {
                metaPieces.push(countryPreviewMetaHtml(userCountry));
            }
            if (whenModal) {
                metaPieces.push(`<span class="shrink-0 text-slate-500">${esc(whenModal)}</span>`);
            }
            const metaJoinedModal = metaPieces.join(
                '<span class="text-slate-600" aria-hidden="true">·</span>',
            );
            const canEditPost = p.can_edit === true;
            const userHeaderModal = `
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 flex-1 gap-3">
                        <a href="${esc(p.user.profile_url)}" class="wall-post-user-avatar shrink-0 rounded-full ring-1 ring-slate-600/70 outline-none transition hover:ring-emerald-500/50 focus-visible:ring-2 focus-visible:ring-emerald-400/60" aria-label="Ver perfil: @${esc(p.user.username)}">
                            <img src="${esc(p.user.avatar_medium || p.user.avatar_thumb || p.user.avatar)}" alt="" class="h-12 w-12 rounded-full object-cover bg-slate-800" width="48" height="48" loading="lazy" decoding="async" />
                        </a>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-100">@${esc(p.user.username)}</p>
                            ${
                                metaJoinedModal !== ''
                                    ? `<div class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-[11px] leading-tight">${metaJoinedModal}</div>`
                                    : ''
                            }
                        </div>
                    </div>
                    ${
                        canEditPost
                            ? `<button type="button" id="wall-post-edit-trigger" class="inline-flex shrink-0 items-center rounded-full border border-cyan-500/40 px-3 py-1.5 text-xs font-semibold text-cyan-200 transition hover:bg-cyan-500/10">Editar post</button>`
                            : ''
                    }
                </div>`;

            const tagsLine = (p.tags || [])
                .map(
                    (t) =>
                        `<button type="button" class="wall-feed-tag-pill inline-flex rounded-full bg-slate-800 px-2.5 py-1 text-xs font-medium text-emerald-200 ring-1 ring-slate-600 transition hover:bg-emerald-600/25 hover:ring-emerald-500/50" data-tag-name="${esc(t.name)}" aria-label="Filtrar por ${esc(t.name)}">#${esc(t.name)}</button>`,
                )
                .join(' ');

            const commentsHtml = renderCommentsTreeHtml(p.comments || [], {
                showReplyButtons: config.isAuthenticated === true,
            });

            const commentForm = config.isAuthenticated
                ? `
                <form id="wall-comment-form" class="mt-4 space-y-2 border-t border-slate-700/80 pt-4">
                    <label class="block text-sm font-medium text-slate-300">Tu comentario</label>
                    <textarea name="body" rows="3" class="w-full rounded-xl border-slate-600 bg-slate-800/80 text-slate-100 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 placeholder-slate-500" required maxlength="2000" placeholder="Escribe algo…"></textarea>
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-500">Publicar</button>
                </form>
            `
                : `<p class="mt-4 text-sm text-slate-400"><a href="${config.loginUrl}" class="text-emerald-400 hover:text-emerald-300 underline">Inicia sesión</a> para comentar.</p>`;

            const heroImg =
                p.image_url != null
                    ? `<div class="mb-4 overflow-hidden rounded-xl border border-slate-700"><img src="${esc(p.image_url)}" alt="" class="w-full max-h-72 object-cover" loading="lazy" /></div>`
                    : '';

            const modalLiked = p.liked === true;
            const modalLikesCount = p.likes_count ?? 0;
            const canReanalyzeEcoAnálisis =
                config.isAuthenticated === true &&
                config.authUserId != null &&
                Number(config.authUserId) === Number(p.user?.id);

            const interactionBarHtml = buildEcoAnálisisFrontInteractionBar(
                buildInteractionStatsHtml(
                    {
                        ...p,
                        liked: modalLiked,
                        likes_count: modalLikesCount,
                        comments_count: p.comments_count ?? 0,
                    },
                    { commentsCountId: 'wall-modal-comments-count', comfortable: true },
                ),
            );

            modalBody.innerHTML = `
                <div class="space-y-4" data-modal-post-id="${p.id}">
                    ${buildWallModalFlipHtml({
                        postId: Number(p.id),
                        heroImg,
                        userHeaderModal,
                        tagsLine,
                        titleHtml: esc(p.title),
                        descriptionStoryHtml: formatStory(p.description),
                        interactionBarHtml,
                        aiAnalysis: p.ai_analysis ?? null,
                        canReanalyze: canReanalyzeEcoAnálisis,
                    })}
                    <div>
                        <h3 class="font-semibold text-slate-200 mb-2">Comentarios</h3>
                        <div id="wall-modal-comments" class="rounded-xl border border-slate-700/60 bg-slate-950/50 p-3 shadow-inner">${commentsHtml}</div>
                        ${commentForm}
                    </div>
                </div>
            `;

            modalFlipCleanup?.();
            modalFlipCleanup = null;
            const flipRoot = modalBody.querySelector('[data-EcoAnálisis-flip-root]');
            if (flipRoot) {
                modalFlipCleanup = bindEcoAnálisisFlip(flipRoot, {
                    postId: Number(p.id),
                    axios,
                    reanalyzeUrl: `${String(config.postBaseUrl).replace(/\/$/, '')}/${p.id}/reanalyze`,
                    canReanalyze: canReanalyzeEcoAnálisis,
                    initialAnalysis: p.ai_analysis ?? null,
                    onNotify: (msg, variant) => showToast(msg, variant ?? 'info'),
                });
            }

            const editTriggerBtn = document.getElementById('wall-post-edit-trigger');
            if (canEditPost && editTriggerBtn) {
                editTriggerBtn.addEventListener('click', () => {
                    openComposerForEdit(p);
                });
            }

            modalCommentAbort?.abort();
            modalCommentAbort = new AbortController();
            setupCommentInteractions(
                modalBody,
                {
                    postId: Number(p.id),
                    postBaseUrl: config.postBaseUrl,
                    axios,
                    isAuthenticated: config.isAuthenticated === true,
                    loginUrl: config.loginUrl,
                    getCommentsWrap: () => document.getElementById('wall-modal-comments'),
                    showToast,
                    applyCommentsCount,
                    modalCommentsCountSelector: '#wall-modal-comments-count',
                },
                { signal: modalCommentAbort.signal },
            );
        } catch (e) {
            modalBody.innerHTML =
                '<p class="text-red-400 text-center py-8">No se pudo cargar la publicación.</p>';
        }
    }

    function closeModal() {
        modalFlipCleanup?.();
        modalFlipCleanup = null;
        modalCommentAbort?.abort();
        modalCommentAbort = null;
        modal?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function setComposerMode(mode, postId = null) {
        composerState.mode = mode === 'edit' ? 'edit' : 'create';
        composerState.postId = composerState.mode === 'edit' ? Number(postId) : null;

        if (composerModeInput) {
            composerModeInput.value = composerState.mode;
        }
        if (editPostIdInput) {
            editPostIdInput.value =
                composerState.mode === 'edit' && Number.isFinite(composerState.postId)
                    ? String(composerState.postId)
                    : '';
        }
        if (createTitleEl) {
            createTitleEl.textContent =
                composerState.mode === 'edit' ? 'Editar publicación' : 'Nueva publicación';
        }
        if (createSubmitBtn) {
            createSubmitBtn.textContent =
                composerState.mode === 'edit' ? 'Guardar cambios' : 'Publicar';
        }
    }

    function openCreateModal() {
        if (!createModal) {
            return;
        }
        createModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeCreateModal() {
        closeTagPanel();
        createModal?.classList.add('hidden');
        const detailOpen = modal && !modal.classList.contains('hidden');
        if (!detailOpen) {
            document.body.classList.remove('overflow-hidden');
        }
    }

    function resetCreateForm() {
        closeTagPanel();
        createForm?.reset();
        setComposerMode('create');
        selectedCreateTags.clear();
        selectedTagMeta.clear();
        if (tagInput) {
            tagInput.value = '';
        }
        hideTagDropdown();
        const smartWrap = document.getElementById('create-post-smart-hints');
        const smartLab = document.getElementById('create-post-smart-hints-label');
        smartWrap?.classList.add('hidden');
        smartLab?.classList.add('hidden');
        if (smartWrap) {
            smartWrap.innerHTML = '';
        }
        revokeCreateImageUrl();
        if (imageInput) {
            imageInput.value = '';
        }
        if (imagePreview) {
            imagePreview.removeAttribute('src');
        }
        imageFilled?.classList.add('hidden');
        imageEmpty?.classList.remove('hidden');
        imageZone?.setAttribute('data-drag', 'inactive');
        if (titleEditable) {
            titleEditable.innerHTML = '';
            titleEditable.classList.add('ce-empty');
        }
        if (descEditable) {
            descEditable.innerHTML = '';
            descEditable.classList.add('ce-empty');
        }
        if (hiddenTitle) {
            hiddenTitle.value = '';
        }
        if (hiddenDesc) {
            hiddenDesc.value = '';
        }
        if (foodInput) {
            foodInput.value = '';
        }
        if (drinkInput) {
            drinkInput.value = '';
        }
        if (createErrors) {
            createErrors.classList.add('hidden');
            createErrors.textContent = '';
        }
        scheduleSyncEditableCard();
    }

    function openComposerForEdit(post) {
        resetCreateForm();
        setComposerMode('edit', post.id);

        if (titleEditable) {
            titleEditable.textContent = post.title || '';
            updateCeEmptyClass(titleEditable);
        }
        if (descEditable) {
            descEditable.textContent = post.description || '';
            updateCeEmptyClass(descEditable);
        }
        if (foodInput) {
            foodInput.value = post.food || '';
        }
        if (drinkInput) {
            drinkInput.value = post.drink || '';
        }

        selectedCreateTags.clear();
        selectedTagMeta.clear();
        (post.tags || []).forEach((tag) => addSelectedTag(tag));
        scheduleSyncEditableCard();

        closeModal();
        openCreateModal();
        window.requestAnimationFrame(() => {
            titleEditable?.focus();
        });
    }

    function applyImageFile(file) {
        if (!file || !String(file.type || '').startsWith('image/')) {
            return;
        }
        revokeCreateImageUrl();
        createImageObjectUrl = URL.createObjectURL(file);
        if (imagePreview) {
            imagePreview.src = createImageObjectUrl;
        }
        imageEmpty?.classList.add('hidden');
        imageFilled?.classList.remove('hidden');
        scheduleSyncEditableCard();
    }

    imageInput?.addEventListener('change', () => {
        const file = imageInput.files?.[0];
        if (!file) {
            return;
        }
        applyImageFile(file);
    });

    imageEmpty?.addEventListener('click', () => {
        imageInput?.click();
    });

    removeImageBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        revokeCreateImageUrl();
        if (imageInput) {
            imageInput.value = '';
        }
        imagePreview?.removeAttribute('src');
        imageFilled?.classList.add('hidden');
        imageEmpty?.classList.remove('hidden');
        scheduleSyncEditableCard();
    });

    imageZone?.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        imageZone.setAttribute('data-drag', 'active');
    });

    imageZone?.addEventListener('dragleave', (e) => {
        if (!imageZone.contains(e.relatedTarget)) {
            imageZone.setAttribute('data-drag', 'inactive');
        }
    });

    imageZone?.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        imageZone.setAttribute('data-drag', 'inactive');
        const file = e.dataTransfer?.files?.[0];
        if (!file) {
            return;
        }
        const dt = new DataTransfer();
        dt.items.add(file);
        imageInput.files = dt.files;
        applyImageFile(file);
    });

    fabCreatePost?.addEventListener('click', () => {
        resetCreateForm();
        openCreateModal();
        window.requestAnimationFrame(() => {
            titleEditable?.focus();
        });
    });

    [createBackdrop, createCloseBtn, createCancelBtn].forEach((el) => {
        el?.addEventListener('click', () => {
            closeCreateModal();
        });
    });

    createForm?.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        if (!config.postStoreUrl) {
            return;
        }

        syncHiddenFromEditables();
        normalizeEditableBr(titleEditable);
        normalizeEditableBr(descEditable);

        const titleLen = plainTextFromEditable(titleEditable);
        const descLen = plainTextFromEditable(descEditable);
        if (!titleLen || !descLen) {
            showToast('Necesitamos un título y una descripción para publicar.', 'error');

            return;
        }

        if (selectedCreateTags.size === 0) {
            openTagPanel();
            if (createErrors) {
                createErrors.textContent = 'Elige al menos una etiqueta para clasificar tu publicación.';
                createErrors.classList.remove('hidden');
            }
                showToast('Elige al menos una etiqueta para clasificar tu publicación.', 'error');

            return;
        }

        const submitBtn = document.getElementById('create-post-submit');
        submitBtn?.setAttribute('disabled', 'true');

        const fd = new FormData(createForm);
        fd.delete('tags');
        selectedCreateTags.forEach((id) => fd.append('tags[]', String(id)));

        if (createErrors) {
            createErrors.classList.add('hidden');
            createErrors.textContent = '';
        }

        try {
            const isEdit = composerState.mode === 'edit' && Number.isFinite(composerState.postId);
            const targetPostId = isEdit ? Number(composerState.postId) : null;
            const requestUrl = isEdit ? `${config.postBaseUrl}/${targetPostId}` : config.postStoreUrl;

            let response;
            if (isEdit) {
                fd.append('_method', 'PUT');
                response = await axios.post(requestUrl, fd);
            } else {
                response = await axios.post(requestUrl, fd);
            }

            const data = response.data || {};
            const post = data.post;
            showToast(
                isEdit ? 'Post actualizado, re-analizando...' : 'Analizando contenido...',
                'info',
            );
            closeCreateModal();
            resetCreateForm();
            if (feedEl && post && isEdit) {
                const existing = feedEl.querySelector(`article[data-post-id="${post.id}"]`);
                const replacement = renderCard(post, {
                    onOpenDetail: (id) => {
                        void openModal(id);
                    },
                });
                if (existing) {
                    existing.replaceWith(replacement);
                } else {
                    feedEl.insertBefore(replacement, feedEl.firstChild);
                }
            } else if (feedEl && post) {
                feedEl.insertBefore(
                    renderCard(post, {
                        onOpenDetail: (id) => {
                            void openModal(id);
                        },
                    }),
                    feedEl.firstChild,
                );
            }
            setFeedStatus(
                isEdit
                    ? 'Publicación actualizada y enviada a reanálisis.'
                    : 'Nueva publicación añadida al feed.',
            );
        } catch (err) {
            const res = err.response;
            if (res?.status === 422 && res.data?.errors) {
                const lines = Object.values(res.data.errors)
                    .flat()
                    .map((m) => String(m));
                if (createErrors) {
                    createErrors.innerHTML = lines.map((l) => `<p>${esc(l)}</p>`).join('');
                    createErrors.classList.remove('hidden');
                }
                showToast(lines[0] || 'Revisa los datos e inténtalo de nuevo.', 'error');
            } else {
                showToast('No pudimos publicar ahora. Comprueba tu conexión e inténtalo otra vez.', 'error');
            }
        } finally {
            submitBtn?.removeAttribute('disabled');
        }
    });

    document.getElementById('wall-modal-close')?.addEventListener('click', closeModal);
    modalBackdrop?.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (createModal && !createModal.classList.contains('hidden')) {
                if (isTagPanelOpen()) {
                    closeTagPanel();

                    return;
                }
                closeCreateModal();

                return;
            }
            if (modal && !modal.classList.contains('hidden')) {
                closeModal();
            }
        }
    });

    document.querySelectorAll('[data-navbar-feed]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.navbarFeed || '';
            if (tab === 'fyp') {
                state.following = false;
                updateFilterUi();
                scheduleFetch();

                return;
            }
            if (tab === 'following') {
                if (!config.isAuthenticated) {
                    window.location.href = config.loginUrl;

                    return;
                }
                state.following = true;
                updateFilterUi();
                scheduleFetch();
            }
        });
    });

    document.querySelectorAll('[data-sort]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const next = btn.dataset.sort || 'recent';
            const sortChanged = next !== state.sort;
            state.sort = next;
            updateFilterUi({ flashSortContext: sortChanged });
            scheduleFetch();
        });
    });

    searchInput?.addEventListener('input', () => {
        if (searchDebounceTimer) {
            window.clearTimeout(searchDebounceTimer);
        }
        searchDebounceTimer = window.setTimeout(() => {
            state.q = searchInput.value;
            pagination.page = 1;
            pagination.hasMore = true;
            scheduleFetch();
        }, 320);
    });

    function updateFilterUi({ flashSortContext = false } = {}) {
        updateNavbarFeedUi();

        document.querySelectorAll('[data-sort]').forEach((el) => {
            const on = state.sort === el.dataset.sort;
            el.className = on ? CLS.secondary : CLS.inactiveSort;
            el.setAttribute('aria-selected', on ? 'true' : 'false');
        });

        updateSortContextUi({ flash: flashSortContext });
    }

    updateFilterUi();
    setupInfiniteScroll();
    scheduleFetch(0);
}
