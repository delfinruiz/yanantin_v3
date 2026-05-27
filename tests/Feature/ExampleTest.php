<?php

test('the application returns a successful response', function () {
    $response = $this->get('http://localhost/');

    $response->assertStatus(200);
});
