<?php

use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('logs in with username and password using unique opaque sessions', function () {
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

    expect($first->json('token'))->toHaveLength(64)
        ->not->toBe($second->json('token'))
        ->and(Session::where('token', $first->json('token'))->first())
        ->user_id->toBe($user->id)
        ->expires_at->isFuture()->toBeTrue();
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

it('returns the user resolved by the opaque session middleware', function () {
    ['user' => $user, 'token' => $token] = authenticatedSession();

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertSuccessful()
        ->assertJsonPath('id', $user->id);
});

it('rejects invalid opaque sessions', function (string $case) {
    $user = User::factory()->create(['is_active' => $case !== 'inactive']);

    $token = match ($case) {
        'missing' => null,
        'unknown' => Str::random(64),
        'expired' => Session::factory()->for($user)->expired()->create()->token,
        'revoked' => Session::factory()->for($user)->revoked()->create()->token,
        'inactive' => Session::factory()->for($user)->create()->token,
    };

    $request = $this;
    if ($token !== null) {
        $request = $request->withToken($token);
    }

    $request->getJson('/api/user')->assertUnauthorized();
})->with(['missing', 'unknown', 'expired', 'revoked', 'inactive']);

it('revokes the current opaque session on logout', function () {
    ['session' => $session, 'token' => $token] = authenticatedSession();

    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertSuccessful();

    expect($session->fresh()->revoked_at)->not->toBeNull();

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertUnauthorized();
});
