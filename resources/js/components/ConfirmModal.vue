<script setup>
import { ref, computed, toRefs } from 'vue';
import { useFocusTrap } from '../composables/useFocusTrap';

const props = defineProps({
    show: Boolean,
    title: String,
    message: String,
    type: { type: String, default: 'danger' },
});
const emit = defineEmits(['confirm', 'cancel']);

const dialogRef = ref(null);
const { show } = toRefs(props);
const isActive = computed(() => show.value);

useFocusTrap(dialogRef, {
    active: isActive,
    onEscape: () => emit('cancel'),
});
</script>

<template>
    <Teleport to="body">
        <Transition name="confirm-modal" appear>
            <div v-if="show"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
                @click.self="emit('cancel')">
                <div ref="dialogRef"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="confirm-modal-title"
                    aria-describedby="confirm-modal-desc"
                    class="confirm-modal-panel bg-zinc-900 border border-white/10 rounded-2xl w-full max-w-sm p-6 shadow-2xl focus:outline-none">
                    <h3 id="confirm-modal-title" class="text-lg font-black text-white mb-2">{{ title }}</h3>
                    <p id="confirm-modal-desc" class="text-sm text-zinc-400 mb-6">{{ message }}</p>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="emit('cancel')"
                            class="flex-1 py-2.5 text-sm font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-white active:scale-95 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                            Atcelt
                        </button>
                        <button type="button" @click="emit('confirm')"
                            :class="['flex-1 py-2.5 text-sm font-black rounded-xl active:scale-95 transition-all focus-visible:outline-none focus-visible:ring-2',
                                type === 'danger'
                                    ? 'bg-red-500 text-white hover:bg-red-600 focus-visible:ring-red-400/60'
                                    : 'bg-amber-500 text-black hover:bg-amber-400 focus-visible:ring-amber-400/60']">
                            Apstiprināt
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.confirm-modal-enter-active,
.confirm-modal-leave-active {
    transition: opacity 180ms ease;
}
.confirm-modal-enter-active .confirm-modal-panel,
.confirm-modal-leave-active .confirm-modal-panel {
    transition: transform 220ms cubic-bezier(0.34, 1.56, 0.64, 1), opacity 180ms ease;
}
.confirm-modal-enter-from,
.confirm-modal-leave-to {
    opacity: 0;
}
.confirm-modal-enter-from .confirm-modal-panel,
.confirm-modal-leave-to .confirm-modal-panel {
    transform: scale(0.92) translateY(8px);
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .confirm-modal-enter-active,
    .confirm-modal-leave-active,
    .confirm-modal-enter-active .confirm-modal-panel,
    .confirm-modal-leave-active .confirm-modal-panel {
        transition: opacity 120ms ease;
    }
    .confirm-modal-enter-from .confirm-modal-panel,
    .confirm-modal-leave-to .confirm-modal-panel {
        transform: none;
    }
}
</style>
