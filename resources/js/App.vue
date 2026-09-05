<script setup>
import { ref } from 'vue';

const count = ref(0);
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center gap-6 bg-gray-50 text-gray-900">
        <h1 class="text-3xl font-semibold">T-Task</h1>
        <p class="text-gray-600">Laravel + Vue 3 + Tailwind CSS</p>

        <button
            class="rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white transition hover:bg-indigo-700"
            @click="count++"
        >
            Count is {{ count }}
        </button>
    </div>
</template>
