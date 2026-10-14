<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /* Корень бэкенда ведёт в админку */
    public function test_the_application_redirects_to_admin(): void
    {
        $this->get('/')->assertRedirect('/admin');
    }
}
