import { ensureEcho } from '../echo.js';
import { esc, buildInteractionStatsHtml } from './postCard.js';

/**
 * @typedef {{ historia?: string, afinidad?: string, equilibrio?: string, recomendacion?: string, score?: number }} AiAnalysis
 */

const SVG_CHEVRON_LEFT = `<svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>`;

const SVG_SPARKLE_CHART = `<svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" /></svg>`;

const SVG_STAR_SCORE = `<svg class="inline-block h-4 w-4 align-text-bottom text-amber-300/95" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 00.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>`;

const SVG_ANALYSIS_HEADING = `<svg class="h-5 w-5 shrink-0 text-emerald-300/95" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>`;

const MARIDAJE_NAV_HINT_HTML = `Puedes alternar entre la publicación y el análisis con los botones <span class="font-medium text-slate-400">Ver análisis</span> y <span class="font-medium text-slate-400">Volver</span>.`;

/**
 * Altura natural de una cara del flip (caras apiladas con position:absolute).
 *
 * @param {HTMLElement} faceEl
 */
function measureEcoAnálisisFaceHeight(faceEl) {
    if (!faceEl) {
        return 0;
    }
    const rect = faceEl.getBoundingClientRect();

    return Math.ceil(Math.max(faceEl.scrollHeight, rect.height));
}

/**
 * Ajusta la altura del contenedor 3D al contenido de la cara visible (evita huecos en el front y compresión en el back).
 *
 * @param {HTMLElement} sceneRoot — [data-EcoAnálisis-flip-root]
 */
export function syncEcoAnálisisFlipHeight(sceneRoot) {
    const inner = sceneRoot.querySelector('.post-EcoAnálisis-flip-inner');
    const front = sceneRoot.querySelector('.post-EcoAnálisis-front');
    const back = sceneRoot.querySelector('.post-EcoAnálisis-back');
    if (!inner || !front || !back) {
        return;
    }

    const flipped = inner.classList.contains('is-EcoAnálisis-flipped');
    const active = flipped ? back : front;
    const h = measureEcoAnálisisFaceHeight(active);
    inner.style.height = `${Math.max(0, h)}px`;
}

/**
 * Barra inferior del post (muro / detalle): stats a la izquierda, «Ver análisis» a la derecha.
 *
 * @param {string} statsLeftHtml — HTML de like + comentarios ({@link buildInteractionStatsHtml})
 */
export function buildEcoAnálisisFrontInteractionBar(statsLeftHtml) {
    return `
        <div class="post-EcoAnálisis-actions-bar mt-1 flex flex-col gap-2.5 border-t border-slate-700/60 pt-2.5 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
            <div class="flex min-w-0 flex-wrap items-center gap-5 sm:flex-1">${statsLeftHtml}</div>
            <div class="flex shrink-0 items-center justify-start sm:justify-end">
                <button type="button" class="EcoAnálisis-btn-show-analysis inline-flex cursor-pointer items-center gap-1.5 rounded-full bg-emerald-600/90 px-3 py-1.5 text-xs font-semibold text-white shadow-sm shadow-emerald-900/25 transition-all duration-200 ease-out hover:bg-emerald-500 hover:scale-[1.02] active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/70">
                    ${SVG_SPARKLE_CHART}
                    <span>Ver análisis</span>
                </button>
            </div>
        </div>`;
}

/**
 * @param {boolean} canReanalyze
 */
function buildBackToolbarHtml(canReanalyze) {
    return `
        <div class="EcoAnálisis-analysis-toolbar flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-slate-700/70 px-5 pb-3 pt-4">
            <button type="button" class="EcoAnálisis-btn-back-analysis inline-flex items-center gap-1.5 rounded-full border border-slate-600/80 bg-slate-800/80 px-3 py-1.5 text-xs font-medium text-slate-200 transition hover:bg-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60">
                ${SVG_CHEVRON_LEFT}
                <span>Volver</span>
            </button>
            ${
                canReanalyze
                    ? `<button type="button" class="EcoAnálisis-btn-reanalyze-analysis inline-flex items-center gap-2 rounded-full bg-violet-600/90 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-violet-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400/70">
                <span>Analizar de nuevo</span>
            </button>`
                    : ''
            }
        </div>`;
}

/**
 * @param {AiAnalysis|null|undefined} analysis
 */
