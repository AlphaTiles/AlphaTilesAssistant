<?php

namespace Tests\Feature;

use App\Models\LanguagePack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItemsControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_downloads_google_services_json_from_the_underscore_stored_file(): void
    {
        $languagePack = LanguagePack::factory()->create();
        $content = '{"project_info":{"project_id":"demo"}}';

        Storage::disk('public')->put("languagepacks/{$languagePack->id}/res/raw/google_services.json", $content);

        $response = $this->withoutMiddleware()->get("/languagepack/items/{$languagePack->id}/download/google-services.json");

        $response->assertOk();
        $response->assertDownload('google-services.json');

        $downloadedPath = $response->baseResponse->getFile()->getPathname();

        $this->assertSame($content, file_get_contents($downloadedPath));
    }

    /** @test */
    public function it_returns_404_when_the_requested_file_does_not_exist(): void
    {
        $languagePack = LanguagePack::factory()->create();

        $response = $this->withoutMiddleware()->get("/languagepack/items/{$languagePack->id}/download/missing-file.txt");

        $response->assertNotFound();
    }
}