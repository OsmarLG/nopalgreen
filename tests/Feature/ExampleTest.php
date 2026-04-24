<?php

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('NopalGreen')
        ->assertSee('"component":"welcome"', false)
        ->assertSee('"app_name":"NopalGreen"', false)
        ->assertSee('<title>NopalGreen</title>', false);
});
