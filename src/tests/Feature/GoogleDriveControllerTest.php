<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\LanguagePack;
use App\Jobs\ExportDriveFolderJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GoogleDriveControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function export_page_returns_view_with_picker_context(): void
    {
        $user = User::factory()->create();
        $languagePack = LanguagePack::factory()->create(['user_id' => $user->id]);

        Session::put('drive_permissions_time', Carbon::now());
        Session::put('socialite_token', 'fake-token');
        Session::put('socialite_refresh_token', 'fake-refresh-token');

        $response = $this->actingAs($user)
            ->get("/languagepack/export/{$languagePack->id}");

        $response->assertStatus(200);
        $response->assertViewIs('languagepack.export');
        $response->assertViewHas('accessToken', 'fake-token');
        $response->assertViewHas('refreshToken', 'fake-refresh-token');
        $response->assertViewHas('userId', $user->id);

        $redirectResponse = $this->actingAs($user)
            ->get("/drive/export/{$languagePack->id}");

        $redirectResponse->assertRedirect("/languagepack/export/{$languagePack->id}");
    }

    /** @test */
    public function dispatchexport_endpoint_dispatches_export_job_with_selected_folder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $languagePack = LanguagePack::factory()->create(['user_id' => $user->id]);

        Session::put('socialite_token', 'fake-token');
        Session::put('socialite_refresh_token', 'fake-refresh-token');

        $response = $this->actingAs($user)
            ->postJson('/api/drive/dispatchexport', [
                'languagePackId' => $languagePack->id,
                'folderId' => 'target-folder-id-123',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'folderId' => 'target-folder-id-123',
        ]);

        Queue::assertPushed(ExportDriveFolderJob::class, function (ExportDriveFolderJob $job) use ($languagePack) {
            return $job->languagePack->id === $languagePack->id
                && $job->driveRootFolderId === 'target-folder-id-123';
        });
    }
}
