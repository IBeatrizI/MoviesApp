<?php

namespace Tests\Feature;

use Tests\TestCase;

class CatalogFrontendTest extends TestCase
{
    public function test_homepage_serves_the_catalog_and_separate_assets(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Explorar filmes')
            ->assertSee('Séries')
            ->assertSee('assets/css/catalog.css')
            ->assertSee('assets/js/catalog.js');
    }
}
