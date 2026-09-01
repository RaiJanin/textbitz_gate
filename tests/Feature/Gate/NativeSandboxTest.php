<?php

it('renders the native sandbox page (debug only)', function () {
    $this->get('/native-sandbox')
        ->assertOk();
});

it('runs the PHP native probe and reports the runtime status', function () {
    $this->getJson('/debug/native/probe')
        ->assertOk()
        ->assertJsonPath('nativeRuntime', false)
        ->assertJsonStructure(['nativeRuntime', 'note', 'checks' => [['name', 'ok', 'value', 'error', 'ms']]])
        ->assertJsonFragment(['name' => 'PlatformService::detect()', 'ok' => true]);
});

it('skips the blocking bridge calls when there is no native runtime', function () {
    $names = collect($this->getJson('/debug/native/probe')->json('checks'))->pluck('name');

    expect($names)->not->toContain('Device::getInfo()')          // bridge call — skipped off-device
        ->and($names)->toContain('extension_loaded(nativephp)');  // cheap check — always run
});
