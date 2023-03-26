<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PostFormTest extends TestCase
{
    /** @test */

    function post_creation_page_contains_livewire_component()

    {

        $this->get('/create-post')->assertSeeLivewire('post-fomr');

    }
}
