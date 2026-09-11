<?php

use App\Models\User;

test('profile settings redirect to password settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertRedirect('/settings/security');
});

test('appearance settings remain available', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('appearance.edit'))
        ->assertOk();
});

test('users cannot update their name or email from account settings', function () {
    $user = User::factory()->create();
    $originalName = $user->name;
    $originalEmail = $user->email;

    $this->actingAs($user)->patch('/settings/profile', [
        'name' => 'Nombre modificado',
        'email' => 'modificado@example.com',
    ])->assertRedirect('/settings/security');

    expect($user->refresh())
        ->name->toBe($originalName)
        ->email->toBe($originalEmail);
});
