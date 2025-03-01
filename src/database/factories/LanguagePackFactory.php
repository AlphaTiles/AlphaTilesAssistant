<?php

namespace Database\Factories;

use App\Models\LanguagePack;
use App\Models\User;
use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class LanguagePackFactory extends Factory
{
    protected $model = LanguagePack::class;

    public function definition()
    {
        return [
            'userid' => User::factory(),
            'name' => $this->faker->words(2, true),
            'import_status' => ImportStatus::SUCCESS,
        ];
    }
}