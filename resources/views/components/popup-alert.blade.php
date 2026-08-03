@php
    $popup = $popup ?? session('popup');

    $statusMsg = session('status') ?? session()->get('status');
    if (!$popup && $statusMsg) {
        $isPasswordPage = request()->routeIs('password.*') ||
                          request()->is('forgot-password*') ||
                          request()->is('reset-password*');
        $popup = [
            'type'    => 'success',
            'title'   => $isPasswordPage ? 'Permintaan Reset Password Diproses' : 'Informasi',
            'message' => $statusMsg,
        ];
    }

    if (!$popup && session('success')) {
        $msg = session('success');
        $msgClean = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}✅🗑️🔄❌⚠️]/u', '', $msg);
        
        $title = 'Berhasil';
        if (request()->is('admin*') && request('section') === 'users') {
            $title = 'Pengguna Berhasil Ditambahkan';
        } elseif (request()->is('admin*') && request('section') === 'threshold') {
            $title = 'Konfigurasi Berhasil Disimpan';
        }
        
        $popup = [
            'type'    => 'success',
            'title'   => $title,
            'message' => trim($msgClean),
        ];
    }

    if (!$popup && session('error')) {
        $msg = session('error');
        $msgClean = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}✅🗑️🔄❌⚠️]/u', '', $msg);
        
        $title = 'Gagal';
        if (request()->is('admin*') && (request('section') === 'threshold' || str_contains(url()->previous(), 'section=threshold'))) {
            $title = 'Konfigurasi Gagal Disimpan';
        } elseif (request()->is('admin*') && (request('section') === 'users' || str_contains(url()->previous(), 'section=users'))) {
            $title = 'Pengguna Gagal Ditambahkan';
        }
        
        $popup = [
            'type'    => 'error',
            'title'   => $title,
            'message' => trim($msgClean),
        ];
    }

    if (!$popup && isset($errors) && $errors->any()) {
        $title = 'Terjadi Kesalahan';
        $message = $errors->first();

        if (request()->routeIs('login') || request()->is('login')) {
            $title = 'Login Gagal';
            $emailErr = $errors->first('email');
            $passErr  = $errors->first('password');

            if ($emailErr && (str_contains($emailErr, 'credentials') || str_contains($emailErr, 'tidak sesuai') || str_contains($emailErr, 'failed') || str_contains($emailErr, 'match'))) {
                $message = 'Email atau password yang Anda masukkan tidak sesuai.';
            } elseif ($errors->has('email') && $errors->has('password')) {
                $message = 'Email dan password wajib diisi.';
            } else {
                $message = $errors->first();
            }
        } elseif (request()->is('admin*') || request()->routeIs('admin.*')) {
            if (request('section') === 'threshold' || str_contains(url()->previous(), 'section=threshold') || $errors->has('settings.*')) {
                $title = 'Konfigurasi Gagal Disimpan';
                $message = 'Periksa kembali nilai batas yang dimasukkan.';
            } else {
                $title = 'Pengguna Gagal Ditambahkan';
                $emailErr = $errors->first('email');
                $passErr  = $errors->first('password');

                if ($emailErr && (str_contains($emailErr, 'taken') || str_contains($emailErr, 'terdaftar') || str_contains($emailErr, 'unique'))) {
                    $message = 'Email tersebut sudah terdaftar pada sistem.';
                } elseif ($passErr && (str_contains($passErr, 'confirmation') || str_contains($passErr, 'konfirmasi'))) {
                    $message = 'Konfirmasi password tidak sesuai.';
                } else {
                    $message = $errors->first();
                }
            }
        }

        $popup = [
            'type'    => 'error',
            'title'   => $title,
            'message' => $message,
        ];
    }
@endphp

@if($popup)
<div id="smartfarm-popup-overlay"
     class="popup-overlay"
     role="presentation"
     onclick="closePopupCardOnOverlay(event)">

    <div id="smartfarm-popup-card"
         class="popup-card popup-type-{{ $popup['type'] ?? 'info' }}"
         role="alert"
         aria-live="assertive"
         aria-labelledby="popup-title"
         aria-describedby="popup-message"
         onclick="event.stopPropagation()">

        <button type="button"
                class="popup-close"
                onclick="closePopupCard()"
                aria-label="Tutup popup">
            &times;
        </button>

        <div class="popup-icon-wrapper">
            @if(($popup['type'] ?? 'info') === 'success')
                <svg class="popup-svg-icon popup-svg-success" viewBox="0 0 52 52">
                    <circle class="popup-circle-success" cx="26" cy="26" r="23" fill="none" stroke="#22c55e" stroke-width="3" />
                    <path class="popup-check-success" fill="none" stroke="#22c55e" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" d="M14 27l7 7 17-17" />
                </svg>
            @else
                <svg class="popup-svg-icon popup-svg-error" viewBox="0 0 52 52">
                    <circle class="popup-circle-error" cx="26" cy="26" r="23" fill="none" stroke="#ef4444" stroke-width="3" />
                    <path class="popup-cross-error" fill="none" stroke="#ef4444" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" d="M16 16l20 20M36 16L16 36" />
                </svg>
            @endif
        </div>

        <h3 id="popup-title" class="popup-title">
            {{ $popup['title'] ?? 'Notifikasi' }}
        </h3>

        <p id="popup-message" class="popup-message">
            {{ $popup['message'] ?? '' }}
        </p>

        <button type="button" class="popup-action-close" onclick="closePopupCard()">
            Tutup
        </button>
    </div>
