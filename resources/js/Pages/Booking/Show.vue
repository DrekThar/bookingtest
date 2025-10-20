<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { format, addDays, startOfWeek, endOfWeek, eachDayOfInterval, getDay } from 'date-fns';
import { ru } from 'date-fns/locale';
import axios from 'axios';
import type { Service } from '@/types/inertia';

interface BookingForm {
    name: string;
    phone: string;
    service_id: number;
    date: string | null;
    time: string | null;
}

const props = defineProps<{
    service: Service;
    availableDays: number[];
    errors: Partial<string, string>;
}>();

const today = new Date();
const currentWeekStart = ref<Date>(startOfWeek(today, { weekStartsOn: 1 }));
const selectedDate = ref<string | null>(null);
const availableSlots = ref<string[]>([]);
const selectedSlot = ref<string | null>(null);
const showModal = ref<boolean>(false);
const showSuccessModal = ref<boolean>(false);

const form = useForm<BookingForm>({
    name: '',
    phone: '',
    service_id: props.service.id,
    date: null,
    time: null,
});

const weekDays = computed(() => {
    return eachDayOfInterval({
        start: currentWeekStart.value,
        end: endOfWeek(currentWeekStart.value, { weekStartsOn: 1 }),
    });
});

const isDayDisabled = (day: Date): boolean => {
    const dayOfWeek = getDay(day);
    const isoDayOfWeek = dayOfWeek === 0 ? 7 : dayOfWeek;
    return !props.availableDays.includes(isoDayOfWeek);
};

const changeWeek = (amount: number): void => {
    currentWeekStart.value = addDays(currentWeekStart.value, amount * 7);
    selectedDate.value = null;
    availableSlots.value = [];
};

const selectDate = async (date: Date): Promise<void> => {
    if (isDayDisabled(date)) {
        return;
    }
    selectedDate.value = format(date, 'yyyy-MM-dd');
    selectedSlot.value = null;
    try {
        const response = await axios.get<string[]>(`/services/${props.service.id}/slots?date=${selectedDate.value}`);
        availableSlots.value = response.data;
    } catch (error) {
        console.error("Ошибка при загрузке слотов:", error);
    }
};

const selectSlot = (slot: string): void => {
    selectedSlot.value = slot;
    showModal.value = true;
};

const submitBooking = (): void => {
    form.date = selectedDate.value;
    form.time = selectedSlot.value;
    form.post('/bookings', {
        onSuccess: () => {
            showModal.value = false;
            showSuccessModal.value = true;
            form.reset();
        },
    });
};

const closeSuccessModal = (): void => {
    showSuccessModal.value = false;
    window.location.href = '/';
};

</script>

<template>
    <Head :title="`Запись на ${service.name}`" />

    <div class="container mx-auto p-8">
        <Link href="/" class="text-blue-500 hover:underline mb-4 block">&lt; Назад к списку услуг</Link>
        <h1 class="text-3xl font-bold mb-4">Запись: {{ service.name }}</h1>
        <p class="text-lg text-gray-600 mb-6">Длительность: {{ service.duration_minutes }} минут</p>

        <div class="flex justify-between items-center mb-4">
            <button @click="changeWeek(-1)" class="px-4 py-2 bg-gray-300 rounded">&lt; Прошлая неделя</button>
            <span class="capitalize">{{ format(currentWeekStart, 'LLLL yyyy', { locale: ru }) }}</span>
            <button @click="changeWeek(1)" class="px-4 py-2 bg-gray-300 rounded">Следующая неделя &gt;</button>
        </div>

        <div class="grid grid-cols-7 gap-2 text-center mb-6">
            <div v-for="day in weekDays" :key="day.toISOString()"
                 @click="selectDate(day)"
                 :class="['p-4 border rounded cursor-pointer',
                          {'bg-blue-500 text-white': selectedDate === format(day, 'yyyy-MM-dd')},
                          {'bg-gray-200 text-gray-400 cursor-not-allowed': isDayDisabled(day)},
                          {'hover:bg-blue-100': !isDayDisabled(day)}]"
                >
                <div class="font-bold capitalize">{{ format(day, 'EEE', { locale: ru }) }}</div>
                <div>{{ format(day, 'd') }}</div>
            </div>
        </div>

        <div v-if="selectedDate">
            <h2 class="text-xl font-semibold mb-4">Доступные слоты на {{ selectedDate }}</h2>
            <div v-if="availableSlots.length > 0" class="grid grid-cols-4 gap-4">
                <button v-for="slot in availableSlots" :key="slot"
                        @click="selectSlot(slot)"
                        class="bg-green-500 text-white font-bold py-2 px-4 rounded hover:bg-green-700">
                    {{ slot }}
                </button>
            </div>
            <div v-else class="text-gray-500">
                На этот день нет доступных слотов. Пожалуйста, выберите другой день.
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
                <h2 class="text-2xl font-bold mb-4">Подтвердите запись</h2>
                <p><strong>Услуга:</strong> {{ service.name }} ({{service.duration_minutes}} минут)</p>
                <p><strong>Дата:</strong> {{ selectedDate }}</p>
                <p><strong>Время:</strong> {{ selectedSlot }}</p>
                <form @submit.prevent="submitBooking" class="mt-4">
                    <div class="mb-4">
                        <label for="name" class="block text-gray-700">Ваше имя:</label>
                        <input type="text" id="name" v-model="form.name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                    </div>
                    <div class="mb-4">
                        <label for="phone" class="block text-gray-700">Номер телефона:</label>
                        <input type="tel" id="phone" v-model="form.phone" pattern="(\+7|8)[0-9]{10}" title="Номер телефона должен начинаться с +7 или 8 и содержать 11 цифр" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        <div v-if="form.errors.phone" class="text-red-500 text-sm mt-1">{{ form.errors.phone }}</div>
                    </div>
                    <div v-if="form.errors.general" class="text-red-500 text-sm mb-4">{{ form.errors.general }}</div>
                    <div class="flex justify-end space-x-4">
                        <button type="button" @click="showModal = false" class="px-4 py-2 bg-gray-300 rounded">Отмена</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-500 text-white rounded disabled:opacity-50">Подтвердить запись</button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-8 rounded-lg shadow-lg text-center">
                <h2 class="text-2xl font-bold mb-4">Успешно!</h2>
                <p class="mb-4">Ваша запись была успешно создана.</p>
                <button @click="closeSuccessModal" class="px-4 py-2 bg-green-500 text-white rounded">OK</button>
            </div>
        </div>
    </div>
</template>
