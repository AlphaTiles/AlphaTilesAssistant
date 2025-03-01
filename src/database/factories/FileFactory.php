<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word() . '.' . $this->faker->fileExtension(),
            'file_path' => 'storage/' . $this->faker->word() . '/' . $this->faker->md5() . '.mp3',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Configure the factory for audio files
     */
    public function audio()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->word() . '.mp3',
                'file_path' => 'storage/audio/' . $this->faker->md5() . '.mp3',
            ];
        });
    }

    /**
     * Configure the factory for image files
     */
    public function image()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->word() . '.jpg',
                'file_path' => 'storage/images/' . $this->faker->md5() . '.jpg',
            ];
        });
    }
}