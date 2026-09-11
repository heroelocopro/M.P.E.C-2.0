<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('administrators can visit academic settings', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();

    $response = $this->actingAs($administrator)->get(route('admin.academic-settings'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/academic-settings')
    );
});

test('school roles other than administrator cannot visit academic settings', function (UserRole $role) {
    $user = User::factory()->withRole($role)->create();

    $response = $this->actingAs($user)->get(route('admin.academic-settings'));

    $response->assertForbidden();
})->with([
    'profesor' => UserRole::Teacher,
    'estudiante' => UserRole::Student,
    'acudiente' => UserRole::Guardian,
]);

test('guests are redirected from academic settings to login', function () {
    $response = $this->get(route('admin.academic-settings'));

    $response->assertRedirect(route('login'));
});
