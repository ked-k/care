import axios from 'axios';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

/*
 | Axios setup
 */
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

/*
 | Alpine setup
 |
 | Livewire 4 already provides Alpine.
 | Do not import Alpine or call Alpine.start().
 */
document.addEventListener('alpine:init', () => {

    Alpine.plugin(collapse);
    Alpine.plugin(focus);

    /*
     | Global toast store
     */
    Alpine.store('toast', {
        items: [],
        _id: 0,

        push(message, type = 'success', timeout = 4000) {
            const id = ++this._id;

            this.items.push({
                id,
                message,
                type
            });

            if (timeout) {
                setTimeout(() => this.dismiss(id), timeout);
            }
        },

        dismiss(id) {
            this.items = this.items.filter((toast) => toast.id !== id);
        },
    });

    /*
     | Global confirm store
     */
    Alpine.store('confirm', {
        show: false,
        title: 'Are you sure?',
        message: '',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        tone: 'danger',
        icon: 'ik ik-alert-triangle',
        _onConfirm: null,

        open(opts = {}) {
            this.title = opts.title ?? 'Are you sure?';
            this.message = opts.message ?? '';
            this.confirmText = opts.confirmText ?? 'Confirm';
            this.cancelText = opts.cancelText ?? 'Cancel';
            this.tone = opts.tone ?? 'danger';
            this.icon = opts.icon ?? 'ik ik-alert-triangle';

            this._onConfirm = typeof opts.onConfirm === 'function'
                ? opts.onConfirm
                : null;

            this.show = true;
        },

        confirm() {
            const callback = this._onConfirm;

            this.show = false;
            this._onConfirm = null;

            if (callback) {
                callback();
            }
        },

        cancel() {
            this.show = false;
            this._onConfirm = null;
        },
    });

});

/*
 | Global toast helper
 */
window.toast = (message, type = 'success', timeout = 4000) => {
    Alpine.store('toast').push(message, type, timeout);
};

/*
 | Axios response handling
 */
window.axios.interceptors.response.use(
    (response) => response,

    (error) => {
        if (error.response?.status === 423) {
            window.toast(
                error.response.data?.message ||
                'This action is restricted in the live demo.',
                'warning'
            );
        }

        return Promise.reject(error);
    }
);