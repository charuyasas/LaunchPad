<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('the password page is displayed', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('user-password.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/Password'));
});

test('guests cannot see the password page', function () {
    $this->get(route('user-password.edit'))->assertRedirect(route('login'));
});

test('the password can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('user-password.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('user-password.edit'));

    expect(Hash::check('new-password-123', $user->refresh()->password))->toBeTrue();
});

test('the current password must be correct', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('user-password.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('user-password.edit'));
});
