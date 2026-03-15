<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\LanguagePack;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        $order = $this->faker->unique()->numberBetween(1, 1000);

        return [
            'languagepackid' => LanguagePack::factory(),
            'include'        => true,
            'door'           => $order,
            'order'          => $order,
            'country'        => $this->faker->lexify('??'),
            'level'          => $this->faker->numberBetween(1, 10),
            'color'          => $this->faker->numberBetween(1, 10),
            'file_id'        => null,
            'audio_duration' => null,
            'syll_or_tile'   => $this->faker->randomElement(['s', 't']),
            'stages_included' => null,
            'friendly_name'  => $this->faker->words(2, true),
            'basic'          => false,
            'required_assets' => null,
            'abs'            => false,
        ];
    }
}
