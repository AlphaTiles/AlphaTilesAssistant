<?php

namespace Tests\Feature;

use App\Models\LanguagePack;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotesControllerTest extends TestCase
{
    use RefreshDatabase;

    private LanguagePack $languagePack;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languagePack = LanguagePack::factory()->create();
        $this->user = $this->languagePack->owner;
    }

    /** @test */
    public function it_lists_notes_in_the_order_they_were_added(): void
    {
        Note::create(['languagepackid' => $this->languagePack->id, 'text' => 'first']);
        Note::create(['languagepackid' => $this->languagePack->id, 'text' => 'second']);

        $response = $this->actingAs($this->user)->get("/languagepack/notes/{$this->languagePack->id}");

        $response->assertStatus(200);
        $response->assertSeeInOrder(['first', 'second']);
    }

    /** @test */
    public function it_adds_a_note(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/languagepack/notes/{$this->languagePack->id}", ['text' => 'a new note']);

        $response->assertStatus(302);
        $this->assertDatabaseHas('notes', [
            'languagepackid' => $this->languagePack->id,
            'text' => 'a new note',
        ]);
    }

    /** @test */
    public function it_edits_an_existing_note(): void
    {
        $note = Note::create(['languagepackid' => $this->languagePack->id, 'text' => 'original']);

        $response = $this->actingAs($this->user)
            ->patch("/languagepack/notes/{$this->languagePack->id}", [
                'items' => [
                    ['id' => $note->id, 'text' => 'updated'],
                ],
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'text' => 'updated',
        ]);
    }

    /** @test */
    public function it_deletes_a_note(): void
    {
        $note = Note::create(['languagepackid' => $this->languagePack->id, 'text' => 'to delete']);

        $response = $this->actingAs($this->user)
            ->delete("/languagepack/notes/{$this->languagePack->id}", [
                'deleteIds' => (string) $note->id,
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    /** @test */
    public function it_cancels_a_note_deletion(): void
    {
        $note = Note::create(['languagepackid' => $this->languagePack->id, 'text' => 'kept']);

        $response = $this->actingAs($this->user)
            ->delete("/languagepack/notes/{$this->languagePack->id}", [
                'deleteIds' => (string) $note->id,
                'btnCancel' => 'cancel',
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('notes', ['id' => $note->id]);
    }

    /** @test */
    public function checking_the_delete_box_and_saving_shows_a_confirmation_before_deleting(): void
    {
        $note = Note::create(['languagepackid' => $this->languagePack->id, 'text' => 'about to delete']);

        $response = $this->actingAs($this->user)
            ->patch("/languagepack/notes/{$this->languagePack->id}", [
                'items' => [
                    ['id' => $note->id, 'text' => $note->text, 'delete' => '1'],
                ],
            ]);

        $response->assertStatus(200);
        $response->assertSee('Are you sure want to delete the following notes?');
        $response->assertSee('about to delete');
        $this->assertDatabaseHas('notes', ['id' => $note->id]);
    }
}
