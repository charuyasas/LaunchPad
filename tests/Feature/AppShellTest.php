<?php

use App\Models\User;

test('the root url sends guests to the login page', function () {
    $this->get('/')->assertRedirect('/dashboard');
    $this->get('/dashboard')->assertRedirect(route('login'));
});

test('the root url sends signed in users to the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect('/dashboard');
});

test('the login page renders', function () {
    $this->get(route('login'))->assertOk();
});

test('removed starter kit pages are not reachable', function (string $method, string $url) {
    $this->actingAs(User::factory()->create())
        ->call($method, $url)
        ->assertNotFound();
})->with([
    'registration' => ['GET', '/register'],
    'forgot password' => ['GET', '/forgot-password'],
    'reset password' => ['GET', '/reset-password/token'],
    'email verification' => ['GET', '/email/verify'],
    'two factor challenge' => ['GET', '/two-factor-challenge'],
    'confirm password' => ['GET', '/user/confirm-password'],
    'security settings' => ['GET', '/settings/security'],
    'appearance settings' => ['GET', '/settings/appearance'],
    'passkey endpoints' => ['GET', '/.well-known/passkey-endpoints'],
]);

test('accounts cannot be deleted from the app', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete('/settings/profile', ['password' => 'password'])
        ->assertMethodNotAllowed();

    expect($user->fresh())->not->toBeNull();
});

test('guests cannot register an account', function () {
    $this->post('/register', [
        'name' => 'Intruder',
        'email' => 'intruder@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    expect(User::count())->toBe(0);
});
