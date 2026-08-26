<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Authentication\Models\User;
use Modules\Authentication\Models\Person;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login');
    }

    protected function createUser(array $attributes = []): User
    {
        if (empty($attributes['person_id'])) {
            $person = Person::create([
                'full_name' => 'Test User',
                'first_name' => 'Test',
                'last_name' => 'User',
                'gender' => 'male',
                'type' => 'player',
            ]);
            $attributes['person_id'] = $person->id;
        }

        if (empty($attributes['password'])) {
            $attributes['password'] = bcrypt('secret123');
        }

        return User::create($attributes);
    }

    public function test_login_allows_up_to_5_attempts_per_minute_and_throttles_on_6th()
    {
        $user = $this->createUser([
            'username' => 'rate_limit_user',
            'password' => bcrypt('correct_password'),
        ]);

        // 5 failed login attempts
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'username' => 'rate_limit_user',
                'password' => 'wrong_password',
            ]);

            $response->assertStatus(401);
        }

        // 6th attempt should be throttled (HTTP 429)
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'rate_limit_user',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'status' => 'error',
                'message' => 'تم تجاوز الحد المسموح من المحاولات. يرجى المحاولة لاحقاً.',
            ]);

        $this->assertTrue($response->headers->has('Retry-After'));
    }

    public function test_different_users_have_independent_login_throttling()
    {
        $user1 = $this->createUser([
            'username' => 'first_user',
            'password' => bcrypt('password123'),
        ]);

        $user2 = $this->createUser([
            'username' => 'second_user',
            'password' => bcrypt('password123'),
        ]);

        // Exhaust limit for first_user (5 attempts)
        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'username' => 'first_user',
                'password' => 'wrong_password',
            ]);
        }

        // first_user is throttled
        $response1 = $this->postJson('/api/v1/auth/login', [
            'username' => 'first_user',
            'password' => 'wrong_password',
        ]);
        $response1->assertStatus(429);

        // second_user should NOT be throttled and should succeed with correct credentials
        $response2 = $this->postJson('/api/v1/auth/login', [
            'username' => 'second_user',
            'password' => 'password123',
        ]);
        $response2->assertStatus(200);
    }

    public function test_api_routes_include_rate_limiting_headers()
    {
        $response = $this->getJson('/api/v1/auth/check-username?username=test_check_user');

        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
    }
}
