<script setup>
import { computed } from 'vue';

const props = defineProps({ data: Array });

const maxGames = computed(() => Math.max(...(props.data || []).map(d => d.total_games), 1));
const chartData = computed(() => (props.data || []).slice(-30));
</script>

<template>
    <div v-if="chartData.length" class="space-y-3">
        <div class="flex items-end gap-[2px] h-32">
            <div v-for="(day, i) in chartData" :key="day.date" class="flex-1 flex flex-col items-center justify-end gap-[1px]" :title="day.date">
                <div class="w-full rounded-t bg-zinc-700/50 relative overflow-hidden"
                    :style="{ height: Math.max(4, (day.total_games / maxGames) * 100) + '%' }">
                    <div class="absolute bottom-0 w-full bg-gradient-to-t from-amber-500 to-amber-400 rounded-t"
                        :style="{ height: (day.wins / Math.max(day.total_games, 1)) * 100 + '%' }"></div>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-between text-[10px] text-zinc-600">
            <span>{{ chartData[0]?.date }}</span>
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400"></span> Uzvaras</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-zinc-700"></span> Kopā</span>
            </div>
            <span>{{ chartData[chartData.length - 1]?.date }}</span>
        </div>
    </div>
    <div v-else class="text-center py-8 text-zinc-600 text-sm">Nav progresa datu</div>
</template>
