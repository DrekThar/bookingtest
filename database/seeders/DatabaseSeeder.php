<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Service;
use App\Services\BookingService;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Заполняет базу данных тестовыми данными.
     * Очищает используемые таблицы каждый раз при запуске
     * @return void
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('bookings')->truncate();
        DB::table('services')->truncate();
        DB::table('schedules')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $services = collect();
        $services->add(Service::factory(['name' => 'Поездка на квадроцикле', 'duration_minutes' => 30])
            ->has(
                Booking::factory()
                    ->count(6)
                    ->state(new Sequence(
                        ['start_time' => '2025-10-16 13:00:00'],
                        ['start_time' => '2025-10-16 16:00:00'],
                        ['start_time' => '2025-10-17 10:00:00'],
                        ['start_time' => '2025-10-17 11:00:00'],
                        ['start_time' => '2025-10-17 13:00:00'],
                        ['start_time' => '2025-10-17 18:00:00'],
                    ))
                    ->state($this->setEndTime())
            )
            ->create());

        $services->add(Service::factory(['name' => 'Поездка на квадроцикле', 'duration_minutes' => 60])
            ->has(
                Booking::factory()
                    ->count(1)
                    ->state(new Sequence(
                        ['start_time' => '2025-10-16 10:00:00'],
                    ))
                    ->state($this->setEndTime())
            )
            ->create());

        $services->add(Service::factory(['name' => 'Тур на эндуро', 'duration_minutes' => 60])
            ->has(
                Booking::factory()
                    ->count(3)
                    ->state(new Sequence(
                        ['start_time' => '2025-10-16 10:00:00'],
                        ['start_time' => '2025-10-16 11:30:00'],
                        ['start_time' => '2025-10-16 18:30:00'],
                    ))
                    ->state($this->setEndTime())
            )
            ->create());

        $services->add(Service::factory(['name' => 'Тур на эндуро', 'duration_minutes' => 120])
            ->has(
                Booking::factory()
                    ->count(1)
                    ->state(new Sequence(
                        ['start_time' => '2025-10-17 14:00:00'],
                    ))
                    ->state($this->setEndTime())
            )
            ->create());

        $services->each(function (Service $service) {
            for ($day = 1; $day <= 6; $day++) {
                $service->schedules()->create([
                    'day_of_week' => $day,
                    'start_time' => '10:00:00',
                    'end_time' => '20:00:00',
                ]);
            }
        });
    }

    /**
     * Выставляет end_time на основе start_time + duration_minutes сервиса
     * @return Closure
     */
    public function setEndTime(): Closure
    {
        return function (array $attributes, Service $service) {
            return ['end_time' => Carbon::parse($attributes['start_time'])->addMinutes($service->duration_minutes)];
        };
    }
}
