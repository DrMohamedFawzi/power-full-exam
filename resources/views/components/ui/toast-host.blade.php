{{--
    Client-side toasts. Push one from anywhere with:
        window.aegis.toast('تم الحفظ', 'success')
--}}
<div x-data="toastHost" class="toast toast-start toast-bottom z-50" role="status" aria-live="polite">
    <template x-for="toast in toasts" :key="toast.id">
        <div class="alert alert-soft animate-in shadow-lg" :class="`alert-${toast.type}`">
            <span x-text="toast.message"></span>
            <button type="button" class="btn btn-ghost btn-xs" @click="dismiss(toast.id)" aria-label="إغلاق">✕</button>
        </div>
    </template>
</div>
