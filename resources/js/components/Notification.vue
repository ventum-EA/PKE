<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: Boolean,
    message: String,
    type: { type: String, default: 'success' },
});

// Errors should interrupt the screen reader; successes and info should be polite.
const role = computed(() => (props.type === 'error' ? 'alert' : 'status'));
const ariaLive = computed(() => (props.type === 'error' ? 'assertive' : 'polite'));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-from-class="translate-y-4 opacity-0"
            enter-active-class="transition-all duration-300"
            leave-to-class="translate-y-4 opacity-0"
            leave-active-class="transition-all duration-300">
            <div v-if="show" class="fixed bottom-6 right-6 z-[70] max-w-sm"
                :role="role"
                :aria-live="ariaLive"
                aria-atomic="true">
                <div :class="['px-5 py-3 rounded-xl shadow-xl border text-sm font-bold backdrop-blur',
                    type === 'success' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' :
                    type === 'error' ? 'bg-red-500/10 border-red-500/20 text-red-400' :
                    'bg-amber-500/10 border-amber-500/20 text-amber-400']">
                    {{ message }}
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
