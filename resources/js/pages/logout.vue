<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
const auth = useAuthStore();
onMounted(() => auth.logout());
</script>
<template>
    <div class="min-h-[60vh] flex items-center justify-center">
        <div class="w-12 h-12 border-4 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
    </div>
</template>