export function renderAiAnalysisSectionsHtml(analysis) {
    if (!analysis || typeof analysis !== 'object') {
        return `
            <div class="flex w-full flex-col items-center justify-center gap-4 py-10 px-2">
                <div class="h-11 w-11 rounded-full border-2 border-emerald-500/25 border-t-emerald-400 animate-spin" aria-hidden="true"></div>
                <p class="text-center text-sm font-medium text-slate-300 animate-pulse">Generando el análisis de EcoAnálisis…</p>
                <p class="text-center text-xs text-slate-500 max-w-xs">Cuando esté listo, verás el resultado aquí sin necesidad de recargar la página.</p>
            </div>`;
    }

    const rawScore = Number(analysis.score);
    const isFallbackScore = Number.isFinite(rawScore) && rawScore === 0;
    const scoreDisplay = isFallbackScore
        ? 0
        : Math.min(10, Math.max(1, Number.isFinite(rawScore) ? rawScore : 1));

    const historia = esc(String(analysis.historia ?? ''));
    const afinidadRaw = analysis.afinidad;
    const equilibrioRaw = analysis.equilibrio;
    const afinidad = esc(
        afinidadRaw === null || afinidadRaw === undefined ? '' : String(afinidadRaw),
    );
    const equilibrio = esc(
        equilibrioRaw === null || equilibrioRaw === undefined ? '' : String(equilibrioRaw),
    );
    const recomendacion = esc(String(analysis.recomendacion ?? ''));
    const emptyDash = '<span class="text-slate-500">—</span>';

    const fallbackBanner = isFallbackScore
        ? `<div role="status" class="rounded-lg border border-amber-500/35 bg-amber-950/35 px-3 py-2 text-xs leading-snug text-amber-100">
                No pudimos obtener el análisis automático en este momento; mostramos un texto de respaldo. Si publicaste tú la entrada, puedes pulsar «Analizar de nuevo» para intentarlo otra vez.
            </div>`
        : '';

    const scoreBlock = isFallbackScore
        ? `<div class="border-t border-slate-700/80 pt-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Puntuación</p>
                <p class="mt-2 text-sm text-amber-200/95">
                    <span class="inline-flex items-center gap-1.5">${SVG_STAR_SCORE}<span>0 de 10</span></span>
                    <span class="text-slate-400"> (resultado de respaldo)</span>
                </p>
            </div>`
        : `<div class="border-t border-slate-700/80 pt-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Puntuación</p>
                <p class="mt-2 text-base font-semibold text-amber-300">
                    <span class="inline-flex items-center gap-1.5">${SVG_STAR_SCORE}<span>${scoreDisplay} de 10</span></span>
                </p>
            </div>`;

    const labelCls =
        'text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400';
    const blockCls =
        'space-y-2 rounded-xl border border-slate-700/50 bg-slate-900/40 px-4 py-3.5 sm:px-5';

    return `
        <div class="post-EcoAnálisis-ai-body EcoAnálisis-ai-enter w-full space-y-6 text-[15px] leading-relaxed text-slate-200 transition-all duration-300 ease-out">
            <header class="flex items-center gap-2.5 border-b border-slate-700/60 pb-4 text-base font-semibold text-emerald-300">
                ${SVG_ANALYSIS_HEADING}
                <span>Análisis del EcoAnálisis</span>
            </header>
            ${fallbackBanner}
            <section class="${blockCls}">
                <h5 class="${labelCls}">Historia</h5>
                <p class="text-slate-200/95">${historia}</p>
            </section>
            <section class="${blockCls}">
                <h5 class="${labelCls}">Afinidad</h5>
                <p class="text-slate-200/95">${afinidad === '' ? emptyDash : afinidad}</p>
            </section>
            <section class="${blockCls}">
                <h5 class="${labelCls}">Equilibrio</h5>
                <p class="text-slate-200/95">${equilibrio === '' ? emptyDash : equilibrio}</p>
            </section>
            <section class="${blockCls}">
                <h5 class="${labelCls}">Recomendación</h5>
                <p class="text-slate-200/95">${recomendacion}</p>
            </section>
            ${scoreBlock}
        </div>`;
}

/**
 * @param {{
 *   postId: number,
 *   heroImg: string,
 *   userHeaderModal: string,
 *   tagsLine: string,
 *   titleHtml: string,
 *   descriptionStoryHtml: string,
 *   interactionBarHtml: string,
 *   aiAnalysis: AiAnalysis|null,
 *   canReanalyze: boolean
 * }} p
 */
