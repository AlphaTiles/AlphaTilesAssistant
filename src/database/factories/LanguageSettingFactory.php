<?php

namespace Database\Factories;

use App\Models\LanguageSetting;
use App\Models\LanguagePack;
use Illuminate\Database\Eloquent\Factories\Factory;

class LanguageSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LanguageSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'languagepackid' => LanguagePack::factory(),
            'name' => $this->faker->word(),
            'value' => $this->faker->sentence(),
        ];
    }

    /**
     * Create a setting with a specific name and value
     */
    public function withNameAndValue(string $name, string $value)
    {
        return $this->state(function (array $attributes) use ($name, $value) {
            return [
                'name' => $name,
                'value' => $value,
            ];
        });
    }
}