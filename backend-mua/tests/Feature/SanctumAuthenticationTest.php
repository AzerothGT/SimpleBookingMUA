<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('logs in with username and password using unique sanctum tokens', function () {
    $user = User::factory()->create([
        'username' => 'owner',
        'password_hash' => 'secret-password',
        'is_active' => true,
    ]);

    $first = $this->postJson('/api/login', [
        'username' => 'owner',
        'password' => 'secret-password',
    ])->assertSuccessful();
    $second = $this->postJson('/api/login', [
        'username' => 'owner',
        'password' => 'secret-password',
    ])->assertSuccessful();

    expect($first->json('token'))->not->toBeEmpty()
        ->and($first->json('token'))->not->toBe($second->json('token'))
        ->and($first->json('expires_at'))->not->toBeNull();
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'username' => 'owner',
        'password_hash' => 'secret-password',
    ]);

    $this->postJson('/api/login', [
        'username' => 'owner',
        'password' => 'wrong-password',
    ])->assertUnauthorized();
});

it('rejects login for inactive users', function () {
    User::factory()->inactive()->create([
        'username' => 'owner',
        'password_hash' => 'secret-password',
    ]);

    $this->postJson('/api/login', [
        'username' => 'owner',
        'password' => 'secret-password',
    ])->assertForbidden();
});

it('returns the user resolved by sanctum', function () {
    ['user' => $user, 'token' => $token] = authenticatedSession();

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertSuccessful()
        ->assertJsonPath('id', $user->id);
});

it('rejects invalid tokens', function (string $case) {
    $user = User::factory()->create(['is_active' => $case !== 'inactive']);
    $token = match ($case) {
        'missing' => null,
        'unknown' => '1|'.Str::random(40),
        'expired' => $user->createToken('test', ['*'], now()->subDay())->plainTextToken,
        'inactive' => $user->createToken('test', ['*'], now()->addDays(30))->plainTextToken,
    };

    if ($case === 'inactive') {
        $user->update(['is_active' => false]);
    }

    $request = $this;
    if ($token !== null) {
        $request = $request->withToken($token);
    }

    $request->getJson('/api/user')->assertUnauthorized();
})->with(['missing', 'unknown', 'expired', 'inactive']);

it('revokes the current token on logout', function () {
    ['user' => $user, 'token' => $token] = authenticatedSession();

    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertSuccessful();

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertUnauthorized();
});
