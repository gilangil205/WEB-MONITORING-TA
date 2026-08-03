<div id="smartfarm-confirm-overlay"
     class="confirm-overlay"
     role="presentation"
     aria-hidden="true"
     onclick="closeConfirmDialogOnOverlay(event)"
     style="display: none;">

    <div id="smartfarm-confirm-card"
         class="confirm-card confirm-type-danger"
         role="dialog"
         aria-modal="true"
         aria-labelledby="smartfarm-confirm-title"
         aria-describedby="smartfarm-confirm-message"
         onclick="event.stopPropagation()">

        <button type="button"
                id="smartfarm-confirm-close"
                class="confirm-close"
                onclick="closeConfirmDialog()"
                aria-label="Tutup modal konfirmasi">
            &times;
        </button>

        <div class="confirm-icon-wrapper" id="smartfarm-confirm-icon-wrapper">
            <!-- Icon rendered dynamically by JS -->
        </div>

        <h3 id="smartfarm-confirm-title" class="confirm-title">
            Konfirmasi
        </h3>

        <p id="smartfarm-confirm-message" class="confirm-message">
            Apakah Anda yakin ingin melanjutkan tindakan ini?
        </p>

        <div class="confirm-actions">
            <button type="button"
                    id="smartfarm-confirm-cancel"
                    class="confirm-btn-cancel"
                    onclick="closeConfirmDialog()">
                Batal
            </button>

            <button type="button"
                    id="smartfarm-confirm-proceed"
                    class="confirm-btn-proceed">
                Ya, Lanjutkan
            </button>
        </div>
    </div>
</div>

<style>
.confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(3px);
    z-index: 999998;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    animation: confirmFadeInOverlay 0.25s ease-out forwards;
}

.confirm-card {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.9);
    z-index: 999999;
    width: 420px;
    max-width: calc(100vw - 32px);
    background: #ffffff;
    border-radius: 20px;
    padding: 28px 24px 24px;
    text-align: center;
    box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.25), 0 8px 16px -4px rgba(15, 23, 42, 0.1);
    border: 1px solid #f1f5f9;
    animation: confirmScaleInCard 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    font-family: 'Space Grotesk', system-ui, -apple-system, sans-serif;
    box-sizing: border-box;
}

.confirm-close {
    position: absolute;
    top: 14px;
    right: 16px;
    background: transparent;
    border: none;
    font-size: 22px;
    line-height: 1;
    color: #94a3b8;
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.15s, background-color 0.15s;
}

.confirm-close:hover {
    color: #0f172a;
    background-color: #f1f5f9;
}

.confirm-icon-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 16px;
}

.confirm-svg-icon {
    width: 60px;
    height: 60px;
}

/* SVG Animations */
.confirm-circle-anim {
    stroke-dasharray: 150;
    stroke-dashoffset: 150;
    animation: confirmDrawCircle 0.4s ease-out forwards;
}

.confirm-path-anim {
    stroke-dasharray: 60;
    stroke-dashoffset: 60;
    animation: confirmDrawPath 0.3s 0.25s ease-out forwards;
}

@keyframes confirmDrawCircle {
    to { stroke-dashoffset: 0; }
}

@keyframes confirmDrawPath {
    to { stroke-dashoffset: 0; }
}

.confirm-title {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 8px 0;
    line-height: 1.3;
}

.confirm-message {
    font-size: 14px;
    color: #475569;
    margin: 0 0 24px 0;
    line-height: 1.5;
    word-break: break-word;
}

.confirm-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.confirm-btn-cancel,
.confirm-btn-proceed {
    flex: 1;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: transform 0.15s, background-color 0.15s, box-shadow 0.15s;
    font-family: inherit;
}

.confirm-btn-cancel {
    background: #f1f5f9;
    color: #475569;
}

.confirm-btn-cancel:hover {
    background: #e2e8f0;
    color: #0f172a;
}

/* Danger Type (Hapus) */
.confirm-type-danger .confirm-btn-proceed {
    background: #ef4444;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3);
}
.confirm-type-danger .confirm-btn-proceed:hover {
    background: #dc2626;
    transform: translateY(-1px);
}

/* Success Type (Simpan) */
.confirm-type-success .confirm-btn-proceed {
    background: #22c55e;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(34, 197, 94, 0.3);
}
.confirm-type-success .confirm-btn-proceed:hover {
    background: #16a34a;
    transform: translateY(-1px);
}

/* Warning Type (Reset) */
.confirm-type-warning .confirm-btn-proceed {
    background: #f97316;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(249, 115, 22, 0.3);
}
.confirm-type-warning .confirm-btn-proceed:hover {
    background: #ea580c;
    transform: translateY(-1px);
}

.confirm-btn-proceed:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

@keyframes confirmFadeInOverlay {
    0% { opacity: 0; }
    100% { opacity: 1; }
}

@keyframes confirmScaleInCard {
    0% {
        opacity: 0;
        transform: translate(-50%, -45%) scale(0.85);
    }
    100% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
}

@keyframes confirmScaleOutCard {
    0% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
    100% {
        opacity: 0;
        transform: translate(-50%, -45%) scale(0.85);
    }
}

.confirm-overlay-hide {
    opacity: 0 !important;
    transition: opacity 0.25s ease !important;
}

.confirm-card-hide {
    animation: confirmScaleOutCard 0.25s ease-in forwards !important;
}

