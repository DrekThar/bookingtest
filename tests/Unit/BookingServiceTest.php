<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Services\BookingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use DatabaseTransactions;

    private BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = $this->app->make(BookingService::class);
        BookingService::$slotInterval = 30;
    }

    /**
     * Тест успешного создания бронирования.
     * @throws Exception
     */
    public function test_create_booking(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $validatedData = [
            'service_id' => $service->id,
            'name' => 'Тестовый Клиент',
            'phone' => '89991234567',
            'date' => '2025-01-10',
            'time' => '10:00',
        ];

        $result = $this->bookingService->createBooking($validatedData);

        $this->assertInstanceOf(Booking::class, $result);
        $this->assertDatabaseHas('bookings', [
            'service_id' => $service->id,
            'customer_name' => 'Тестовый Клиент',
            'customer_phone' => '89991234567',
            'start_time' => '2025-01-10 10:00:00',
            'end_time' => Carbon::parse('2025-01-10 10:00')->addMinutes($service->duration_minutes)->toDateTimeString(),
        ]);
    }

    /**
     * Тест предотвращения двойного бронирования на один и тот же слот.
     * @throws Exception
     */
    public function test_prevents_double_booking_for_the_same_slot(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Этот временной слот только что был забронирован. Пожалуйста, выберите другой.');

        $service = Service::factory()->create(['duration_minutes' => 60]);
        $validatedData = [
            'service_id' => $service->id,
            'name' => 'Тестовый Клиент',
            'phone' => '89991234567',
            'date' => '2025-01-15',
            'time' => '14:00',
        ];

        $this->bookingService->createBooking($validatedData); // Первый вызов
        $this->bookingService->createBooking($validatedData); // Второй вызов
    }

    /**
     * Тестирует получение доступных слотов в различных сценариях.
     * @dataProvider slotDataProvider
     */
    public function test_get_available_slots(
        array $schedules,
        array $bookings,
        int $duration,
        string $date,
        array $expected
    ): void {
        $service = Service::factory()->create(['duration_minutes' => $duration]);
        $date = Carbon::parse($date);

        foreach ($schedules as $schedule) {
            Schedule::factory()->create(array_merge($schedule, ['service_id' => $service->id]));
        }

        foreach ($bookings as $booking) {
            Booking::factory()->create(array_merge($booking, ['service_id' => $service->id]));
        }

        $availableSlots = $this->bookingService->getAvailableSlots($service, $date);

        $this->assertEquals($expected, $availableSlots);
    }

    public static function slotDataProvider(): array
    {
        $testDate = '2025-10-20';

        return [
            'обычный день без броней' => [
                'schedules' => [['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '12:00']],
                'bookings' => [],
                'duration' => 30,
                'date' => $testDate,
                'expected' => ['09:00', '09:30', '10:00', '10:30', '11:00'],
            ],

            'день с одной бронью в середине' => [
                'schedules' => [['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '12:00']],
                'bookings' => [['start_time' => $testDate.' 10:00:00', 'end_time' => $testDate.' 10:30:00']],
                'duration' => 30,
                'date' => $testDate,
                'expected' => ['09:00', '11:00'],
            ],

            'нерабочий день' => [
                'schedules' => [['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00']], // расписание на вторник
                'bookings' => [],
                'duration' => 30,
                'date' => $testDate, // проверяем понедельник
                'expected' => [],
            ],

            'все слоты заняты' => [
                'schedules' => [['day_of_week' => 1, 'start_time' => '10:00', 'end_time' => '12:00']],
                'bookings' => [
                    ['start_time' => $testDate.' 10:00:00', 'end_time' => $testDate.' 11:00:00'],
                    ['start_time' => $testDate.' 11:00:00', 'end_time' => $testDate.' 12:00:00'],
                ],
                'duration' => 30,
                'date' => $testDate,
                'expected' => [],
            ],

            'длительность услуги 60 минут' => [
                'schedules' => [['day_of_week' => 1, 'start_time' => '10:00', 'end_time' => '13:00']],
                'bookings' => [],
                'duration' => 60,
                'date' => $testDate,
                'expected' => ['10:00', '10:30', '11:00', '11:30'],
            ],
        ];
    }
}
