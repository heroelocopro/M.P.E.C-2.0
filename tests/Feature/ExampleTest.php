<?php

use Inertia\Testing\AssertableInertia as Assert;

test('renders the school platform landing page', function () {
    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('welcome')
    );
});
