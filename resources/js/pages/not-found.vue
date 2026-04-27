<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();
</script>

<template>
    <div class="min-h-[calc(100vh-80px)] flex items-center justify-center px-4">
        <div class="text-center max-w-md">
            <div class="text-9xl font-black text-amber-500/30 mb-2" aria-hidden="true">404</div>
            <h1 class="text-2xl font-black text-white mb-3">{{ $t('errors.not_found_title') }}</h1>
            <p class="text-zinc-500 mb-8">{{ $t('errors.not_found_message') }}</p>

            <div class="flex flex-wrap items-center justify-center gap-3">
                <router-link
                    to="/"
                    class="px-5 py-2.5 bg-amber-500 text-black font-bold rounded-xl hover:bg-amber-400 transition-colors text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60"
                >
                    {{ $t('errors.go_home') }}
                </router-link>
                <button
                    type="button"
                    @click="$router.back()"
                    class="px-5 py-2.5 bg-zinc-800 text-white font-bold rounded-xl hover:bg-zinc-700 transition-colors text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500/60"
                >
                    {{ $t('errors.go_back') }}
                </button>
            </div>
        </div>
    </div>
</template>
