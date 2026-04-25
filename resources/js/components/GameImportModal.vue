<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useNotification } from '../composables/useNotification';
import api from '../services/api';

const emit = defineEmits(['close', 'imported']);
const { t } = useI18n();
const { notify } = useNotification();

const source = ref('lichess');
const username = ref('');
const maxGames = ref(30);
const isImporting = ref(false);
const result = ref(null);

const sourceLabel = computed(() => source.value === 'lichess' ? 'Lichess' : 'Chess.com');
const placeholder = computed(() => source.value === 'lichess' ? 'DrNykterstein' : 'MagnusCarlsen');

async function doImport() {
    if (!username.value.trim()) return;
    isImporting.value = true;
    result.value = null;

    try {
        const { data } = await api.post('/games/import', {
            source: source.value,
            username: username.value.trim(),
            max: maxGames.value,
        });

        result.value = data;

        if (data.imported > 0) {
            notify(t('import.success', { count: data.imported, source: sourceLabel.value }), 'success');
            emit('imported', data.imported);
        } else {
            notify(t('import.no_new'), 'info');
        }
    } catch (err) {
        const msg = err?.response?.data?.message || t('import.error');
        notify(msg, 'error');
    } finally {
        isImporting.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 backdrop-blur-sm p-4" @click.self="emit('close')" @keydown.escape="emit('close')">
            <div role="dialog" aria-modal="true" aria-labelledby="import-heading" class="bg-zinc-900 border border-white/10 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-5 border-b border-white/5">
                    <div class="flex items-center justify-between">
                        <h2 id="import-heading" class="text-lg font-black text-white flex items-center gap-2">
                            <span class="text-xl">📥</span> {{ $t('import.title') }}
                        </h2>
                        <button @click="emit('close')" class="text-zinc-500 hover:text-zinc-300 text-xl transition-colors">✕</button>
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">{{ $t('import.subtitle') }}</p>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Source selector -->
                    <div>
                        <label class="text-xs font-bold text-zinc-400 mb-2 block">{{ $t('import.platform') }}</label>
                        <div class="flex gap-2">
                            <button v-for="s in ['lichess', 'chesscom']" :key="s" @click="source = s"
                                :class="['flex-1 py-3 rounded-xl border text-sm font-bold transition-all text-center',
                                    source === s ? 'bg-amber-500/10 border-amber-500/20 text-amber-400' : 'border-white/5 text-zinc-500 hover:text-zinc-300']">
                                {{ s === 'lichess' ? '♞ Lichess' : '♜ Chess.com' }}
                            </button>
                        </div>
                    </div>

                    <!-- Username -->
                    <div>
                        <label class="text-xs font-bold text-zinc-400 mb-2 block">{{ $t('import.username_on', { platform: sourceLabel }) }}</label>
                        <input v-model="username" :placeholder="placeholder" @keyup.enter="doImport"
                            class="w-full bg-black/30 border border-white/5 rounded-xl px-4 py-3 text-sm text-zinc-300 placeholder-zinc-600 focus:outline-none focus:border-amber-500/20 transition-colors">
                    </div>

                    <!-- Max games slider -->
                    <div>
                        <label class="text-xs font-bold text-zinc-400 mb-2 flex justify-between">
                            <span>{{ $t('import.max_games') }}</span>
                            <span class="text-amber-400 tabular-nums">{{ maxGames }}</span>
                        </label>
                        <input type="range" v-model.number="maxGames" min="5" max="50" step="5" class="w-full accent-amber-500">
                    </div>

                    <!-- Import button -->
                    <button @click="doImport" :disabled="isImporting || !username.trim()"
                        class="w-full py-3.5 bg-amber-500 text-black font-black rounded-xl text-sm hover:bg-amber-400 disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2">
                        <span v-if="isImporting" class="w-4 h-4 border-2 border-black/30 border-t-black rounded-full animate-spin"></span>
                        {{ isImporting ? $t('import.importing') : $t('import.import_button') }}
                    </button>

                    <!-- Result -->
                    <div v-if="result" class="p-4 rounded-xl border" :class="result.imported > 0 ? 'bg-emerald-500/5 border-emerald-500/10' : 'bg-zinc-800/50 border-white/5'">
                        <div class="flex items-center gap-4 text-center">
                            <div class="flex-1">
                                <p class="text-xl font-black" :class="result.imported > 0 ? 'text-emerald-400' : 'text-zinc-500'">{{ result.imported }}</p>
                                <p class="text-[9px] font-bold uppercase tracking-wider text-zinc-600">{{ $t('import.imported_label') }}</p>
                            </div>
                            <div class="flex-1">
                                <p class="text-xl font-black text-zinc-500">{{ result.skipped }}</p>
                                <p class="text-[9px] font-bold uppercase tracking-wider text-zinc-600">{{ $t('import.skipped_label') }}</p>
                            </div>
                        </div>
                        <div v-if="result.errors?.length" class="mt-3 pt-3 border-t border-white/5">
                            <p class="text-[10px] text-red-400" v-for="err in result.errors.slice(0, 3)" :key="err">{{ err }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
