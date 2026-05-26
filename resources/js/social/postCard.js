/** Gradientes tipo daily.dev para cabecera de card (determinísticos por id) */
const HEADER_GRADIENTS = [
    'from-violet-600 via-purple-600 to-fuchsia-700',
    'from-emerald-600 via-teal-600 to-cyan-700',
    'from-amber-600 via-orange-600 to-rose-700',
    'from-sky-600 via-blue-600 to-indigo-800',
    'from-rose-600 via-pink-600 to-violet-800',
];

function gradientClassForPostId(id) {
    const n = Number(id) || 0;

    return HEADER_GRADIENTS[Math.abs(n) % HEADER_GRADIENTS.length];
}

export function relativeTimeEs(iso) {
    if (!iso) {
        return '';
    }
    const then = new Date(iso).getTime();
    if (Number.isNaN(then)) {
        return '';
    }
    const sec = Math.floor((Date.now() - then) / 1000);
    if (sec < 45) {
        return 'ahora';
    }
    const min = Math.floor(sec / 60);
    if (min < 60) {
        return min <= 1 ? 'hace 1 min' : `hace ${min} min`;
    }
    const h = Math.floor(min / 60);
    if (h < 24) {
        return h === 1 ? 'hace 1 h' : `hace ${h} h`;
    }
    const d = Math.floor(h / 24);
    if (d < 7) {
        return d === 1 ? 'hace 1 día' : `hace ${d} días`;
    }
    const w = Math.floor(d / 7);
    if (w < 5) {
        return w === 1 ? 'hace 1 sem' : `hace ${w} sem`;
    }

    return new Date(iso).toLocaleDateString('es', { day: 'numeric', month: 'short' });
}

export function esc(s) {
    const d = document.createElement('div');
    d.textContent = s;

    return d.innerHTML;
}

/**
 * Nombre de país + bandera: usa flag_url del API o, en su defecto, /flags/{iso}.svg.
 * @param {{ name?: string, flag_url?: string | null, iso_code?: string | null } | null | undefined} country
 */
export function countryPreviewMetaHtml(country) {
    const name = esc(country?.name ?? '—');
    let url = country?.flag_url;
    if (!url && country?.iso_code) {
        const code = String(country.iso_code).trim().toLowerCase();
        if (code.length === 2) {
            url = `/flags/${code}.svg`;
        }
    }
    if (url) {
        return `<span class="inline-flex items-center gap-1.5 min-w-0 max-w-full"><img src="${esc(url)}" alt="" class="h-3.5 w-5 shrink-0 rounded-sm object-cover object-left ring-1 ring-slate-500/50" width="20" height="14" loading="lazy" decoding="async" /><span class="truncate text-slate-400">${name}</span></span>`;
    }

    return `<span class="truncate text-slate-400">${name}</span>`;
}

export function onEnterOrSpace(event, callback) {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        callback();
    }
}

export function formatStory(text) {
    return esc(String(text)).replace(/\n/g, '<br>');
}

export const ICON_COMMENT =
    '<svg class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>';

export function flashLikeAnimation(postId) {
    document.querySelectorAll(`button[data-like-post-id="${postId}"]`).forEach((btn) => {
        btn.classList.remove('wall-like-pop');
        btn.offsetWidth;
        btn.classList.add('wall-like-pop');
        window.setTimeout(() => btn.classList.remove('wall-like-pop'), 480);
    });
}

export function heartSvgHtml(liked) {
    if (liked) {
        return '<svg class="h-4 w-4 text-rose-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>';
    }

    return '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>';
}

/**
 * Like + contador de comentarios (compacto) para barra de interacción.
 *
 * @param {object} post — payload PostResource
 * @param {{ commentsCountId?: string, comfortable?: boolean }} [opts]
 */
export function buildInteractionStatsHtml(post, opts = {}) {
    const comfortable = opts.comfortable === true;
    const liked = post.liked === true;
    const likesCount = post.likes_count ?? 0;
    const commentsCount = post.comments_count ?? 0;
    const idAttr = opts.commentsCountId ? ` id="${esc(opts.commentsCountId)}"` : '';

    const likeBtnClass = comfortable
        ? `wall-like-btn inline-flex min-h-[40px] cursor-pointer items-center gap-1.5 rounded-full px-2 py-1.5 text-sm font-medium transition-all duration-200 ease-out hover:bg-slate-800/80 hover:text-slate-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 active:scale-[0.98] ${
              liked ? 'text-rose-500' : 'text-slate-400'
          }`
        : `wall-like-btn inline-flex min-h-[36px] cursor-pointer items-center gap-1.5 rounded-full px-1.5 py-1 text-xs font-medium transition-all duration-200 ease-out hover:bg-slate-800/80 hover:text-slate-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 active:scale-[0.98] ${
              liked ? 'text-rose-500' : 'text-slate-400'
          }`;

    const statText = comfortable ? 'text-sm text-slate-400' : 'text-[11px] text-slate-400';

    return `
        <button type="button" class="${likeBtnClass}" data-like-post-id="${post.id}" data-liked="${liked ? '1' : '0'}" aria-pressed="${liked ? 'true' : 'false'}" aria-label="Me gusta">
            <span class="wall-like-svg-wrap shrink-0">${heartSvgHtml(liked)}</span>
            <span data-like-count class="tabular-nums">${likesCount}</span>
        </button>
        <span class="inline-flex cursor-default items-center gap-1.5 ${statText} transition-colors duration-200 hover:text-slate-200" title="Comentarios">
            ${ICON_COMMENT}
            <span${idAttr} data-comments-count class="tabular-nums">${commentsCount}</span>
        </span>`;
}

