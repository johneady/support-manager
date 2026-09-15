<?php

use App\Models\User;

/*
 * Browser coverage for the authentication path whose behaviour depends on
 * JavaScript actually running -- the Flux components and Livewire round-trips
 * the auth pages are built from. The feature suite already asserts the
 * server-side outcomes; what it cannot see is a page that renders but throws
 * in the browser, which is how these break on a dependency bump.
 */

test('a user can log in through the browser', function () {
    User::factory()->create([
        'email' => 'ada@example.com',
        'password' => 'password',
    ]);

    $page = visit('/login');

    $page->assertSee('Log in to your account')
        ->fill('email', 'ada@example.com')
        ->fill('password', 'password')
        ->click('@login-button')
        // Path rather than URL: the test server binds an ephemeral port, so
        // the host in any absolute URL differs from run to run.
        ->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticated();
});

test('an invalid password keeps the user on the login page with an error', function () {
    User::factory()->create([
        'email' => 'ada@example.com',
        'password' => 'password',
    ]);

    $page = visit('/login');

    $page->fill('email', 'ada@example.com')
        ->fill('password', 'wrong-password')
        ->click('@login-button')
        ->assertSee('These credentials do not match our records.')
        ->assertNoJavaScriptErrors();

    $this->assertGuest();
});
