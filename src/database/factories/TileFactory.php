<?php

namespace Database\Factories;

use App\Enums\TileTypeEnum;
use App\Models\Tile;
use App\Models\File;
use App\Models\LanguagePack;
use Illuminate\Database\Eloquent\Factories\Factory;

class TileFactory extends Factory
{
    protected $model = Tile::class;

    public function definition()
    {
        return [
            'languagepackid' => LanguagePack::factory(),
            'value' => $this->faker->randomLetter(),
            'upper' => function (array $attributes) {
                return strtoupper($attributes['value']);
            },
            'type' => TileTypeEnum::CONSONANT->value,
            'file_id' => null,
            'stage' => $this->faker->numberBetween(1, 5),
            'or_1' => null,
            'or_2' => null,
            'or_3' => null,
            'type2' => null,
            'file2_id' => null,
            'stage2' => null,
            'type3' => null,
            'file3_id' => null,
            'stage3' => null,
        ];
    }
}