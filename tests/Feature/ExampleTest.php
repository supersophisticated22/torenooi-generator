<?php

test('home page shows the landing template content', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Maak je toernooi');
    $response->assertSee('Alles voor jouw toernooi');
    $response->assertSee('Simpel & transparant', false);
    $response->assertSee('Starter');
    $response->assertSee('€49');
});
