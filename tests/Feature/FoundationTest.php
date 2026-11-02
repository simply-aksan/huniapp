<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;

test('root url redirects guest to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

test('legacy foundation route redirects to home', function () {
    $response = $this->get('/foundation');

    $response->assertRedirect(route('home'));
});

test('root url redirects authenticated owner to owner dashboard', function () {
    $owner = User::where('email', 'aksan@huniapp.test')->first();
    $response = $this->actingAs($owner)->get('/');

    $response->assertRedirect(route('owner.dashboard'));
});

test('root url redirects authenticated tenant to tenant dashboard', function () {
    $tenant = User::where('email', 'budi@huniapp.test')->first();
    $response = $this->actingAs($tenant)->get('/');

    $response->assertRedirect(route('tenant.dashboard'));
});

test('owner portal layout foundation is accessible for authenticated owner', function () {
    $owner = User::where('email', 'aksan@huniapp.test')->first();
    $response = $this->actingAs($owner)->get(route('owner.dashboard'));

    $response->assertStatus(200);
    $response->assertSee('Selamat datang,');
    $response->assertSee('Kos Harmoni');
});

test('tenant portal layout foundation is accessible for authenticated tenant', function () {
    $tenant = User::where('email', 'budi@huniapp.test')->first();
    $response = $this->actingAs($tenant)->get(route('tenant.dashboard'));

    $response->assertStatus(200);
    $response->assertSee('BERANDA');
    $response->assertSee('Hai, Budi!');
});

test('application defines only owner and tenant roles without admin', function () {
    $roles = array_column(UserRole::cases(), 'value');

    expect($roles)->toBe(['owner', 'tenant'])
        ->and(in_array('admin', $roles, true))->toBeFalse();
});
