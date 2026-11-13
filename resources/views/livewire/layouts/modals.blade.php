<div>
    {{-- Global Confirmation Modal --}}
    <div x-data="{ confirmModal: { open: false, title: '', message: '', callback: null } }" @confirm-modal.window="confirmModal = { open: true, title: $event.detail.title, message: $event.detail.message, callback: $event.detail.callback }">
        <div x-show="confirmModal.open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" style="display: none;">
            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div x-show="confirmModal.open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative w-full max-w-md transform overflow-hidden rounded-lg bg-white dark:bg-slate-800 text-start shadow-xl">
                    <div class="absolute end-0 top-0 hidden pe-4 pt-4 sm:block">
                        <button @click="confirmModal.open = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" aria-label="Close">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div class="p-6">
                        <div x-show="confirmModal.title" class="mb-4">
                            <h3 x-text="confirmModal.title" class="text-lg font-semibold text-slate-900 dark:text-white"></h3>
                        </div>
                        <p x-text="confirmModal.message" class="text-slate-600 dark:text-slate-300"></p>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700 border-t border-slate-200 dark:border-slate-600 flex justify-end space-x-3">
                        <button @click="confirmModal.open = false" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-600 border border-slate-300 dark:border-slate-500 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-500">Cancel</button>
                        <button @click="confirmModal.callback(); confirmModal.open = false" class="px-4 py-2 text-sm font-medium text-white bg-rose-600 border border-transparent rounded-lg hover:bg-rose-700">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Global Loading Overlay --}}
    <div x-data="{ loading: false }" @show-loading.window="loading = true" @hide-loading.window="loading = false">
        <div x-show="loading" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" style="display: none;">
            <div class="bg-white dark:bg-slate-800 rounded-lg p-6 flex flex-col items-center space-y-4">
                <div class="animate-spin rounded-full h-10 w-10 border-3 border-indigo-600 border-t-transparent"></div>
                <p class="text-slate-700 dark:text-slate-300">Processing...</p>
            </div>
        </div>
    </div>

    {{-- Toast Notifications --}}
    <div x-data="{ toasts: [] }"
         @toast-add.window="handleToastAdd($event)"
         @toast-add="handleToastAdd($event)"
         @toast-remove.window="toasts = toasts.filter(t => t.id !== $event.detail)">
        <div class="fixed bottom-4 end-4 z-50 flex flex-col space-y-2" style="max-width: 480px;">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-show="true" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="transform opacity-0 translate-x-full" x-transition:enter-end="transform opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="transform opacity-100 translate-x-0" x-transition:leave-end="transform opacity-0 translate-x-full" class="flex items-start space-x-3 min-w-[320px] max-w-[440px] px-4 py-3 rounded-lg shadow-lg border-s-4 animate-slide-in" :style="getToastStyle(toast.type)" role="alert" aria-live="polite" aria-atomic="true">
                    <div class="flex-1 min-w-0">
                        <template x-if="toast.title">
                            <p class="font-medium text-white" x-text="toast.title" style="font-weight: 600; font-size: 0.925rem;"></p>
                        </template>
                        <p class="text-white mt-1" x-text="toast.message" style="font-weight: 600; font-size: 0.925rem; color: #ffffff !important;"></p>
                    </div>
                    <button @click="removeToast(toast.id)" class="text-white/90 hover:text-white shrink-0" aria-label="Dismiss" style="font-size: 1.125rem; line-height: 1;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </template>
        </div>

        <script>
            function handleToastAdd(event) {
                let payload = event.detail;
                // Handle Livewire v3/v4 event payload formats:
                // - event.detail = {type, title, message}
                // - event.detail = [{type, title, message}]
                // - event.detail = "string message"
                if (Array.isArray(payload)) {
                    payload = payload[0] || {};
                }
                let msg = typeof payload === 'string' ? payload : (payload.message || payload.text || payload.title || 'Action completed');
                let type = (typeof payload === 'object' && payload.type) ? payload.type : 'success';
                let title = (typeof payload === 'object' && payload.title) ? payload.title : '';
                let duration = (typeof payload === 'object' && payload.duration) ? payload.duration : 4500;

                const id = Date.now() + Math.random();
                this.toasts.push({ id, type, title, message: msg, duration });

                // Auto-dismiss
                setTimeout(() => {
                    this.removeToast(id);
                }, duration);
            }

            function removeToast(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }

            function getToastStyle(type) {
                const styles = {
                    success: 'background-color: #059669; color: #ffffff; border-left-color: #047857;',
                    error: 'background-color: #dc2626; color: #ffffff; border-left-color: #b91c1c;',
                    warning: 'background-color: #1e293b; color: #ffffff; border-left-color: #3b82f6;',
                    info: 'background-color: #1e293b; color: #ffffff; border-left-color: #3b82f6;',
                };
                return styles[type] || styles.success;
            }
        </script>
    </div>
</div>







