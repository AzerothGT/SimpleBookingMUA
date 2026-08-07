<?php

test('the root path redirects to the swagger ui', function () {
    $this->get('/')->assertRedirect('/api/documentation');
});
