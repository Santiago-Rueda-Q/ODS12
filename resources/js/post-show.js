import { ensureEcho } from './echo.js';
import { resetAppChromeState } from './ui/appChromeReset.js';
import { renderCard, heartSvgHtml, flashLikeAnimation } from './social/postCard.js';
import { mountPostShowEcoAnálisisFlip } from './social/maridajeFlip.js';
import {
    renderCommentsTreeHtml,
    setupCommentInteractions,
    renderCommentNodeHtml,
} from './social/commentThread.js';

let postShowPageInitialized = false;

/** @type {(() => void) | null} */
let postShowFlipCleanup = null;

function showToast(el, message) {
    if (!el) {
        return;
    }
    el.textContent = message;
    el.classList.remove('hidden');
    window.setTimeout(() => {
        el.classList.add('hidden');
    }, 4200);
}

function syncCommentsCount(root, postId, count) {
    const s = String(count);
    root
        .querySelectorAll(`article[data-post-id="${postId}"] [data-comments-count], #post-show-card-mount [data-comments-count]`)
        .forEach((node) => {
            node.textContent = s;
        });
}

export function initPostShow() {
    const root = document.getElementById('post-show-page');
    if (!root || postShowPageInitialized) {
        return;
    }
    postShowPageInitialized = true;

    resetAppChromeState();

    const axios = window.axios;
    if (!axios) {
        console.error('Axios no está disponible');

        return;
    }

    const cfgEl = document.getElementById('post-show-config');
    const postEl = document.getElementById('post-show-json');
    if (!cfgEl?.textContent || !postEl?.textContent) {
        return;
    }

    const config = JSON.parse(cfgEl.textContent);
    const post = JSON.parse(postEl.textContent);
    const toastEl = document.getElementById('post-show-toast');

    const mount = document.getElementById('post-show-card-mount');
    if (mount) {
        mount.innerHTML = '';
        postShowFlipCleanup?.();
        postShowFlipCleanup = null;
        const article = renderCard(post, { omitInteractionBar: true });
        postShowFlipCleanup = mountPostShowEcoAnálisisFlip(mount, post, article, {
            axios,
            postBaseUrl: config.postBaseUrl,
            authUserId: config.authUserId ?? null,
            onNotify: (msg) => showToast(toastEl, msg),
        });
    }

    const commentsWrap = document.getElementById('post-show-comments');
    if (commentsWrap) {
        commentsWrap.className =
            'mt-4 rounded-xl border border-slate-700/60 bg-slate-950/50 p-3 shadow-inner';
        commentsWrap.innerHTML = renderCommentsTreeHtml(post.comments || [], {
            showReplyButtons: config.isAuthenticated === true,
        });
    }

    async function toggleLike(postId) {
        if (!config.isAuthenticated) {
            window.location.href = config.loginUrl;

            return;
        }
        try {
            const { data } = await axios.post(`${config.postBaseUrl}/${postId}/likes/toggle`);
            const liked = data.liked === true;
            const likesCount = data.likes_count ?? 0;
            if (liked) {
                flashLikeAnimation(postId);
            }
            root.querySelectorAll(`[data-like-post-id="${postId}"]`).forEach((btn) => {
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
        } catch (e) {
            console.error(e);
            showToast(toastEl, 'No pudimos guardar tu «me gusta». Inténtalo de nuevo.');
        }
    }

    root.addEventListener('click', (e) => {
        const likeBtn = e.target.closest('.wall-like-btn');
        if (!likeBtn?.dataset.likePostId) {
            return;
        }
        e.preventDefault();
        void toggleLike(Number(likeBtn.dataset.likePostId));
    });

    setupCommentInteractions(root, {
        postId: Number(post.id),
        postBaseUrl: config.postBaseUrl,
        axios,
        isAuthenticated: config.isAuthenticated === true,
        loginUrl: config.loginUrl,
        getCommentsWrap: () => document.getElementById('post-show-comments'),
        showToast: (msg) => showToast(toastEl, msg),
        applyCommentsCount: (pid, count) => syncCommentsCount(root, pid, count),
        updateHeadingCount: (count) => {
            const n = document.getElementById('post-show-heading-count');
            if (n) {
                n.textContent = `(${count})`;
            }
        },
    });

    const Echo = ensureEcho();
    if (!Echo || config.postId == null) {
        return;
    }

    async function reloadCommentsTreeRemote() {
        const wrap = document.getElementById('post-show-comments');
        if (!wrap) {
            return;
        }
        try {
            const { data } = await axios.get(`${config.postBaseUrl}/${config.postId}`);
            const p = data.post;
            wrap.innerHTML = renderCommentsTreeHtml(p.comments || [], {
                showReplyButtons: config.isAuthenticated === true,
            });
            syncCommentsCount(root, p.id, p.comments_count ?? 0);
            const heading = document.getElementById('post-show-heading-count');
            if (heading) {
                heading.textContent = `(${p.comments_count ?? 0})`;
            }
        } catch (err) {
            console.error(err);
        }
    }

    const channel = Echo.channel(`post.${config.postId}`);
    const moderationChannel = Echo.channel('posts.moderation');

    channel.listen('.post.like.updated', (e) => {
        if (Number(e.post_id) !== Number(config.postId)) {
            return;
        }
        root.querySelectorAll(`[data-like-post-id="${e.post_id}"]`).forEach((btn) => {
            const span = btn.querySelector('[data-like-count]');
            if (span) {
                span.textContent = String(e.likes_count);
            }
        });
    });

    channel.listen('.post.comment.created', async (e) => {
        if (Number(e.post_id) !== Number(config.postId)) {
            return;
        }
        const wrap = document.getElementById('post-show-comments');
        if (!wrap) {
            return;
        }
        const cid = e.comment?.id;
        if (cid != null && wrap.querySelector(`[data-comment-id="${cid}"]`)) {
            return;
        }
        syncCommentsCount(root, e.post_id, e.comments_count);
        const heading = document.getElementById('post-show-heading-count');
        if (heading) {
            heading.textContent = `(${e.comments_count})`;
        }
        if (e.comment?.parent_id) {
            await reloadCommentsTreeRemote();

            return;
        }
        const treeRoot = wrap.querySelector('.space-y-3');
        const html = renderCommentNodeHtml(e.comment, config.isAuthenticated === true);
        if (treeRoot) {
            treeRoot.insertAdjacentHTML('beforeend', html);
        } else {
            wrap.innerHTML = renderCommentsTreeHtml([e.comment], {
                showReplyButtons: config.isAuthenticated === true,
            });
        }
    });

    moderationChannel.listen('.post.moderation.updated', (e) => {
        if (Number(e?.post_id) !== Number(config.postId)) {
            return;
        }

        if ((e?.status || '') === 'rejected') {
            showToast(toastEl, 'La publicación fue retirada por moderación automática.');
            window.setTimeout(() => {
                window.location.href = '/dashboard';
            }, 900);
        }
    });

    window.addEventListener(
        'beforeunload',
        () => {
            Echo.leave(`post.${config.postId}`);
            Echo.leave('posts.moderation');
        },
        { once: true },
    );
}
