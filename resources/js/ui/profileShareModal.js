/**
 * Modal compartir perfil (sin Alpine). CSP: bundle Vite únicamente.
 */
export function initProfileShareModal() {
    const modal = document.getElementById('profile-share-modal');
    const openBtn = document.getElementById('profile-share-open');
    const closeBtn = document.getElementById('profile-share-close');
    const backdrop = document.getElementById('profile-share-backdrop');
    const panel = document.getElementById('profile-share-panel');
    const copyBtn = document.getElementById('profile-share-copy');
    const shareBtn = document.getElementById('profile-share-native');
    const urlEl = document.getElementById('profile-share-url');
    const copyLabel = copyBtn?.querySelector('[data-profile-share-copy-label]');
    const shareLabel = shareBtn?.querySelector('[data-profile-share-native-label]');

    if (!modal || !openBtn || !urlEl) {
        return;
    }

    const shareUrl = urlEl.textContent?.trim() || window.location.href;
    const defaultCopy = copyLabel?.textContent?.trim() || 'Copiar enlace';
    const defaultShare = shareLabel?.textContent?.trim() || 'Compartir';

    const openModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        requestAnimationFrame(() => {
            modal.dataset.state = 'open';
            panel?.setAttribute('data-state', 'open');
        });
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal.dataset.state = 'closed';
        panel?.setAttribute('data-state', 'closed');
        window.setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }, 220);
    };

    openBtn.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    copyBtn?.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(shareUrl);
            if (copyLabel) {
                copyLabel.textContent = 'Copiado';
            }
            window.setTimeout(() => {
                if (copyLabel) {
                    copyLabel.textContent = defaultCopy;
                }
            }, 1200);
        } catch (err) {
            console.error(err);
        }
    });

    shareBtn?.addEventListener('click', async () => {
        try {
            if (navigator.share) {
                await navigator.share({
                    title: 'Perfil de EcoShare',
                    text: 'Mira este perfil en EcoShare',
                    url: shareUrl,
                });

                return;
            }
            await navigator.clipboard.writeText(shareUrl);
            if (shareLabel) {
                shareLabel.textContent = 'Copiado';
            }
            window.setTimeout(() => {
                if (shareLabel) {
                    shareLabel.textContent = defaultShare;
                }
            }, 1200);
        } catch (err) {
            console.error(err);
        }
    });
}
