<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Schedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'day_of_week' => $this->faker->unique()->numberBetween(1, 6),
            'start_time' => '10:00:00',
            'end_time' => '20:00:00',
        ];
    }
}
