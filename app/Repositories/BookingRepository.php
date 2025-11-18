<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class BookingRepository
{
    /**
     * Находит расписание на определенный день недели.
     * @param  int  $serviceId
     * @param  int  $dayOfWeek
     * @return Schedule|null
     */
    public function findScheduleForDay(int $serviceId, int $dayOfWeek): ?Schedule
    {
        return Schedule::where('day_of_week', $dayOfWeek)->where('service_id', $serviceId)->first();
    }

    /**
     * Находит все бронирования для конкретной услуги на заданную дату.
     * @param  Service  $service
     * @param  Carbon  $date
     * @return Collection
     */
    public function findBookingsForServiceOnDate(Service $service, Carbon $date): Collection
    {
        return Booking::where('service_id', $service->id)
            ->whereDate('start_time', $date->toDateString())
            ->get();
    }

    /**
     * Создает новое бронирование в рамках транзакции с блокировкой для предотвращения "гонки состояний".
     * @throws Exception
     */
    public function create(array $data): Booking
    {
        try {
            return DB::transaction(function () use ($data) {
                // Блокируем родительскую запись услуги, чтобы сериализовать попытки бронирования для этой услуги
                // Могут быть разные варианты решения этой проблемы, но с текущей реализацией приложения это будет оптимально
                $service = Service::where('id', $data['service_id'])->lockForUpdate()->first();

                if (!$service) {
                    throw new Exception('Услуга не найдена.');
                }

                $conflictingBooking = Booking::where('service_id', $data['service_id'])
                    ->where(function ($query) use ($data) {
                        $query->where('start_time', '<', $data['end_time'])
                            ->where('end_time', '>', $data['start_time']);
                    })
                    ->exists();

                if ($conflictingBooking) {
                    throw new Exception('Этот временной слот только что был забронирован. Пожалуйста, выберите другой.');
                }

                return Booking::create($data);
            });
        } catch (Throwable $e) {
            // Любые другие общие ошибки
            report($e);
            throw new Exception('Произошла непредвиденная ошибка при создании записи. Пожалуйста, попробуйте еще раз.');
        }
    }
}