/**
 * @param {object} post — payload PostResource
 * @param {{ onOpenDetail?: (id: number) => void, omitInteractionBar?: boolean }} [options]
 */
export function renderCard(post, options = {}) {
    const { onOpenDetail, omitInteractionBar } = options;

    const el = document.createElement('article');
    el.className =
        'group relative flex flex-col overflow-hidden rounded-xl border border-slate-700/80 bg-slate-900/80 shadow-sm shadow-black/30 transition duration-200 ease-out hover:z-[1] hover:shadow-lg hover:shadow-black/40 hover:border-slate-600 backdrop-blur-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/70';
    if (onOpenDetail) {
        el.classList.add(
            'cursor-pointer',
            'hover:scale-[1.02]',
            'active:scale-[0.99]',
        );
        el.setAttribute('role', 'button');
        el.setAttribute('tabindex', '0');
    }
    el.dataset.postId = String(post.id);
    el.setAttribute('aria-label', `Publicación: ${post.title}`);

    const when = relativeTimeEs(post.created_at);
    const countryHtml = countryPreviewMetaHtml(post.user?.country ?? post.country);
    const timeHtml = when
        ? `<span class="shrink-0 text-slate-500" data-post-relative-time>${esc(when)}</span>`
        : '';
    const user = post.user || {};
    const profileUrl = esc(user.profile_url || '#');
    const avatarUrl = esc(user.avatar_thumb || user.avatar || '');
    const username = user.username ? esc(String(user.username)) : '';
    const userTitle = username
        ? `<span class="text-slate-100">@${username}</span>`
        : `<span class="text-slate-100">${esc(user.name || '—')}</span>`;
    const ariaProfile = esc(user.username ? `@${user.username}` : String(user.name || 'usuario'));

    const grad = gradientClassForPostId(post.id);
    const highlightBadge =
        post.EcoAnálisis_highlighted === true
            ? `<span class="pointer-events-none absolute right-2 top-2 z-10 rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-200 ring-1 ring-amber-400/40 shadow-sm shadow-black/40" title="EcoAnálisis destacado">🔥 Destacado</span>`
            : '';

    const headerHtml = post.image_url
        ? `<div class="relative h-[140px] shrink-0 overflow-hidden bg-slate-800">
                ${highlightBadge}
                <img src="${esc(post.image_url)}" alt="" class="h-full w-full object-cover" loading="lazy" />
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 to-transparent pointer-events-none"></div>
               </div>`
        : `<div class="relative h-[140px] shrink-0 overflow-hidden bg-slate-800">
                ${highlightBadge}
                <div class="absolute inset-0 bg-gradient-to-br ${grad} opacity-95"></div>
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(255,255,255,0.12),_transparent_55%)]"></div>
            </div>`;

    const tagPills = (post.tags || [])
        .slice(0, 5)
        .map(
            (t) =>
                `<button type="button" class="wall-feed-tag-pill inline-flex rounded-full bg-slate-800/90 px-2 py-0.5 text-[10px] font-medium text-emerald-200/95 ring-1 ring-slate-600/80 transition hover:bg-emerald-600/25 hover:ring-emerald-500/50" data-tag-name="${esc(t.name)}" aria-label="Filtrar por ${esc(t.name)}">#${esc(t.name)}</button>`,
        )
        .join('');

    el.innerHTML = `
            ${headerHtml}
            <div class="flex flex-1 flex-col p-4 pt-3">
                <div class="mb-3 flex gap-3">
                    <a href="${profileUrl}" class="wall-post-user-avatar shrink-0 self-start rounded-full ring-1 ring-slate-600/70 transition hover:ring-emerald-500/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60" data-post-avatar-link aria-label="Ver perfil: ${ariaProfile}">
                        <img src="${avatarUrl}" alt="" class="h-10 w-10 rounded-full object-cover bg-slate-800" width="40" height="40" loading="lazy" decoding="async" />
                    </a>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold leading-tight">${userTitle}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-[11px] leading-tight">
                            ${countryHtml}
                            ${when ? `<span class="text-slate-600" aria-hidden="true">·</span>` : ''}
                            ${timeHtml}
                        </div>
                    </div>
                </div>
                <h3 class="text-[15px] font-semibold leading-snug text-slate-100 line-clamp-2">${esc(post.title)}</h3>
                <p class="mt-3 text-sm leading-relaxed text-slate-400 line-clamp-3">${esc(post.excerpt)}</p>
                <div class="mt-3 flex flex-wrap gap-1.5">${tagPills}</div>
                ${
                    omitInteractionBar === true
                        ? ''
                        : `
                <div class="mt-4 border-t border-slate-700/80 pt-3">
                    <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                        <div class="flex min-w-0 items-center gap-5">
                            ${buildInteractionStatsHtml(post, { comfortable: false })}
                        </div>
                        <span class="max-w-full truncate text-end text-[11px] text-slate-500 sm:max-w-[45%]">${esc((post.tags || []).find((t) => t.type === 'experience')?.name || '')}</span>
                    </div>
                </div>`
                }
            </div>
        `;

    if (onOpenDetail) {
        el.addEventListener('click', (e) => {
            if (
                e.target.closest('a') ||
                e.target.closest('.wall-like-btn') ||
                e.target.closest('.wall-feed-tag-pill')
            ) {
                return;
            }
            onOpenDetail(post.id);
        });
        el.addEventListener('keydown', (e) => {
            if (
                e.target.closest('.wall-like-btn') ||
                e.target.closest('.wall-feed-tag-pill') ||
                e.target.closest('.wall-post-user-avatar')
            ) {
                return;
            }
            onEnterOrSpace(e, () => onOpenDetail(post.id));
        });
    }

    return el;
}
