<?php

namespace Database\Factories;

use App\Models\Word;
use App\Models\LanguagePack;
use Illuminate\Database\Eloquent\Factories\Factory;

class WordFactory extends Factory
{
    protected $model = Word::class;

    public function definition()
    {
        return [
            'languagepackid' => LanguagePack::factory(),
            'value' => $this->faker->word(),
            'mixed_types' => '',
            'stage' => $this->faker->numberBetween(1, 5),
            'audiofile_id' => null,
            'imagefile_id' => null
        ];
    }
}