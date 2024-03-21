<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LangInfoTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_create_language_pack_loads()
    {
        $this->withoutMiddleware();
        
        $response = $this->get('/languagepack/create');

        $response->assertStatus(200);
    }
}
