<?php

test('the application redirects guests to login route', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
