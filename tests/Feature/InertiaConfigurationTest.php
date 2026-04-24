<?php

test('inertia ssr is disabled for the browser-rendered app', function () {
    expect(config('inertia.ssr.enabled'))->toBeFalse();
});