export function buildWallModalFlipHtml(p) {
    const slotInner = renderAiAnalysisSectionsHtml(p.aiAnalysis);

    return `
        <div data-EcoAnálisis-flip-root data-EcoAnálisis-post-id="${p.postId}" class="mb-1">
            <p class="mb-3 text-center text-[11px] leading-relaxed text-slate-500">
                ${MARIDAJE_NAV_HINT_HTML}
            </p>
            <div class="post-EcoAnálisis-flip-scene rounded-xl ring-1 ring-slate-700/45">
                <div class="post-EcoAnálisis-flip-inner rounded-xl">
                    <div class="post-EcoAnálisis-front rounded-xl border border-slate-700/80 bg-slate-900/60 shadow-inner shadow-black/30 overflow-hidden">
                        ${p.heroImg}
                        <div class="space-y-3 px-4 py-3">
                            ${p.userHeaderModal}
                            <div>
                                <h2 class="text-xl font-bold text-slate-50">${p.titleHtml}</h2>
                                <div class="mt-2 flex flex-wrap gap-2">${p.tagsLine}</div>
                            </div>
                            <div class="text-sm leading-relaxed text-slate-300 whitespace-pre-wrap">${p.descriptionStoryHtml}</div>
                            ${p.interactionBarHtml}
                        </div>
                    </div>
                    <div class="post-EcoAnálisis-back flex w-full flex-col rounded-xl border border-emerald-900/40 bg-slate-950/95 shadow-inner shadow-black/40">
                        ${buildBackToolbarHtml(p.canReanalyze)}
                        <div class="w-full overflow-visible px-5 pb-5 pt-1" data-EcoAnálisis-ai-slot>
                            ${slotInner}
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
}

/**
 * @param {HTMLElement} mountEl
 * @param {object} post
 * @param {HTMLElement} articleEl
 * @param {{
 *   axios: import('axios').AxiosStatic,
 *   postBaseUrl: string,
 *   authUserId?: number|null,
 *   onNotify?: (message: string, variant?: string) => void
 * }} io
 */
export function mountPostShowEcoAnálisisFlip(mountEl, post, articleEl, io) {
    mountEl.innerHTML = '';

    const canReanalyze =
        io.authUserId != null && Number(io.authUserId) === Number(post.user?.id ?? NaN);

    const root = document.createElement('div');
    root.dataset.EcoAnálisisFlipRoot = '';
    root.dataset.EcoAnálisisPostId = String(post.id);
    root.className = 'mb-6';

    const hint = document.createElement('p');
    hint.className = 'mb-3 text-center text-[11px] leading-relaxed text-slate-500';
    hint.innerHTML = MARIDAJE_NAV_HINT_HTML;

    const scene = document.createElement('div');
    scene.className = 'post-EcoAnálisis-flip-scene rounded-xl ring-1 ring-slate-700/45';

    const inner = document.createElement('div');
    inner.className = 'post-EcoAnálisis-flip-inner rounded-xl';

    const front = document.createElement('div');
    front.className =
        'post-EcoAnálisis-front rounded-xl border border-slate-700/80 bg-slate-900/60 shadow-inner shadow-black/30 overflow-hidden';

    articleEl.classList.add('w-full');
    front.appendChild(articleEl);

    const toolbarHost = document.createElement('div');
    toolbarHost.className = 'bg-slate-900/55 px-4 pb-4 pt-2';
    toolbarHost.innerHTML = buildEcoAnálisisFrontInteractionBar(
        buildInteractionStatsHtml(post, { comfortable: true }),
    );
    front.appendChild(toolbarHost);

    const back = document.createElement('div');
    back.className =
        'post-EcoAnálisis-back flex w-full flex-col rounded-xl border border-emerald-900/40 bg-slate-950/95 shadow-inner shadow-black/40';
    back.innerHTML =
        buildBackToolbarHtml(canReanalyze) +
        `<div class="w-full overflow-visible px-5 pb-5 pt-1" data-EcoAnálisis-ai-slot>${renderAiAnalysisSectionsHtml(post.ai_analysis ?? null)}</div>`;

    inner.appendChild(front);
    inner.appendChild(back);
    scene.appendChild(inner);
    root.appendChild(hint);
    root.appendChild(scene);
    mountEl.appendChild(root);

    const reanalyzeUrl = `${io.postBaseUrl.replace(/\/$/, '')}/${post.id}/reanalyze`;

    return bindEcoAnálisisFlip(root, {
        postId: Number(post.id),
        axios: io.axios,
        reanalyzeUrl,
        canReanalyze,
        initialAnalysis: post.ai_analysis ?? null,
        onNotify: io.onNotify,
    });
}

/**
 * @typedef {{
 *   postId: number,
 *   axios?: import('axios').AxiosStatic,
 *   reanalyzeUrl?: string,
 *   canReanalyze?: boolean,
 *   initialAnalysis?: AiAnalysis|null,
 *   onNotify?: (message: string, variant?: string) => void
 * }} EcoAnálisisFlipOpts
 */

/**
 * @param {HTMLElement} sceneRoot — elemento con [data-EcoAnálisis-flip-root]
 * @param {EcoAnálisisFlipOpts} opts
 * @returns {() => void}
 */
export function bindEcoAnálisisFlip(sceneRoot, opts) {
    const inner = sceneRoot.querySelector('.post-EcoAnálisis-flip-inner');
    const front = sceneRoot.querySelector('.post-EcoAnálisis-front');
    const back = sceneRoot.querySelector('.post-EcoAnálisis-back');
    const slot = sceneRoot.querySelector('[data-EcoAnálisis-ai-slot]');

    if (!inner || !slot) {
        return () => {};
    }

    let latestAnalysis = opts.initialAnalysis ?? null;

    /** Refuerzo tras fuentes / layout asíncrono */
    function scheduleHeightSync() {
        window.requestAnimationFrame(() => {
            syncEcoAnálisisFlipHeight(sceneRoot);
            window.requestAnimationFrame(() => syncEcoAnálisisFlipHeight(sceneRoot));
        });
    }

    function showFront() {
        if (front) {
            inner.style.height = `${Math.max(0, measureEcoAnálisisFaceHeight(front))}px`;
        }
        inner.classList.remove('is-EcoAnálisis-flipped');
    }

    function showBack() {
        if (back) {
            inner.style.height = `${Math.max(0, measureEcoAnálisisFaceHeight(back))}px`;
        }
        inner.classList.add('is-EcoAnálisis-flipped');
    }

    function onWsAnalysis(payload) {
        if (Number(payload?.post_id) !== Number(opts.postId)) {
            return;
        }
        const a = payload?.ai_analysis;
        if (!a || typeof a !== 'object') {
            return;
        }
        latestAnalysis = a;
        slot.innerHTML = renderAiAnalysisSectionsHtml(a);
        scheduleHeightSync();
    }

    /** @param {MouseEvent} e */
    function onRootClick(e) {
        const showBtn = e.target.closest('.EcoAnálisis-btn-show-analysis');
        if (showBtn) {
            e.preventDefault();
            e.stopPropagation();
            showBack();

            return;
        }

        const backBtn = e.target.closest('.EcoAnálisis-btn-back-analysis');
        if (backBtn) {
            e.preventDefault();
            e.stopPropagation();
            showFront();

            return;
        }

        const reBtn = e.target.closest('.EcoAnálisis-btn-reanalyze-analysis');
        if (reBtn && opts.axios && opts.reanalyzeUrl) {
            e.preventDefault();
            e.stopPropagation();
            showBack();
            slot.innerHTML = renderAiAnalysisSectionsHtml(null);
            scheduleHeightSync();
            void (async () => {
                try {
                    await opts.axios.post(opts.reanalyzeUrl);
                    opts.onNotify?.('Estamos generando un nuevo análisis.', 'info');
                } catch {
                    opts.onNotify?.('No pudimos iniciar un nuevo análisis. Inténtalo de nuevo.', 'error');
                    slot.innerHTML = renderAiAnalysisSectionsHtml(latestAnalysis);
                    scheduleHeightSync();
                }
            })();
        }
    }

    sceneRoot.addEventListener('click', onRootClick);

    scheduleHeightSync();
    if (document.fonts?.ready) {
        void document.fonts.ready.then(() => syncEcoAnálisisFlipHeight(sceneRoot));
    }

    /** @type {ResizeObserver | null} */
    let flipResizeObserver = null;
    if (typeof ResizeObserver !== 'undefined' && front && back) {
        flipResizeObserver = new ResizeObserver(() => syncEcoAnálisisFlipHeight(sceneRoot));
        flipResizeObserver.observe(front);
        flipResizeObserver.observe(back);
    }

    let resizeDebounce = 0;
    function onWindowResize() {
        window.clearTimeout(resizeDebounce);
        resizeDebounce = window.setTimeout(() => syncEcoAnálisisFlipHeight(sceneRoot), 120);
    }
    window.addEventListener('resize', onWindowResize, { passive: true });

    let echoChannel = null;
    const wsHandler = (payload) => {
        onWsAnalysis(payload);
    };
    const Echo = ensureEcho();
    if (Echo != null && opts.postId != null) {
        echoChannel = Echo.channel(`post.${opts.postId}`);
        echoChannel.listen('.post.analysis.generated', wsHandler);
    }

    return () => {
        sceneRoot.removeEventListener('click', onRootClick);
        inner.classList.remove('is-EcoAnálisis-flipped');
        inner.style.height = '';
        window.removeEventListener('resize', onWindowResize);
        window.clearTimeout(resizeDebounce);
        flipResizeObserver?.disconnect();
        if (echoChannel != null) {
            echoChannel.stopListening('.post.analysis.generated', wsHandler);
        }
    };
}
