<?php

namespace Tests\Feature;

use App\Enums\ErrorTypeEnum;
use App\Enums\LangInfoEnum;
use App\Enums\TabEnum;
use App\Models\LanguagePack;
use App\Models\LanguageSetting;
use App\Models\User;
use App\Models\Word;
use App\Services\ValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WordlistControllerTest extends TestCase
{
    use RefreshDatabase;

    private LanguagePack $languagePack;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languagePack = LanguagePack::factory()->create();
        $this->user = $this->languagePack->owner;

        LanguageSetting::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'name' => LangInfoEnum::LANG_NAME_ENGLISH->value,
            'value' => 'English',
        ]);
    }

    public function test_update_shows_validation_errors_after_saving_words(): void
    {
        $word = Word::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'value' => 'a b q',
            'audiofile_id' => null,
            'imagefile_id' => null,
        ]);

        $validationErrors = [
            ErrorTypeEnum::PARSE_WORD_INTO_TILES->value => [[
                'value' => 'a b q',
                'type' => ErrorTypeEnum::PARSE_WORD_INTO_TILES,
                'tab' => ErrorTypeEnum::PARSE_WORD_INTO_TILES->tab()->name(),
            ]],
        ];

        $mockValidationService = Mockery::mock(ValidationService::class);
        $mockValidationService->shouldReceive('handle')
            ->with(TabEnum::WORD)
            ->andReturn($validationErrors);

        $this->app->instance(ValidationService::class, $mockValidationService);

        $response = $this->actingAs($this->user)
            ->patch("/languagepack/wordlist/{$this->languagePack->id}", [
                'words' => [[
                    'id' => $word->id,
                    'languagepackid' => $this->languagePack->id,
                    'value' => 'TestWord',
                    'mixed_types' => '',
                    'delete' => '0',
                    'stage' => null,
                ]],
                'orderBy' => 'value',
            ]);

        $response->assertOk();
        $response->assertViewHas('saveMessage', 'Your changes were saved, but there are still word validation issues to fix.');
        $response->assertViewHas('validationErrors');
    }
}
