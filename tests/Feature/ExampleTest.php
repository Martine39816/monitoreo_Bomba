<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     * La ruta "/" redirige al login o al dashboard, por lo que
     * debe devolver 302 en lugar de 200.
     */
    public function test_the_application_returns_a_redirect_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
    }
}