</div>

<style>
.popup-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(3px);
    z-index: 999998;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    animation: popupFadeInOverlay 0.3s ease-out forwards;
}

.popup-card {
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
    animation: popupScaleInCard 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    font-family: 'Space Grotesk', system-ui, -apple-system, sans-serif;
    box-sizing: border-box;
}

.popup-close {
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

.popup-close:hover {
    color: #0f172a;
    background-color: #f1f5f9;
}

.popup-icon-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 16px;
}

.popup-svg-icon {
    width: 64px;
    height: 64px;
}

/* Animasi SVG Success */
.popup-circle-success {
    stroke-dasharray: 150;
    stroke-dashoffset: 150;
    animation: drawCircle 0.5s ease-out forwards;
}

.popup-check-success {
    stroke-dasharray: 50;
    stroke-dashoffset: 50;
    animation: drawCheck 0.35s 0.3s ease-out forwards;
}

/* Animasi SVG Error */
.popup-circle-error {
    stroke-dasharray: 150;
    stroke-dashoffset: 150;
    animation: drawCircle 0.5s ease-out forwards;
}

.popup-cross-error {
    stroke-dasharray: 60;
    stroke-dashoffset: 60;
    animation: drawCross 0.35s 0.3s ease-out forwards;
}

@keyframes drawCircle {
    to {
        stroke-dashoffset: 0;
    }
}

@keyframes drawCheck {
    to {
        stroke-dashoffset: 0;
    }
}

@keyframes drawCross {
    to {
        stroke-dashoffset: 0;
    }
}

.popup-title {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 8px 0;
    line-height: 1.3;
}

.popup-type-success .popup-title {
    color: #14532d;
}

.popup-type-error .popup-title {
    color: #7f1d1d;
}

.popup-message {
    font-size: 14px;
    color: #475569;
    margin: 0 0 22px 0;
    line-height: 1.5;
    word-break: break-word;
}

.popup-action-close {
    width: 100%;
    padding: 12px 18px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: transform 0.15s, background-color 0.15s, box-shadow 0.15s;
    font-family: inherit;
}

.popup-type-success .popup-action-close {
    background: #22c55e;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(34, 197, 94, 0.3);
}

.popup-type-success .popup-action-close:hover {
    background: #16a34a;
    transform: translateY(-1px);
}

.popup-type-error .popup-action-close {
    background: #ef4444;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3);
}

.popup-type-error .popup-action-close:hover {
    background: #dc2626;
    transform: translateY(-1px);
}

@keyframes popupFadeInOverlay {
    0% { opacity: 0; }
    100% { opacity: 1; }
}

@keyframes popupScaleInCard {
    0% {
        opacity: 0;
        transform: translate(-50%, -45%) scale(0.85);
    }
    100% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
}

@keyframes popupScaleOutCard {
    0% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
    100% {
        opacity: 0;
        transform: translate(-50%, -45%) scale(0.85);
    }
}

.popup-overlay-hide {
    opacity: 0 !important;
    transition: opacity 0.3s ease !important;
}

.popup-card-hide {
    animation: popupScaleOutCard 0.3s ease-in forwards !important;
}

@media (prefers-reduced-motion: reduce) {
    .popup-overlay,
    .popup-card,
    .popup-circle-success,
    .popup-check-success,
    .popup-circle-error,
    .popup-cross-error {
        animation-duration: 0.01s !important;
        transition-duration: 0.01s !important;
    }
}

@media (max-width: 480px) {
    .popup-card {
        width: calc(100vw - 32px);
        padding: 24px 18px 20px;
        border-radius: 16px;
    }
    .popup-title {
        font-size: 17px;
    }
    .popup-message {
        font-size: 13px;
    }
}
</style>

<script>
function closePopupCard() {
    var overlay = document.getElementById('smartfarm-popup-overlay');
    var card    = document.getElementById('smartfarm-popup-card');
    if (card) {
        card.classList.add('popup-card-hide');
    }
    if (overlay) {
        overlay.classList.add('popup-overlay-hide');
        setTimeout(function() {
            if (overlay && overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, 300);
    }
}

function closePopupCardOnOverlay(event) {
    if (event.target && event.target.id === 'smartfarm-popup-overlay') {
        closePopupCard();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var autoHideTimer = setTimeout(function() {
        closePopupCard();
    }, 9000); // Auto hide dalam 9 detik agar cukup waktu screenshot

    var card = document.getElementById('smartfarm-popup-card');
    if (card) {
        card.addEventListener('mouseenter', function() {
            clearTimeout(autoHideTimer);
        });
        card.addEventListener('mouseleave', function() {
            autoHideTimer = setTimeout(closePopupCard, 5000);
        });
    }
});
</script>
@endif
