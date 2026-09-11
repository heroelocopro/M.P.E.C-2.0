<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('administrators can view users grouped by school role', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    User::factory()->withRole(UserRole::Teacher)->create();
    User::factory()->withRole(UserRole::Student)->create();
    User::factory()->withRole(UserRole::Guardian)->create();

    $response = $this->actingAs($administrator)->get(route('admin.users'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/users/index')
        ->has('users.data', 4)
        ->where('counts.all', 4)
        ->where('counts.administrators', 1)
        ->where('counts.teachers', 1)
        ->where('counts.students', 1)
        ->where('counts.guardians', 1)
    );
});

test('administrators can filter users by role', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    User::factory()->withRole(UserRole::Teacher)->create();
    User::factory()->withRole(UserRole::Student)->create();

    $response = $this->actingAs($administrator)->get(route('admin.users', [
        'role' => UserRole::Teacher->value,
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/users/index')
        ->has('users.data', 1)
        ->where('users.data.0.role', UserRole::Teacher->value)
        ->where('filters.role', UserRole::Teacher->value)
    );
});

test('administrators can search users by name or email within a role', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    User::factory()->withRole(UserRole::Teacher)->create([
        'name' => 'María González',
        'email' => 'maria@aula.test',
    ]);
    User::factory()->withRole(UserRole::Teacher)->create([
        'name' => 'Carlos Ramírez',
        'email' => 'carlos@aula.test',
    ]);
    User::factory()->withRole(UserRole::Student)->create([
        'name' => 'María Estudiante',
    ]);

    $response = $this->actingAs($administrator)->get(route('admin.users', [
        'role' => UserRole::Teacher->value,
        'search' => 'maria@aula',
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/users/index')
        ->has('users.data', 1)
        ->where('users.data.0.name', 'María González')
        ->where('filters.role', UserRole::Teacher->value)
        ->where('filters.search', 'maria@aula')
    );
});

test('invalid role filters are rejected', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();

    $response = $this->actingAs($administrator)->get(route('admin.users', [
        'role' => 'super-admin',
    ]));

    $response->assertSessionHasErrors('role');
});

test('search filters longer than one hundred characters are rejected', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();

    $response = $this->actingAs($administrator)->get(route('admin.users', [
        'search' => str_repeat('a', 101),
    ]));

    $response->assertSessionHasErrors('search');
});

test('school roles other than administrator cannot view users', function (UserRole $role) {
    $user = User::factory()->withRole($role)->create();

    $response = $this->actingAs($user)->get(route('admin.users'));

    $response->assertForbidden();
})->with([
    'profesor' => UserRole::Teacher,
    'estudiante' => UserRole::Student,
    'acudiente' => UserRole::Guardian,
]);

test('guests are redirected from user management to login', function () {
    $response = $this->get(route('admin.users'));

    $response->assertRedirect(route('login'));
});
