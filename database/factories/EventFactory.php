<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Order;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(3);

        return [
            // 🔗 Relaciones automáticas si no se inyectan en el test
            'user_id'     => User::factory(),
            'template_id' => Template::factory(),
            'order_id'    => Order::factory(),
            
            // 📝 Datos del evento
            'title'       => $title,
            'slug'        => Str::slug($title),
            'event_date'  => $this->faker->dateTimeBetween('+2 months', '+1 year')->format('Y-m-d H:i:s'),
            'is_active'   => true,
            
            // 🧠 Configuración estructurada para tu campo JSON/objeto features
            'features'    => [
                'max_guests'       => $this->faker->numberBetween(50, 300),
                'allow_children'   => $this->faker->boolean(),
                'theme_color'      => $this->faker->hexColor(),
                'show_dress_code'  => true,
            ],
        ];
    }

    /**
     * Estado para forzar que el evento esté inactivo.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
