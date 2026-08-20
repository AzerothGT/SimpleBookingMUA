<?php

test('the root path redirects to the swagger ui', function () {
    $this->get('/')->assertRedirect('/api/documentation');
});

test('swagger uses the forwarded https scheme behind a proxy', function () {
    $response = $this
        ->withServerVariables([
            'HTTP_HOST' => 'example.ngrok-free.dev',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])
        ->get('/api/documentation');

    $response
        ->assertOk()
        ->assertSee('https://localhost:8000/docs/asset/swagger-ui.css', escape: false)
        ->assertDontSee('http://localhost:8000/docs/asset', escape: false);
});
