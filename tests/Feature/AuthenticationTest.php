<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    $this->owner = User::where('email', 'aksan@huniapp.test')->first();
    $this->tenant = User::where('email', 'budi@huniapp.test')->first();
});

test('login screen is rendered with soft neo-brutalism design and branding', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200);
    $response->assertSee('Masuk ke Akun');
    $response->assertSee('Pemilik Kos');
    $response->assertSee('Penghuni Kos');
    $response->assertSee('Pak Aksan');
    $response->assertSee('Mas Budi');
});

test('owner can authenticate with valid credentials and is redirected to owner dashboard', function () {
    $response = $this->post(route('login'), [
        'email' => 'aksan@huniapp.test',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($this->owner);
    $response->assertRedirect(route('owner.dashboard'));
});

test('tenant can authenticate with valid credentials and is redirected to tenant dashboard', function () {
    $response = $this->post(route('login'), [
        'email' => 'budi@huniapp.test',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($this->tenant);
    $response->assertRedirect(route('tenant.dashboard'));
});

test('user cannot authenticate with invalid password', function () {
    $response = $this->from(route('login'))->post(route('login'), [
        'email' => 'aksan@huniapp.test',
        'password' => 'wrong-secret-password',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
});

test('login requires email and password fields', function () {
    $response = $this->from(route('login'))->post(route('login'), [
        'email' => '',
        'password' => '',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['email', 'password']);
});

test('authenticated user can logout and session is securely invalidated', function () {
    $response = $this->actingAs($this->owner)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status', 'Anda telah berhasil keluar dari HUNI App.');
});

test('unauthenticated guest is redirected to login when accessing owner portal', function () {
    $response = $this->get(route('owner.dashboard'));

    $response->assertRedirect(route('login'));
});

test('unauthenticated guest is redirected to login when accessing tenant portal', function () {
    $response = $this->get(route('tenant.dashboard'));

    $response->assertRedirect(route('login'));
});

test('authenticated tenant receives 403 forbidden when trying to access owner portal', function () {
    $response = $this->actingAs($this->tenant)->get(route('owner.dashboard'));

    $response->assertStatus(403);
});

test('authenticated owner receives 403 forbidden when trying to access tenant portal', function () {
    $response = $this->actingAs($this->owner)->get(route('tenant.dashboard'));

    $response->assertStatus(403);
});

test('authenticated owner accessing login route is redirected to owner dashboard', function () {
    $response = $this->actingAs($this->owner)->get(route('login'));

    $response->assertRedirect(route('owner.dashboard'));
});

test('authenticated tenant accessing login route is redirected to tenant dashboard', function () {
    $response = $this->actingAs($this->tenant)->get(route('login'));

    $response->assertRedirect(route('tenant.dashboard'));
});
