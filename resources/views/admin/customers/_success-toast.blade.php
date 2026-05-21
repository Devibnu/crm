@if (session('success'))
    <div class="crm-success-toast" role="status" aria-live="polite" data-crm-success-toast>
        <div class="crm-success-toast-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
        <div>
            <strong>Berhasil</strong>
            <p>{{ session('success') }}</p>
        </div>
        <button type="button" aria-label="Close notification" data-crm-success-toast-close>
            <svg viewBox="0 0 24 24"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toast = document.querySelector('[data-crm-success-toast]');
            const closeButton = document.querySelector('[data-crm-success-toast-close]');

            if (!toast || !closeButton) {
                return;
            }

            const hideToast = () => {
                toast.classList.add('is-hiding');
                window.setTimeout(() => toast.remove(), 220);
            };

            closeButton.addEventListener('click', hideToast);
            window.setTimeout(hideToast, 4200);
        });
    </script>
@endif
