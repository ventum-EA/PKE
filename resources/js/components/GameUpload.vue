<script setup>
import { ref, computed, watch } from 'vue';
import { useGamesStore } from '../stores/games';
import { parsePgn, detectOpening } from '../services/chess';
import { useFocusTrap } from '../composables/useFocusTrap';

const emit = defineEmits(['close', 'created']);
const gamesStore = useGamesStore();
const isLoading = ref(false);
const errorMsg = ref('');
const parseInfo = ref(null);

const dialogRef = ref(null);
const isDialogOpen = computed(() => true);
useFocusTrap(dialogRef, {
    active: isDialogOpen,
    onEscape: () => emit('close'),
});

const form = ref({
    pgn: '',
    white_player: '',
    black_player: '',
    result: '*',
    opening_name: '',
    opening_eco: '',
    user_color: 'white',
    played_at: '',
    total_moves: 0,
});

const samplePgn = '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6 5. O-O Be7 6. Re1 b5 7. Bb3 d6 8. c3 O-O';

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => { form.value.pgn = ev.target.result; };
    reader.readAsText(file);
};

// Auto-detect opening and move count when PGN changes
watch(() => form.value.pgn, (pgn) => {
    if (!pgn?.trim()) {
        parseInfo.value = null;
        return;
    }
    try {
        const parsed = parsePgn(pgn);
        if (parsed.moves.length > 0) {
            parseInfo.value = {
                totalMoves: parsed.moves.length,
                valid: true,
            };
            form.value.total_moves = parsed.moves.length;

            // Auto-detect opening
            const opening = detectOpening(parsed.moves);
            if (opening && !form.value.opening_name) {
                form.value.opening_name = opening.name;
                form.value.opening_eco = opening.eco;
            }

            // Try to extract headers
            if (parsed.headers) {
                if (parsed.headers.White && !form.value.white_player) form.value.white_player = parsed.headers.White;
                if (parsed.headers.Black && !form.value.black_player) form.value.black_player = parsed.headers.Black;
                if (parsed.headers.Result) form.value.result = parsed.headers.Result;
                if (parsed.headers.Date && !form.value.played_at) {
                    const d = parsed.headers.Date.replace(/\./g, '-');
                    if (d.match(/\d{4}-\d{2}-\d{2}/)) form.value.played_at = d;
                }
            }
        } else {
            parseInfo.value = { totalMoves: 0, valid: false };
        }
    } catch {
        parseInfo.value = { totalMoves: 0, valid: false };
    }
});

const handleSubmit = async () => {
    if (!form.value.pgn.trim()) { errorMsg.value = 'PGN ir obligāts'; return; }
    isLoading.value = true;
    errorMsg.value = '';
    try {
        await gamesStore.createGame({ ...form.value });
        emit('created');
    } catch (error) {
        errorMsg.value = error.message || 'Kļūda saglabājot partiju';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4" @click.self="emit('close')">
        <div ref="dialogRef"
            role="dialog"
            aria-modal="true"
            aria-labelledby="game-upload-title"
            class="bg-zinc-900 border border-white/10 rounded-3xl w-full max-w-xl max-h-[90vh] overflow-y-auto shadow-2xl focus:outline-none">
            <div class="p-6 border-b border-white/5 flex items-center justify-between">
                <h2 id="game-upload-title" class="text-lg font-black text-white">⬆ Augšupielādēt partiju</h2>
                <button @click="emit('close')"
                    type="button"
                    aria-label="Aizvērt augšupielādes logu"
                    class="text-zinc-500 hover:text-white transition-colors text-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60 rounded">✕</button>
            </div>

            <div class="p-6 space-y-5">
                <div v-if="errorMsg" class="bg-red-500/10 border border-red-500/20 rounded-xl p-3">
                    <p class="text-sm text-red-400">{{ errorMsg }}</p>
                </div>

                <!-- PGN Input -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-zinc-500">PGN gājieni *</label>
                        <label class="text-xs text-amber-400 font-bold cursor-pointer hover:text-amber-300">
                            📁 Importēt .pgn failu
                            <input type="file" accept=".pgn,.txt" @change="handleFileUpload" class="hidden" />
                        </label>
                    </div>
                    <textarea v-model="form.pgn" rows="5" :placeholder="samplePgn"
                        class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white font-mono text-sm placeholder-zinc-700 focus:outline-none focus:border-amber-500/50 transition-all resize-none"></textarea>

                    <!-- Parse feedback -->
                    <div v-if="parseInfo" class="mt-2 text-xs">
                        <span v-if="parseInfo.valid" class="text-emerald-400">
                            ✓ Parsēti {{ parseInfo.totalMoves }} gājieni
                            <span v-if="form.opening_name" class="text-zinc-500 ml-2">
                                · {{ form.opening_eco }} {{ form.opening_name }}
                            </span>
                        </span>
                        <span v-else class="text-red-400">✕ Neizdevās parsēt PGN</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">Baltais spēlētājs</label>
                        <input v-model="form.white_player" type="text" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500/50 transition-all" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">Melnais spēlētājs</label>
                        <input v-model="form.black_player" type="text" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500/50 transition-all" />
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">Rezultāts</label>
                        <select v-model="form.result" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500/50">
                            <option value="*">Nezināms</option>
                            <option value="1-0">1-0</option>
                            <option value="0-1">0-1</option>
                            <option value="1/2-1/2">½-½</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">Jūsu krāsa</label>
                        <select v-model="form.user_color" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500/50">
                            <option value="white">♔ Baltais</option>
                            <option value="black">♚ Melnais</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">Datums</label>
                        <input v-model="form.played_at" type="date" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500/50" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">Atklātne</label>
                        <input v-model="form.opening_name" type="text" placeholder="auto-noteikta" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder-zinc-700 focus:outline-none focus:border-amber-500/50" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">ECO kods</label>
                        <input v-model="form.opening_eco" type="text" placeholder="auto" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder-zinc-700 focus:outline-none focus:border-amber-500/50" />
                    </div>
                </div>

                <button @click="handleSubmit" :disabled="isLoading || !form.pgn.trim() || (parseInfo && !parseInfo.valid)"
                    class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl disabled:opacity-40 transition-all shadow-lg shadow-amber-500/20 uppercase tracking-wider text-sm">
                    {{ isLoading ? 'Saglabā...' : 'Saglabāt partiju' }}
                </button>
            </div>
        </div>
    </div>
</template>
