<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = Carbon::instance($this->faker->dateTimeBetween('now', '+1 month'));
        $startTime->hour($this->faker->numberBetween(9, 16))
            ->minute($this->faker->randomElement([0, 15, 30, 45]))
            ->second(0);

        $endTime = $startTime->copy()->addMinutes(30);

        return [
            'service_id' => Service::factory(),
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }
}
