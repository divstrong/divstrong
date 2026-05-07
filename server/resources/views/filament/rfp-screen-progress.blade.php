<div
    x-data="{
        open: false,
        progress: 0,
        circumference: 2 * Math.PI * 54,
        timer: null,
        startTime: null,
        steps: [
            { at: 0,   key: 'upload',   label: 'Uploading document' },
            { at: 12,  key: 'extract',  label: 'Extracting text' },
            { at: 30,  key: 'read',     label: 'Reading content' },
            { at: 55,  key: 'analyze',  label: 'Analyzing requirements' },
            { at: 78,  key: 'score',    label: 'Scoring fit' },
            { at: 92,  key: 'finalize', label: 'Finalizing analysis' },
        ],
        get dashOffset() {
            return this.circumference * (1 - this.progress / 100);
        },
        get currentStepKey() {
            return [...this.steps].reverse().find(s => this.progress >= s.at)?.key ?? 'upload';
        },
        stepStatus(step) {
            const idx = this.steps.findIndex(s => s.key === step.key);
            const currentIdx = this.steps.findIndex(s => s.key === this.currentStepKey);
            if (idx < currentIdx) return 'done';
            if (idx === currentIdx) return 'active';
            return 'pending';
        },
        start() {
            if (this.open) return;
            this.open = true;
            this.progress = 0;
            this.startTime = Date.now();
            document.body.style.overflow = 'hidden';

            this.timer = setInterval(() => {
                const elapsed = (Date.now() - this.startTime) / 1000;
                this.progress = Math.min(95, 95 * (1 - Math.exp(-elapsed / 12)));
            }, 150);
        },
        stop() {
            this.open = false;
            this.progress = 0;
            document.body.style.overflow = '';
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
    }"
    x-init="
        // Click handler on the modal's Screen submit button — most reliable trigger.
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('button, [role=button]');
            if (!btn) return;
            const txt = (btn.innerText || '').trim().toLowerCase();
            if (txt === 'screen' || txt === 'screen rfp' || txt.startsWith('screen ')) {
                // Only fire if a create-action modal form is currently visible.
                const inModal = btn.closest('[data-modal-id], .fi-modal, [x-data*=modal]') !== null
                    || document.querySelector('.fi-modal-window') !== null;
                if (inModal) {
                    setTimeout(() => start(), 50);
                }
            }
        }, true);

        document.addEventListener('livewire:initialized', () => {
            Livewire.hook('commit', ({ succeed, fail }) => {
                fail(() => stop());
                succeed(({ snapshot }) => {
                    try {
                        const parsed = JSON.parse(snapshot);
                        const errors = parsed?.memo?.errors ?? {};
                        if (Object.keys(errors).length) stop();
                    } catch (e) {}
                });
            });
        });
    "
    x-show="open"
    x-cloak
    x-transition.opacity.duration.300ms
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-white dark:bg-gray-950"
>
    <div class="w-full max-w-xl px-6 text-center">
        {{-- Circular progress with document icon --}}
        <div class="relative w-56 h-56 mx-auto">
            <svg class="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 120 120">
                <circle cx="60" cy="60" r="54" fill="none" stroke="#f3f4f6" class="dark:stroke-gray-800" stroke-width="6"/>
                <circle cx="60" cy="60" r="54" fill="none" stroke="#ed2537" stroke-width="6"
                        stroke-linecap="round"
                        :stroke-dasharray="circumference"
                        :stroke-dashoffset="dashOffset"
                        style="transition: stroke-dashoffset 250ms cubic-bezier(0.4, 0, 0.2, 1); filter: drop-shadow(0 0 6px rgba(237, 37, 55, 0.35));"/>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-2 animate-pulse" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                <span class="text-5xl font-bold text-gray-900 dark:text-white tabular-nums leading-none" x-text="Math.round(progress) + '%'"></span>
            </div>
        </div>

        {{-- Heading --}}
        <h2 class="mt-10 text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
            Screening your RFP
        </h2>
        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
            Hang tight — Claude is reading the document and scoring fit.
        </p>

        {{-- Step checklist --}}
        <div class="mt-8 inline-flex flex-col items-start gap-2.5 text-left">
            <template x-for="step in steps" :key="step.key">
                <div class="flex items-center gap-3 transition-opacity duration-300"
                     :class="stepStatus(step) === 'pending' ? 'opacity-40' : 'opacity-100'">
                    {{-- Status indicator --}}
                    <div class="relative flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center"
                         :class="{
                            'bg-emerald-100 dark:bg-emerald-900/30': stepStatus(step) === 'done',
                            'bg-red-100 dark:bg-red-900/30': stepStatus(step) === 'active',
                            'bg-gray-100 dark:bg-gray-800': stepStatus(step) === 'pending',
                         }">
                        {{-- Done: checkmark --}}
                        <svg x-show="stepStatus(step) === 'done'" class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{-- Active: pulsing dot --}}
                        <span x-show="stepStatus(step) === 'active'" class="w-2 h-2 rounded-full bg-[#ed2537] animate-ping absolute"></span>
                        <span x-show="stepStatus(step) === 'active'" class="w-2 h-2 rounded-full bg-[#ed2537]"></span>
                        {{-- Pending: empty dot --}}
                        <span x-show="stepStatus(step) === 'pending'" class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-600"></span>
                    </div>
                    <span class="text-sm font-medium"
                          :class="{
                            'text-gray-900 dark:text-white': stepStatus(step) === 'active',
                            'text-gray-500 dark:text-gray-400': stepStatus(step) === 'done',
                            'text-gray-400 dark:text-gray-500': stepStatus(step) === 'pending',
                          }"
                          x-text="step.label"></span>
                </div>
            </template>
        </div>

        <p class="mt-10 text-xs text-gray-400 dark:text-gray-500">
            Usually 15–45 seconds. Please don't close this tab.
        </p>
    </div>
</div>
