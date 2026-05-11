<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('openRegister', true);
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'nama' => 'Test User',
            'email' => 'test@example.com',
            'whatsapp' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