@media (prefers-reduced-motion: reduce) {
    .confirm-overlay,
    .confirm-card,
    .confirm-circle-anim,
    .confirm-path-anim {
        animation-duration: 0.01s !important;
        transition-duration: 0.01s !important;
    }
}

@media (max-width: 480px) {
    .confirm-card {
        width: calc(100vw - 32px);
        padding: 24px 18px 20px;
        border-radius: 16px;
    }
    .confirm-actions {
        flex-direction: column-reverse;
        gap: 8px;
    }
}
</style>

<script>
let smartfarmPendingForm = null;
let smartfarmPendingCallback = null;
let smartfarmLastTriggerElement = null;

const smartfarmConfirmIcons = {
    danger: `<svg class="confirm-svg-icon" viewBox="0 0 52 52">
                <circle class="confirm-circle-anim" cx="26" cy="26" r="23" fill="none" stroke="#ef4444" stroke-width="3" />
                <path class="confirm-path-anim" fill="none" stroke="#ef4444" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" d="M16 16l20 20M36 16L16 36" />
             </svg>`,
    success: `<svg class="confirm-svg-icon" viewBox="0 0 52 52">
                <circle class="confirm-circle-anim" cx="26" cy="26" r="23" fill="none" stroke="#22c55e" stroke-width="3" />
                <path class="confirm-path-anim" fill="none" stroke="#22c55e" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" d="M14 27l7 7 17-17" />
              </svg>`,
    warning: `<svg class="confirm-svg-icon" viewBox="0 0 52 52">
                <circle class="confirm-circle-anim" cx="26" cy="26" r="23" fill="none" stroke="#f97316" stroke-width="3" />
                <path class="confirm-path-anim" fill="none" stroke="#f97316" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" d="M26 15v14M26 35h.01" />
              </svg>`
};

function openConfirmModal(config) {
    const overlay = document.getElementById('smartfarm-confirm-overlay');
    const card    = document.getElementById('smartfarm-confirm-card');
    const iconWrapper = document.getElementById('smartfarm-confirm-icon-wrapper');
    const titleEl   = document.getElementById('smartfarm-confirm-title');
    const msgEl     = document.getElementById('smartfarm-confirm-message');
    const proceedBtn = document.getElementById('smartfarm-confirm-proceed');

    if (!overlay || !card) return;

    smartfarmLastTriggerElement = document.activeElement;
    smartfarmPendingForm = config.form || null;
    smartfarmPendingCallback = config.callback || null;

    const type = config.type || 'danger';
    card.className = 'confirm-card confirm-type-' + type;

    iconWrapper.innerHTML = smartfarmConfirmIcons[type] || smartfarmConfirmIcons.danger;
    titleEl.textContent = config.title || 'Konfirmasi';
    msgEl.textContent = config.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
    proceedBtn.textContent = config.buttonText || 'Ya, Lanjutkan';
    proceedBtn.disabled = false;

    overlay.classList.remove('confirm-overlay-hide');
    card.classList.remove('confirm-card-hide');
    overlay.style.display = 'flex';
    overlay.setAttribute('aria-hidden', 'false');

    document.body.style.overflow = 'hidden';
    proceedBtn.focus();
}

function closeConfirmDialog() {
    const overlay = document.getElementById('smartfarm-confirm-overlay');
    const card    = document.getElementById('smartfarm-confirm-card');
    if (!overlay || !card) return;

    card.classList.add('confirm-card-hide');
    overlay.classList.add('confirm-overlay-hide');

    setTimeout(function() {
        overlay.style.display = 'none';
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        smartfarmPendingForm = null;
        smartfarmPendingCallback = null;
        if (smartfarmLastTriggerElement && typeof smartfarmLastTriggerElement.focus === 'function') {
            smartfarmLastTriggerElement.focus();
        }
    }, 250);
}

function closeConfirmDialogOnOverlay(event) {
    if (event.target && event.target.id === 'smartfarm-confirm-overlay') {
        closeConfirmDialog();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Event delegation untuk form konfirmasi
    document.body.addEventListener('submit', function(event) {
        const form = event.target.closest('.js-confirm-form');
        if (!form) return;

        event.preventDefault();

        const title      = form.getAttribute('data-confirm-title') || 'Konfirmasi';
        const message    = form.getAttribute('data-confirm-message') || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
        const buttonText = form.getAttribute('data-confirm-button') || 'Ya, Lanjutkan';
        const type       = form.getAttribute('data-confirm-type') || 'danger';

        openConfirmModal({
            title: title,
            message: message,
            buttonText: buttonText,
            type: type,
            form: form
        });
    });

    const proceedBtn = document.getElementById('smartfarm-confirm-proceed');
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function() {
            if (smartfarmPendingForm) {
                proceedBtn.disabled = true;
                const activeForm = smartfarmPendingForm;
                closeConfirmDialog();
                HTMLFormElement.prototype.submit.call(activeForm);
            } else if (typeof smartfarmPendingCallback === 'function') {
                proceedBtn.disabled = true;
                const callback = smartfarmPendingCallback;
                closeConfirmDialog();
                callback();
            }
        });
    }

    // Escape key handler
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const overlay = document.getElementById('smartfarm-confirm-overlay');
            if (overlay && overlay.style.display !== 'none') {
                closeConfirmDialog();
            }
        }
    });
});
</script>
