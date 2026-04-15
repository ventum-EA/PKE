<script setup>
import { useI18n } from 'vue-i18n';
import { setLocale } from '../i18n';

const { locale } = useI18n();

const locales = [
    { code: 'lv', label: 'LV', flag: '🇱🇻' },
    { code: 'en', label: 'EN', flag: '🇬🇧' },
];

function switchLocale(code) {
    setLocale(code);
}
</script>

<template>
    <div class="flex items-center gap-0.5 rounded-lg border border-white/10 overflow-hidden" role="radiogroup" aria-label="Valodas izvēle">
        <button v-for="loc in locales" :key="loc.code"
            type="button"
            role="radio"
            :aria-checked="locale === loc.code"
            @click="switchLocale(loc.code)"
            :class="['px-2 py-1.5 text-[10px] font-black uppercase tracking-wider transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60',
                locale === loc.code
                    ? 'bg-amber-500/15 text-amber-400'
                    : 'text-zinc-500 hover:text-zinc-300 hover:bg-white/5']">
            {{ loc.flag }} {{ loc.label }}
        </button>
    </div>
</template>
