<?php

namespace Database\Factories;

use App\Models\Syllable;
use App\Models\LanguagePack;
use Illuminate\Database\Eloquent\Factories\Factory;

class SyllableFactory extends Factory
{
    protected $model = Syllable::class;

    public function definition()
    {
        return [
            'languagepackid' => LanguagePack::factory(),
            'value' => $this->faker->unique()->lexify('??'),
            'file_id' => null,
            'or_1' => null,
            'or_2' => null,
            'or_3' => null,
            'color' => null,
        ];
    }
}