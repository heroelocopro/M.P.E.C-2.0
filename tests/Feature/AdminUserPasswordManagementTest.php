<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('administrators can update another users password', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    $user = User::factory()->withRole(UserRole::Student)->create();

    $this->actingAs($administrator)->put(route('admin.users.password.update', $user), [
        'password' => 'Nueva-clave-2026',
        'password_confirmation' => 'Nueva-clave-2026',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('Nueva-clave-2026', $user->refresh()->password))->toBeTrue();
});

test('non administrators cannot update another users password', function (UserRole $role) {
    $actor = User::factory()->withRole($role)->create();
    $user = User::factory()->create();
    $originalPassword = $user->password;

    $this->actingAs($actor)->put(route('admin.users.password.update', $user), [
        'password' => 'Nueva-clave-2026',
        'password_confirmation' => 'Nueva-clave-2026',
    ])->assertForbidden();

    expect($user->refresh()->password)->toBe($originalPassword);
})->with([UserRole::Teacher, UserRole::Student, UserRole::Guardian]);

test('administrator password updates require confirmation and a valid password', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    $user = User::factory()->create();
    $originalPassword = $user->password;

    $this->actingAs($administrator)->put(route('admin.users.password.update', $user), [
        'password' => 'corta',
        'password_confirmation' => 'diferente',
    ])->assertSessionHasErrors('password');

    expect($user->refresh()->password)->toBe($originalPassword);
});
