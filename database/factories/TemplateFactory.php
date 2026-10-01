<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Template;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Template>
 */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name'            => $this->faker->word(),
            'slug'            => $this->faker->unique()->slug(),
            'price'           => 499.00,
            'stripe_price_id' => 'price_fake_' . $this->faker->md5(),
            'is_active'       => true,
        ];
    }
}
