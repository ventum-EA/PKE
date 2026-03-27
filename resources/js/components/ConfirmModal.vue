<script setup>
defineProps({ show: Boolean, title: String, message: String, type: { type: String, default: 'danger' } });
const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="emit('cancel')">
            <div class="bg-zinc-900 border border-white/10 rounded-2xl w-full max-w-sm p-6 shadow-2xl">
                <h3 class="text-lg font-black text-white mb-2">{{ title }}</h3>
                <p class="text-sm text-zinc-400 mb-6">{{ message }}</p>
                <div class="flex items-center gap-3">
                    <button @click="emit('cancel')"
                        class="flex-1 py-2.5 text-sm font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-white transition-all">
                        Atcelt
                    </button>
                    <button @click="emit('confirm')"
                        :class="['flex-1 py-2.5 text-sm font-black rounded-xl transition-all',
                            type === 'danger' ? 'bg-red-500 text-white hover:bg-red-600' : 'bg-amber-500 text-black hover:bg-amber-400']">
                        Apstiprināt
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
