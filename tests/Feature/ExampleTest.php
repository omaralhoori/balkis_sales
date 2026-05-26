<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the application redirects guests to login page', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

test('the application returns a successful response for authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(200);
});
