<?php

test('redirects guests to the login page', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('dashboard'));
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
