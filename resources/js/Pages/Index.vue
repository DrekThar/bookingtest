<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { Service } from '@/types/inertia';

defineProps<{
    services: Service[]
}>();
</script>

<template>
    <Head title="Записаться на прием" />

    <div class="min-h-screen bg-gray-100 flex items-center justify-center">
        <div class="w-full max-w-2xl p-8 bg-white rounded-lg shadow-md">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Записаться на услугу</h1>
            <p class="text-center text-gray-600 mb-8">Пожалуйста, выберите услугу, чтобы увидеть доступное время.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="service in services" :key="service.id"
                     class="border p-4 rounded-lg shadow hover:shadow-lg transition-shadow">
                    <h2 class="text-xl font-semibold">{{ service.name }}</h2>
                    <p class="text-gray-500 mt-2">{{ service.duration_minutes }} минут</p>
                    <Link :href="`/services/${service.id}/show`"
                          class="mt-4 inline-block bg-blue-500 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition-colors">
                        Записаться
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
