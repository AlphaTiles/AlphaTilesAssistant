<?php

namespace Database\Factories;

use App\Models\Key;
use App\Models\LanguagePack;
use Illuminate\Database\Eloquent\Factories\Factory;

class KeyFactory extends Factory
{
    protected $model = Key::class;

    public function definition()
    {
        return [
            'languagepackid' => LanguagePack::factory(),
            'value' => $this->faker->unique()->lexify('?'),
            'color' => $this->faker->numberBetween(1, 6),
        ];
    }
}