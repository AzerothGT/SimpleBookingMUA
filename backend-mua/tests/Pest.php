<?php

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change different classes or "use()" traits, etc.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing expectations, you often need to check that values meet
| certain conditions. The "expect()" function gives you access to a set of
| "expectations" methods that you can use to assert different things. Of
| course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you often need some testing
| code specific to your project that you don't want to repeat. Here you
| can expose helpers as global functions to help you reduce the number
| of lines of code in tests.
|
*/

/**
 * @return array{user: User, token: string}
 */
function authenticatedSession(): array
{
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    return [
        'user' => $user,
        'token' => $user->createToken('test', ['*'], now()->addDays(30))->plainTextToken,
    ];
}

function tokenForRole(string $role): string
{
    $user = User::factory()->create([
        'role' => $role,
        'is_active' => true,
    ]);

    return $user->createToken('test', ['*'], now()->addDays(30))->plainTextToken;
}

function validBooking(array $attributes = []): Booking
{
    return Booking::factory()
        ->create($attributes);
}
