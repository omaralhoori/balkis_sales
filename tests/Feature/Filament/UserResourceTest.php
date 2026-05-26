<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['auth.super_admin_email' => 'super@example.com']);
});

test('guest cannot access user resource list page', function () {
    get('/admin/users')
        ->assertRedirect('/admin/login');
});

test('non-admin employee cannot access user resource list page', function () {
    $employee = User::factory()->create([
        'email' => 'employee@example.com',
        'role' => 'employee',
    ]);

    actingAs($employee)
        ->get('/admin/users')
        ->assertForbidden();
});

test('unauthorized admin user cannot access user resource list page', function () {
    $admin = User::factory()->create([
        'email' => 'other-admin@example.com',
        'role' => 'admin',
    ]);

    actingAs($admin)
        ->get('/admin/users')
        ->assertForbidden();
});

test('super admin user can access user resource list page', function () {
    $superAdmin = User::factory()->create([
        'email' => 'super@example.com',
        'role' => 'admin',
    ]);

    actingAs($superAdmin)
        ->get('/admin/users')
        ->assertSuccessful();
});

test('super admin can list users', function () {
    $superAdmin = User::factory()->create([
        'email' => 'super@example.com',
        'role' => 'admin',
    ]);

    $otherUser = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'employee',
    ]);

    actingAs($superAdmin);

    Livewire::test(ListUsers::class)
        ->assertSuccessful()
        ->assertSee('John Doe')
        ->assertSee('john@example.com');
});

test('super admin can create a user', function () {
    $superAdmin = User::factory()->create([
        'email' => 'super@example.com',
        'role' => 'admin',
    ]);

    actingAs($superAdmin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'New Employee',
            'email' => 'new@example.com',
            'password' => 'password123',
            'role' => 'employee',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', [
        'name' => 'New Employee',
        'email' => 'new@example.com',
        'role' => 'employee',
    ]);

    $createdUser = User::where('email', 'new@example.com')->first();
    expect(Hash::check('password123', $createdUser->password))->toBeTrue();
});

test('super admin can edit a user and change password', function () {
    $superAdmin = User::factory()->create([
        'email' => 'super@example.com',
        'role' => 'admin',
    ]);

    $userToEdit = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'role' => 'employee',
        'password' => Hash::make('oldpassword'),
    ]);

    actingAs($superAdmin);

    Livewire::test(EditUser::class, [
        'record' => $userToEdit->getKey(),
    ])
        ->fillForm([
            'name' => 'New Name',
            'email' => 'new-email@example.com',
            'password' => 'newsecurepassword',
            'role' => 'admin',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $userToEdit->refresh();
    expect($userToEdit->name)->toBe('New Name');
    expect($userToEdit->email)->toBe('new-email@example.com');
    expect($userToEdit->role)->toBe('admin');
    expect(Hash::check('newsecurepassword', $userToEdit->password))->toBeTrue();
});

test('editing user without password keeps existing password', function () {
    $superAdmin = User::factory()->create([
        'email' => 'super@example.com',
        'role' => 'admin',
    ]);

    $userToEdit = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'role' => 'employee',
        'password' => Hash::make('oldpassword'),
    ]);

    actingAs($superAdmin);

    Livewire::test(EditUser::class, [
        'record' => $userToEdit->getKey(),
    ])
        ->fillForm([
            'name' => 'New Name',
            'password' => '', // blank password
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $userToEdit->refresh();
    expect($userToEdit->name)->toBe('New Name');
    expect(Hash::check('oldpassword', $userToEdit->password))->toBeTrue();
});

test('super admin can delete a user', function () {
    $superAdmin = User::factory()->create([
        'email' => 'super@example.com',
        'role' => 'admin',
    ]);

    $userToDelete = User::factory()->create([
        'name' => 'Delete Me',
        'email' => 'delete@example.com',
    ]);

    actingAs($superAdmin);

    Livewire::test(EditUser::class, [
        'record' => $userToDelete->getKey(),
    ])
        ->callAction('delete');

    $this->assertDatabaseMissing('users', [
        'id' => $userToDelete->id,
    ]);
});
