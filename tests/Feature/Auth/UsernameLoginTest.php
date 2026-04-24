<?php

use App\Models\User;

test('login screen uses username and hides public registration', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('"component":"auth\/login"', false)
        ->assertSee('"canRegister":false', false);
});

test('users can authenticate with username', function () {
    $user = User::factory()->create([
        'username' => 'operador01',
    ]);

    $response = $this->post(route('login.store'), [
        'username' => 'operador01',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration routes are disabled', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});
