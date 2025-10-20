<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { Service } from '@/types/inertia';

defineProps<{
  service: Service;
  availableSlots: string[];
  date: string;
}>();
</script>

<template>
    <Head :title="`Слоты для ${service.name}`" />

    <div class="min-h-screen bg-gray-100 flex items-center justify-center">
        <div class="w-full max-w-2xl p-8 bg-white rounded-lg shadow-md">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-2">{{ service.name }}</h1>
            <p class="text-center text-gray-600 mb-8">Доступные слоты на {{ date }}</p>

            <div v-if="availableSlots.length > 0" class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <div v-for="slot in availableSlots" :key="slot" class="text-center p-2 border rounded-lg bg-blue-500 text-white font-semibold shadow hover:bg-blue-700 transition-colors cursor-pointer">
                    {{ slot }}
                </div>
            </div>
            <div v-else>
                <p class="text-center text-gray-500">Нет доступных слотов на этот день.</p>
            </div>

            <div class="text-center mt-8">
                <a href="/" class="text-blue-500 hover:underline">&larr; Назад к услугам</a>
            </div>
        </div>
    </div>
</template>
