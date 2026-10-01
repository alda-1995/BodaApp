<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guest>
 */
class GuestFactory extends Factory
{
    protected $model = Guest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),     
            'name'    => $this->faker->name(),
            'email'   => $this->faker->unique()->safeEmail(), 
            'phone'   => $this->faker->numerify('+52##########'),
        ];
    }
}
