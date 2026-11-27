<style>
    .sb-toast-stack {
        position: fixed;
        top: 1.15rem;
        right: 1.15rem;
        z-index: 1095;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        width: min(390px, calc(100vw - 1.5rem));
        pointer-events: none;
    }

    .sb-toast-card {
        pointer-events: auto;
        background: #ffffff;
        border: 1px solid #e4e4e7;
        border-left-width: 4px;
        border-radius: 12px;
        box-shadow: 0 14px 32px rgba(24, 24, 27, 0.14);
        padding: 0.85rem 0.95rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        position: relative;
        overflow: hidden;
        animation: sbToastIn 0.24s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .sb-toast-card.sb-toast-success { border-left-color: #16a34a; }
    .sb-toast-card.sb-toast-error { border-left-color: #dc2626; }
    .sb-toast-card.sb-toast-warning { border-left-color: #d97706; }
    .sb-toast-card.sb-toast-info { border-left-color: #2563eb; }

    .sb-toast-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.15rem;
    }
    .sb-toast-success .sb-toast-icon { background: #dcfce7; color: #15803d; }
    .sb-toast-error .sb-toast-icon { background: #fee2e2; color: #b91c1c; }
    .sb-toast-warning .sb-toast-icon { background: #fef3c7; color: #b45309; }
    .sb-toast-info .sb-toast-icon { background: #dbeafe; color: #1d4ed8; }

    .sb-toast-progress {
        position: absolute;
        left: 0;
        bottom: 0;
        height: 3px;
        width: 100%;
        transform-origin: left center;
        animation: sbToastProgress 5s linear forwards;
    }
    .sb-toast-success .sb-toast-progress { background: #16a34a; }
    .sb-toast-error .sb-toast-progress { background: #dc2626; }
    .sb-toast-warning .sb-toast-progress { background: #d97706; }
    .sb-toast-info .sb-toast-progress { background: #2563eb; }

    .sb-toast-closing {
        opacity: 0;
        transform: translateX(18px);
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    @keyframes sbToastIn {
        from { opacity: 0; transform: translateY(-10px) translateX(12px); }
        to { opacity: 1; transform: translateY(0) translateX(0); }
    }
    @keyframes sbToastProgress {
        from { transform: scaleX(1); }
        to { transform: scaleX(0); }
    }
</style>

<!-- Stack Notifikasi Custom Sentosa Bangunan -->
<div id="sb-toast-stack" class="sb-toast-stack d-print-none" aria-live="polite">
    @if(session('success'))
        <div class="sb-toast-card sb-toast-success" data-sb-auto-dismiss="5200" role="alert">
            <span class="sb-toast-icon"><i class="ph-fill ph-check-circle"></i></span>
            <div class="flex-grow-1 small">
                <div class="fw-bold text-dark">Berhasil</div>
                <div class="text-secondary mt-1">{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close btn-sm mt-1" onclick="window.sbCloseToast(this.closest('.sb-toast-card'))" aria-label="Tutup"></button>
            <div class="sb-toast-progress"></div>
        </div>
    @endif

    @if(session('error'))
        <div class="sb-toast-card sb-toast-error" data-sb-auto-dismiss="8000" role="alert">
            <span class="sb-toast-icon"><i class="ph-fill ph-warning-circle"></i></span>
            <div class="flex-grow-1 small">
                <div class="fw-bold text-dark">Gagal / Perhatian</div>
                <div class="text-secondary mt-1">{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close btn-sm mt-1" onclick="window.sbCloseToast(this.closest('.sb-toast-card'))" aria-label="Tutup"></button>
            <div class="sb-toast-progress" style="animation-duration: 8s;"></div>
        </div>
    @endif

    @if($errors->any())
        <div class="sb-toast-card sb-toast-error" data-sb-auto-dismiss="9000" role="alert">
            <span class="sb-toast-icon"><i class="ph-fill ph-shield-warning"></i></span>
            <div class="flex-grow-1 small">
                <div class="fw-bold text-dark">Periksa Input Anda</div>
                <ul class="mb-0 ps-3 mt-1 text-secondary">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close btn-sm mt-1" onclick="window.sbCloseToast(this.closest('.sb-toast-card'))" aria-label="Tutup"></button>
            <div class="sb-toast-progress" style="animation-duration: 9s;"></div>
        </div>
    @endif
</div>

<!-- Modal Konfirmasi Custom Sentosa (Pengganti dialog confirm() bawaan browser) -->
<div class="modal fade d-print-none" id="sbConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 44px; height: 44px; background: #fef3c7; color: #b45309; font-size: 1.4rem;">
                        <i class="ph-fill ph-question"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold text-dark mb-1" id="sbConfirmTitle">Konfirmasi Tindakan</h6>
                        <p class="small text-secondary mb-0" id="sbConfirmMessage">Apakah Anda yakin ingin melanjutkan tindakan ini?</p>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-light border btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-dark btn-sm px-3 fw-semibold" id="sbConfirmProceedBtn">Ya, Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.sbCloseToast = function (el) {
        if (!el || el.dataset.sbClosing === '1') return;
        el.dataset.sbClosing = '1';
        el.classList.add('sb-toast-closing');
        setTimeout(() => el.remove(), 220);
    };

    window.sbPlayChime = function () {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            const ctx = new Ctx();
            const now = ctx.currentTime;
            [660, 880].forEach((freq, idx) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, now + idx * 0.11);
                gain.gain.setValueAtTime(0.06, now + idx * 0.11);
                gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.11 + 0.18);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now + idx * 0.11);
                osc.stop(now + idx * 0.11 + 0.19);
            });
        } catch (e) {}
    };

    window.sbToast = function (message, type = 'info', options = {}) {
        const stack = document.getElementById('sb-toast-stack');
        if (!stack) return;

        const normType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
        const titles = {
            success: options.title || 'Berhasil',
            error: options.title || 'Perhatian',
            warning: options.title || 'Peringatan',
            info: options.title || 'Informasi',
        };
        const icons = {
            success: 'ph-fill ph-check-circle',
            error: 'ph-fill ph-warning-circle',
            warning: 'ph-fill ph-bell-ringing',
            info: 'ph-fill ph-info',
        };

        const duration = Number(options.duration || 5200);
        const card = document.createElement('div');
        card.className = `sb-toast-card sb-toast-${normType}`;
        card.setAttribute('role', 'alert');

        const safeAction = options.actionUrl
            ? `<a href="${options.actionUrl}" class="btn btn-dark btn-sm mt-2 py-1 px-2">${options.actionLabel || 'Lihat Detail'}</a>`
            : '';

        card.innerHTML = `
            <span class="sb-toast-icon"><i class="${icons[normType]}"></i></span>
            <div class="flex-grow-1 small">
                <div class="fw-bold text-dark"></div>
                <div class="text-secondary mt-1 sb-toast-body-text"></div>
                ${safeAction}
            </div>
            <button type="button" class="btn-close btn-sm mt-1" aria-label="Tutup"></button>
            <div class="sb-toast-progress" style="animation-duration: ${duration}ms;"></div>
        `;
        card.querySelector('.fw-bold').textContent = titles[normType];
        card.querySelector('.sb-toast-body-text').textContent = String(message || '');
        card.querySelector('.btn-close').addEventListener('click', () => window.sbCloseToast(card));

        stack.appendChild(card);
        if (options.chime) window.sbPlayChime();
        setTimeout(() => window.sbCloseToast(card), duration);
    };

    window.sbConfirm = function (message, title = 'Konfirmasi Tindakan') {
        return new Promise((resolve) => {
            const modalEl = document.getElementById('sbConfirmModal');
            if (!modalEl || !window.bootstrap || !window.bootstrap.Modal) {
                resolve(true);
                return;
            }
            document.getElementById('sbConfirmTitle').textContent = title;
            document.getElementById('sbConfirmMessage').textContent = message;

            const proceedBtn = document.getElementById('sbConfirmProceedBtn');
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            let decided = false;

            const onProceed = () => {
                decided = true;
                cleanup();
                modal.hide();
                resolve(true);
            };
            const onHide = () => {
                if (!decided) {
                    cleanup();
                    resolve(false);
                }
            };
            const cleanup = () => {
                proceedBtn.removeEventListener('click', onProceed);
                modalEl.removeEventListener('hidden.bs.modal', onHide);
            };

            proceedBtn.addEventListener('click', onProceed);
            modalEl.addEventListener('hidden.bs.modal', onHide);
            modal.show();
        });
    };

    // Gantikan alert() bawaan browser dengan toast custom
    window.alert = function (msg) {
        window.sbToast(msg, 'warning', { title: 'Pemberitahuan' });
    };

    document.addEventListener('DOMContentLoaded', function () {
        // Auto-dismiss flash toast bawaan server
        document.querySelectorAll('#sb-toast-stack [data-sb-auto-dismiss]').forEach(function (card) {
            const ms = Number(card.getAttribute('data-sb-auto-dismiss')) || 5200;
            setTimeout(() => window.sbCloseToast(card), ms);
        });

        // Konversi otomatis seluruh form onsubmit="return confirm('...')" ke Modal Konfirmasi Custom Sentosa
        document.querySelectorAll('form[onsubmit]').forEach(function (form) {
            const raw = form.getAttribute('onsubmit') || '';
            const match = raw.match(/confirm\((['"`])([\s\S]*?)\1\)/);
            if (match && match[2]) {
                form.dataset.sbConfirm = match[2];
                form.removeAttribute('onsubmit');
            }
        });

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form || !form.dataset.sbConfirm || form.dataset.sbConfirmed === '1') {
                return;
            }
            e.preventDefault();
            e.stopPropagation();

            window.sbConfirm(form.dataset.sbConfirm).then((ok) => {
                if (ok) {
                    form.dataset.sbConfirmed = '1';
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.classList.add('opacity-75');
                    }
                    form.submit();
                }
            });
        }, true);
    });
</script>
