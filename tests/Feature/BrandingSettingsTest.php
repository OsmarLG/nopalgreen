<?php

use App\Models\AppSetting;
use App\Models\User;
use Database\Seeders\RolesAndMasterUserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view branding settings', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'brandingadmin01']);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('branding.edit'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('branding/index')
        ->where('branding.app_name', 'NopalGreen')
        ->where('branding.favicon_url', fn (string $faviconUrl) => str_contains($faviconUrl, 'app-logo-default.svg'))
    );
});

test('legacy settings branding url redirects to standalone branding page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'brandingadmin03']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/settings/branding')
        ->assertRedirect(route('branding.edit'));
});

test('admin can update branding and upload logo', function () {
    $this->seed(RolesAndMasterUserSeeder::class);
    Storage::fake('public');

    $admin = User::factory()->create(['username' => 'brandingadmin02']);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('branding.update'), [
        'app_name' => 'NopalGreen Express',
        'app_tagline' => 'Operacion diaria',
        'logo' => UploadedFile::fake()->image('logo.png', 256, 256),
    ]);

    $response->assertRedirect(route('branding.edit'));

    $branding = AppSetting::current();

    expect($branding->app_name)->toBe('NopalGreen Express');
    expect($branding->app_tagline)->toBe('Operacion diaria');
    expect($branding->logo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($branding->logo_path);
    expect($branding->logo_url)->toContain('?v=');
    expect($branding->favicon_url)->toContain('?v=');
});
