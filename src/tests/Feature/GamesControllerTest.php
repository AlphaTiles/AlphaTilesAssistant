<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Game;
use App\Models\User;
use App\Models\LanguagePack;
use App\Enums\RequiredAssetsEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GamesControllerTest extends TestCase
{
    use RefreshDatabase;

    private LanguagePack $languagePack;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languagePack = LanguagePack::factory()->create();
        $this->user         = $this->languagePack->owner;
    }

    private function url(array $query = []): string
    {
        $base = "/languagepack/games/{$this->languagePack->id}";
        return $query ? $base . '?' . http_build_query($query) : $base;
    }

    private function ids($items): \Illuminate\Support\Collection
    {
        return $items->getCollection()->pluck('id');
    }

    /** @test */
    public function default_filter_shows_only_basic_games(): void
    {
        $basic    = Game::factory()->create(['languagepackid' => $this->languagePack->id, 'basic' => true,  'order' => 1]);
        $nonBasic = Game::factory()->create(['languagepackid' => $this->languagePack->id, 'basic' => false, 'order' => 2]);

        $response = $this->actingAs($this->user)->get($this->url());

        $response->assertOk();
        $response->assertViewHas('items', function ($items) use ($basic, $nonBasic) {
            $ids = $this->ids($items);
            return $ids->contains($basic->id) && !$ids->contains($nonBasic->id);
        });
    }

    /** @test */
    public function show_excluded_shows_all_games(): void
    {
        $basic    = Game::factory()->create(['languagepackid' => $this->languagePack->id, 'basic' => true,  'order' => 1]);
        $nonBasic = Game::factory()->create(['languagepackid' => $this->languagePack->id, 'basic' => false, 'order' => 2]);

        $response = $this->actingAs($this->user)->get($this->url(['show_excluded' => 1]));

        $response->assertOk();
        $response->assertViewHas('items', function ($items) use ($basic, $nonBasic) {
            $ids = $this->ids($items);
            return $ids->contains($basic->id) && $ids->contains($nonBasic->id);
        });
    }

    /** @test */
    public function required_assets_filter_ta_shows_only_ta_games(): void
    {
        $taGame = Game::factory()->create([
            'languagepackid'  => $this->languagePack->id,
            'basic'           => true,
            'required_assets' => RequiredAssetsEnum::TA->value,
            'order'           => 1,
        ]);
        $otherGame = Game::factory()->create([
            'languagepackid'  => $this->languagePack->id,
            'basic'           => true,
            'required_assets' => RequiredAssetsEnum::SB_T->value,
            'order'           => 2,
        ]);

        $response = $this->actingAs($this->user)->get($this->url([
            'show_excluded'          => 1,
            'required_assets_filter' => RequiredAssetsEnum::TA->value,
        ]));

        $response->assertOk();
        $response->assertViewHas('items', function ($items) use ($taGame, $otherGame) {
            $ids = $this->ids($items);
            return $ids->contains($taGame->id) && !$ids->contains($otherGame->id);
        });
    }

    /** @test */
    public function required_assets_filter_sbt_shows_only_sbt_games(): void
    {
        $sbtGame = Game::factory()->create([
            'languagepackid'  => $this->languagePack->id,
            'basic'           => true,
            'required_assets' => RequiredAssetsEnum::SB_T->value,
            'order'           => 1,
        ]);
        $sbtSaGame = Game::factory()->create([
            'languagepackid'  => $this->languagePack->id,
            'basic'           => true,
            'required_assets' => RequiredAssetsEnum::SB_T_SA->value,
            'order'           => 2,
        ]);

        $response = $this->actingAs($this->user)->get($this->url([
            'show_excluded'          => 1,
            'required_assets_filter' => RequiredAssetsEnum::SB_T->value,
        ]));

        $response->assertOk();
        $response->assertViewHas('items', function ($items) use ($sbtGame, $sbtSaGame) {
            $ids = $this->ids($items);
            return $ids->contains($sbtGame->id) && !$ids->contains($sbtSaGame->id);
        });
    }

    /** @test */
    public function required_assets_filter_sbt_sa_shows_only_sbt_sa_games(): void
    {
        $sbtSaGame = Game::factory()->create([
            'languagepackid'  => $this->languagePack->id,
            'basic'           => true,
            'required_assets' => RequiredAssetsEnum::SB_T_SA->value,
            'order'           => 1,
        ]);
        $taGame = Game::factory()->create([
            'languagepackid'  => $this->languagePack->id,
            'basic'           => true,
            'required_assets' => RequiredAssetsEnum::TA->value,
            'order'           => 2,
        ]);

        $response = $this->actingAs($this->user)->get($this->url([
            'show_excluded'          => 1,
            'required_assets_filter' => RequiredAssetsEnum::SB_T_SA->value,
        ]));

        $response->assertOk();
        $response->assertViewHas('items', function ($items) use ($sbtSaGame, $taGame) {
            $ids = $this->ids($items);
            return $ids->contains($sbtSaGame->id) && !$ids->contains($taGame->id);
        });
    }

    /** @test */
    public function abs_filter_shows_only_abs_games(): void
    {
        $absGame = Game::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'basic'          => false,
            'abs'            => true,
            'order'          => 1,
        ]);
        $normalGame = Game::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'basic'          => true,
            'abs'            => false,
            'order'          => 2,
        ]);

        $response = $this->actingAs($this->user)->get($this->url([
            'show_excluded'          => 1,
            'required_assets_filter' => 'abs',
        ]));

        $response->assertOk();
        $response->assertViewHas('items', function ($items) use ($absGame, $normalGame) {
            $ids = $this->ids($items);
            return $ids->contains($absGame->id) && !$ids->contains($normalGame->id);
        });
    }

    /** @test */
    public function required_assets_filter_is_ignored_without_show_excluded(): void
    {
        $taGame = Game::factory()->create([
            'languagepackid'  => $this->languagePack->id,
            'basic'           => true,
            'required_assets' => RequiredAssetsEnum::TA->value,
            'order'           => 1,
        ]);
        $basicGame = Game::factory()->create([
            'languagepackid'  => $this->languagePack->id,
            'basic'           => true,
            'required_assets' => null,
            'order'           => 2,
        ]);

        // Passing required_assets_filter without show_excluded should be forced to 'all'
        $response = $this->actingAs($this->user)->get($this->url([
            'required_assets_filter' => RequiredAssetsEnum::TA->value,
        ]));

        $response->assertOk();
        $response->assertViewHas('items', function ($items) use ($taGame, $basicGame) {
            $ids = $this->ids($items);
            return $ids->contains($taGame->id) && $ids->contains($basicGame->id);
        });
    }

    /** @test */
    public function games_from_other_language_packs_are_not_shown(): void
    {
        $ownGame  = Game::factory()->create(['languagepackid' => $this->languagePack->id, 'basic' => true, 'order' => 1]);
        $otherLp  = LanguagePack::factory()->create();
        $otherGame = Game::factory()->create(['languagepackid' => $otherLp->id, 'basic' => true, 'order' => 1]);

        $response = $this->actingAs($this->user)->get($this->url(['show_excluded' => 1]));

        $response->assertOk();
        $response->assertViewHas('items', function ($items) use ($ownGame, $otherGame) {
            $ids = $this->ids($items);
            return $ids->contains($ownGame->id) && !$ids->contains($otherGame->id);
        });
    }
}
