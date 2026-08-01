<?php

namespace Tests\Feature;

use App\Enums\GameSettingEnum;
use App\Models\GameSetting;
use App\Models\LanguagePack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private LanguagePack $languagePack;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languagePack = LanguagePack::factory()->create();
        $this->user = $this->languagePack->owner;

        $userRoleModel = config('roles.models.role');
        $userRole = $userRoleModel::firstOrCreate(
            ['slug' => 'user'],
            ['name' => 'User', 'description' => 'User Role', 'level' => 1]
        );
        $this->user->attachRole($userRole);
    }

    /** @test */
    public function non_admin_cannot_override_app_id(): void
    {
        GameSetting::create([
            'languagepackid' => $this->languagePack->id,
            'name' => GameSettingEnum::APP_ID->value,
            'value' => 'abcoriginal',
        ]);

        $response = $this->actingAs($this->user)
            ->post("/languagepack/game_settings/{$this->languagePack->id}", $this->buildPayload('zzzmanualoverride'));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('settings.app_id');

        $this->assertDatabaseHas('game_settings', [
            'languagepackid' => $this->languagePack->id,
            'name' => GameSettingEnum::APP_ID->value,
            'value' => 'abcoriginal',
        ]);
    }

    /** @test */
    public function admin_can_override_app_id(): void
    {
        GameSetting::create([
            'languagepackid' => $this->languagePack->id,
            'name' => GameSettingEnum::APP_ID->value,
            'value' => 'abcoriginal',
        ]);

        $roleModel = config('roles.models.role');
        $adminRole = $roleModel::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Admin Role', 'level' => 5]
        );
        $this->user->attachRole($adminRole);

        $response = $this->actingAs($this->user)
            ->post("/languagepack/game_settings/{$this->languagePack->id}", $this->buildPayload('manual-admin-id-override'));

        $response->assertStatus(302);
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('game_settings', [
            'languagepackid' => $this->languagePack->id,
            'name' => GameSettingEnum::APP_ID->value,
            'value' => 'manual-admin-id-override',
        ]);
    }

    private function buildPayload(string $appId): array
    {
        $settings = [];

        foreach (GameSettingEnum::cases() as $setting) {
            if ($setting === GameSettingEnum::GOOGLE_SERVICES_JSON) {
                continue;
            }

            if ($setting === GameSettingEnum::APP_ID) {
                $settings[$setting->value] = $appId;
                continue;
            }

            $defaultValue = $setting->defaultValue();
            if (is_bool($defaultValue)) {
                $defaultValue = $defaultValue ? '1' : '0';
            }

            $settings[$setting->value] = (string) $defaultValue;
        }

        return [
            'id' => (string) $this->languagePack->id,
            'settings' => $settings,
        ];
    }
}