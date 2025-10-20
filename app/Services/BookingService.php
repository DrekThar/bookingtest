<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Repositories\BookingRepository;
use App\Repositories\ServiceRepository;
use Carbon\Carbon;
use Exception;

/**
 * Статические переменные лучше держать в модели для площадки, но она не реализована, поэтому они прописаны здесь
 */
class BookingService
{
    protected BookingRepository $bookingRepository;

    protected ServiceRepository $serviceRepository;

    /**
     * @var int Дополнительное время на обслуживание в минутах
     */
    public static int $serviceTime = 30;

    /**
     * @var int интервал между возможными слотами в минутах
     */
    public static int $slotInterval = 15;

    public function __construct(BookingRepository $bookingRepository, ServiceRepository $serviceRepository)
    {
        $this->bookingRepository = $bookingRepository;
        $this->serviceRepository = $serviceRepository;
    }

    /**
     * Рассчитывает доступные слоты для бронирования
     * @param  Service  $service
     * @param  Carbon  $date
     * @return array
     */
    public function getAvailableSlots(Service $service, Carbon $date): array
    {
        $schedule = $this->bookingRepository->findScheduleForDay($service->id, $date->dayOfWeek);

        if (!$schedule) {
            return []; // В этот день услуга не предоставляется
        }

        // Генерируем все возможные слоты в течение рабочего дня
        $possibleSlots = [];
        // Общая продолжительность блокировки слота = длительность услуги + время на обслуживание.
        $slotBlockDuration = $service->duration_minutes + self::$serviceTime;
        $dayStart = Carbon::parse($date->toDateString().' '.$schedule->start_time);
        $dayEnd = Carbon::parse($date->toDateString().' '.$schedule->end_time);

        $currentTime = $dayStart->copy();
        while ($currentTime->copy()->addMinutes($slotBlockDuration) <= $dayEnd) {
            $possibleSlots[] = $currentTime->copy();
            $currentTime->addMinutes(self::$slotInterval);
        }

        // Получаем все существующие бронирования на этот день
        $bookings = $this->bookingRepository->findBookingsForServiceOnDate($service, $date);

        // Фильтруем массив возможных слотов, оставляя только те, что не пересекаются с существующими бронированиями
        $availableSlots = array_filter($possibleSlots, function (Carbon $possibleSlot) use ($bookings, $service) {
            // Конец полной блокировки нового слота = его начало + длительность услуги + время на обслуживание
            $possibleSlotBlockEnd = $possibleSlot->copy()->addMinutes($service->duration_minutes + self::$serviceTime);

            foreach ($bookings as $booking) {
                $bookingStart = Carbon::parse($booking->start_time);
                $bookingUnavailableUntil = Carbon::parse($booking->end_time)->addMinutes(self::$serviceTime);

                if ($possibleSlot->lt($bookingUnavailableUntil) && $possibleSlotBlockEnd->gt($bookingStart)) {
                    return false; // Этот слот недоступен, отфильтровываем его
                }
            }

            return true; // Слот не пересекается ни с одним бронированием, оставляем его
        });

        // Форматируем результат в нужный вид (H:i) и переиндексируем массив
        return array_values(
            array_map(fn (Carbon $slot) => $slot->format('H:i'), $availableSlots)
        );
    }

    /**
     * Подготавливает данные и создает новое бронирование.
     * @param  array  $data
     * @return Booking
     * @throws Exception
     */
    public function createBooking(array $data): Booking
    {
        $service = $this->serviceRepository->findOrFail($data['service_id']);
        $startTime = Carbon::parse($data['date'].' '.$data['time']);
        $endTime = $startTime->copy()
            ->addMinutes($service->duration_minutes); // Время на обслуживание не добавляется т.к. в теории может изменяться

        $bookingData = [
            'service_id' => $service->id,
            'customer_name' => $data['name'],
            'customer_phone' => $data['phone'],
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];

        return $this->bookingRepository->create($bookingData);
    }
}
