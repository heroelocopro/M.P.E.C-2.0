<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\SchoolAnnouncement;
use Inertia\Testing\AssertableInertia as Assert;

test('every authenticated school role can use the notification center', function (UserRole $role) {
    $user = User::factory()->withRole($role)->create();
    $user->notify(new SchoolAnnouncement('Reunión general', 'La reunión inicia a las 8:00 a. m.'));

    $response = $this->actingAs($user)->get(route('notifications'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('notifications/index')->has('notifications.data', 1)
        ->where('notifications.data.0.title', 'Reunión general')->where('notificationCenter.unread_count', 1));
})->with(UserRole::cases());

test('users can mark one of their notifications as read', function () {
    $user = User::factory()->create();
    $user->notify(new SchoolAnnouncement('Aviso', 'Mensaje importante'));
    $notification = $user->notifications()->firstOrFail();

    $response = $this->actingAs($user)->patch(route('notifications.read', $notification->id));

    $response->assertRedirect();
    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('users cannot mark another users notification as read', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $owner->notify(new SchoolAnnouncement('Privado', 'Solo para el propietario'));
    $notification = $owner->notifications()->firstOrFail();

    $this->actingAs($otherUser)->patch(route('notifications.read', $notification->id))->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

test('users mark all their notifications without changing another account', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $user->notify(new SchoolAnnouncement('Primera', 'Mensaje'));
    $user->notify(new SchoolAnnouncement('Segunda', 'Mensaje'));
    $otherUser->notify(new SchoolAnnouncement('Otro usuario', 'Mensaje'));

    $this->actingAs($user)->patch(route('notifications.read-all'))->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(0);
    expect($otherUser->unreadNotifications()->count())->toBe(1);
});
