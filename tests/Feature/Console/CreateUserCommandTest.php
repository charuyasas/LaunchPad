<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('launchpad:user creates a user from options', function () {
    $this->artisan('launchpad:user', [
        '--name' => 'Ops Admin',
        '--email' => 'ops@example.com',
        '--password' => 'a-strong-password-123',
    ])->assertSuccessful();

    $user = User::where('email', 'ops@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Ops Admin')
        ->and(Hash::check('a-strong-password-123', $user->password))->toBeTrue();
});

test('launchpad:user asks for missing details', function () {
    $this->artisan('launchpad:user')
        ->expectsQuestion('Name', 'Prompted Admin')
        ->expectsQuestion('Email', 'prompted@example.com')
        ->expectsQuestion('Password', 'a-strong-password-123')
        ->assertSuccessful();

    expect(User::where('email', 'prompted@example.com')->exists())->toBeTrue();
});

test('launchpad:user rejects a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('launchpad:user', [
        '--name' => 'Second',
        '--email' => 'taken@example.com',
        '--password' => 'a-strong-password-123',
    ])->assertFailed();

    expect(User::where('email', 'taken@example.com')->count())->toBe(1);
});
