<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    term: { type: String, required: true },
});

const { t, te } = useI18n();
const open = ref(false);

const glossaryKey = `glossary.${props.term}`;
const hasDefinition = te(glossaryKey);
</script>

<template>
    <span class="relative inline-block">
        <span v-if="hasDefinition"
            class="border-b border-dotted border-amber-400/40 cursor-help text-amber-400/90 hover:text-amber-400 transition-colors"
            @mouseenter="open = true"
            @mouseleave="open = false"
            @click="open = !open"
            @keydown.enter="open = !open"
            role="button"
            tabindex="0"
            :aria-describedby="'tip-' + term">
            <slot />
        </span>
        <span v-else><slot /></span>

        <transition name="tip">
            <span v-if="open && hasDefinition"
                :id="'tip-' + term"
                role="tooltip"
                class="absolute z-50 bottom-full left-1/2 -translate-x-1/2 mb-2 w-56 sm:w-64 p-3 bg-zinc-800 border border-amber-500/20 rounded-xl shadow-xl text-xs text-zinc-300 leading-relaxed pointer-events-none">
                <span class="font-bold text-amber-400 block mb-1">{{ $t('glossary.' + term + '_name') }}</span>
                {{ $t(glossaryKey) }}
                <span class="absolute top-full left-1/2 -translate-x-1/2 -mt-px w-2 h-2 bg-zinc-800 border-r border-b border-amber-500/20 rotate-45"></span>
            </span>
        </transition>
    </span>
</template>

<style scoped>
.tip-enter-active { transition: all 150ms ease; }
.tip-leave-active { transition: all 100ms ease; }
.tip-enter-from, .tip-leave-to { opacity: 0; transform: translate(-50%, 4px); }
</style>
