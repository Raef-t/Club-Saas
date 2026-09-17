<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authentication\Models\User;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Services\UsernameSuggestionService;
use Laravel\Sanctum\Sanctum;

class UsernameValidationAndSuggestionTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_username_suggestion_service_validates_format()
    {
        // Valid English cases
        $this->assertTrue(UsernameSuggestionService::isValidFormat('ahmed_99'));
        $this->assertTrue(UsernameSuggestionService::isValidFormat('player.one'));
        $this->assertTrue(UsernameSuggestionService::isValidFormat('user-name'));
        $this->assertTrue(UsernameSuggestionService::isValidFormat('coach123'));

        // Valid Arabic cases
        $this->assertTrue(UsernameSuggestionService::isValidFormat('اسم_عربي'));
        $this->assertTrue(UsernameSuggestionService::isValidFormat('أحمد_99'));
        $this->assertTrue(UsernameSuggestionService::isValidFormat('كابتن_علي'));
        $this->assertTrue(UsernameSuggestionService::isValidFormat('محمد-2026'));
        $this->assertTrue(UsernameSuggestionService::isValidFormat('سارة.نادي'));
        $this->assertTrue(UsernameSuggestionService::isValidFormat('بطل'));

        // Invalid cases: too short (< 3 characters)
        $this->assertFalse(UsernameSuggestionService::isValidFormat('ab'));
        $this->assertFalse(UsernameSuggestionService::isValidFormat('اح'));

        // Invalid cases: too long (> 30 characters)
        $this->assertFalse(UsernameSuggestionService::isValidFormat('this_is_a_very_very_long_username_exceeding_thirty_chars'));
        $this->assertFalse(UsernameSuggestionService::isValidFormat('اسم_مستخدم_طويل_جدا_يتجاوز_ثلاثين_حرفا_في_الطول'));

        // Invalid cases: starts/ends with punctuation
        $this->assertFalse(UsernameSuggestionService::isValidFormat('_invalid_start'));
        $this->assertFalse(UsernameSuggestionService::isValidFormat('_احمد'));
        $this->assertFalse(UsernameSuggestionService::isValidFormat('invalid_end_'));
        $this->assertFalse(UsernameSuggestionService::isValidFormat('احمد_'));
        $this->assertFalse(UsernameSuggestionService::isValidFormat('-user'));
        $this->assertFalse(UsernameSuggestionService::isValidFormat('.player'));

        // Invalid cases: illegal symbols and whitespace
        $this->assertFalse(UsernameSuggestionService::isValidFormat('user@name!'));
        $this->assertFalse(UsernameSuggestionService::isValidFormat('أحمد#1'));
        $this->assertFalse(UsernameSuggestionService::isValidFormat('user name'));
        $this->assertFalse(UsernameSuggestionService::isValidFormat('أحمد علي'));
    }

    public function test_username_suggestion_service_checks_availability()
    {
        $user1 = $this->createUser([
            'username' => 'tec-ply-10001',
            'custom_username' => 'أحمد_البطل',
        ]);

        $this->assertFalse(UsernameSuggestionService::isAvailable('tec-ply-10001'));
        $this->assertFalse(UsernameSuggestionService::isAvailable('أحمد_البطل'));
        $this->assertTrue(UsernameSuggestionService::isAvailable('خالد_المحترف'));
        $this->assertTrue(UsernameSuggestionService::isAvailable('completely_new_username'));

        // Ignore current user
        $this->assertTrue(UsernameSuggestionService::isAvailable('أحمد_البطل', $user1->id));
    }

    public function test_username_suggestion_service_generates_valid_unique_suggestions()
    {
        $this->createUser([
            'username' => 'tec-ply-10002',
            'custom_username' => 'أحمد',
        ]);

        $suggestions = UsernameSuggestionService::generateSuggestions('أحمد', 'أحمد علي');

        $this->assertNotEmpty($suggestions);
        $this->assertCount(5, $suggestions);

        foreach ($suggestions as $suggestion) {
            $this->assertTrue(UsernameSuggestionService::isValidFormat($suggestion), "Suggestion '{$suggestion}' failed format validation");
            $this->assertTrue(UsernameSuggestionService::isAvailable($suggestion), "Suggestion '{$suggestion}' is already taken in DB");
        }
    }

    public function test_check_username_api_returns_available_for_new_arabic_username()
    {
        $response = $this->getJson('/api/v1/auth/check-username?username=' . urlencode('بطل_النادي'));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'username' => 'بطل_النادي',
                    'is_available' => true,
                    'suggestions' => [],
                ]
            ]);
    }

    public function test_check_username_api_returns_suggestions_when_arabic_username_is_taken()
    {
        $this->createUser([
            'username' => 'tec-ply-10003',
            'custom_username' => 'كابتن_سامي',
        ]);

        $response = $this->getJson('/api/v1/auth/check-username?username=' . urlencode('كابتن_سامي'));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'username' => 'كابتن_سامي',
                    'is_available' => false,
                ]
            ]);

        $suggestions = $response->json('data.suggestions');
        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions);

        foreach ($suggestions as $suggestion) {
            $this->assertTrue(UsernameSuggestionService::isValidFormat($suggestion));
            $this->assertTrue(UsernameSuggestionService::isAvailable($suggestion));
        }
    }

    public function test_set_custom_username_endpoint_returns_suggestions_on_duplicate_arabic_username()
    {
        $this->createUser([
            'username' => 'tec-ply-10004',
            'custom_username' => 'نجم_الساحة',
        ]);

        $currentUser = $this->createUser([
            'username' => 'tec-ply-10005',
            'custom_username' => null,
        ]);

        Sanctum::actingAs($currentUser);

        $response = $this->postJson('/api/v1/auth/set-custom-username', [
            'custom_username' => 'نجم_الساحة',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'data' => [
                    'is_available' => false,
                ]
            ]);

        $this->assertNotEmpty($response->json('data.suggestions'));
    }

    public function test_set_custom_username_endpoint_succeeds_for_unique_arabic_username()
    {
        $currentUser = $this->createUser([
            'username' => 'tec-ply-10006',
            'custom_username' => null,
        ]);

        Sanctum::actingAs($currentUser);

        $response = $this->postJson('/api/v1/auth/set-custom-username', [
            'custom_username' => 'أحمد_البطل_99',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'username' => 'tec-ply-10006',
                    'custom_username' => 'أحمد_البطل_99',
                ]
            ]);

        $this->assertEquals('أحمد_البطل_99', $currentUser->fresh()->custom_username);
    }

    public function test_login_with_arabic_custom_username()
    {
        $this->createUser([
            'username' => 'tec-ply-10007',
            'custom_username' => 'الأسطورة_2026',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'الأسطورة_2026',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'user' => [
                        'custom_username' => 'الأسطورة_2026',
                    ],
                ]
            ]);
    }

    public function test_change_password_with_arabic_custom_username()
    {
        $currentUser = $this->createUser([
            'username' => 'tec-ply-10008',
            'custom_username' => null,
            'password' => bcrypt('oldpassword123'),
        ]);

        Sanctum::actingAs($currentUser);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
            'custom_username' => 'صقر_النادي',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'custom_username' => 'صقر_النادي',
                ]
            ]);

        $this->assertEquals('صقر_النادي', $currentUser->fresh()->custom_username);
    }

    public function test_change_password_with_invalid_custom_username_returns_suggestions()
    {
        $currentUser = $this->createUser([
            'username' => 'tec-ply-10009',
            'custom_username' => null,
            'password' => bcrypt('oldpassword123'),
        ]);

        Sanctum::actingAs($currentUser);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
            'custom_username' => '_invalid_format!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'data' => [
                    'is_available' => false,
                ]
            ]);

        $this->assertNotEmpty($response->json('data.suggestions'));
    }

    public function test_change_password_with_taken_custom_username_returns_suggestions()
    {
        $this->createUser([
            'username' => 'existing_user_2026',
            'custom_username' => 'taken_name',
        ]);

        $currentUser = $this->createUser([
            'username' => 'tec-ply-10010',
            'custom_username' => null,
            'password' => bcrypt('oldpassword123'),
        ]);

        Sanctum::actingAs($currentUser);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
            'custom_username' => 'taken_name',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'data' => [
                    'is_available' => false,
                ]
            ]);

        $this->assertNotEmpty($response->json('data.suggestions'));
    }

    public function test_change_password_fails_when_new_password_is_default_12345678()
    {
        $currentUser = $this->createUser([
            'username' => 'tec-ply-10011',
            'custom_username' => null,
            'password' => bcrypt('12345678'),
        ]);

        Sanctum::actingAs($currentUser);

        // Plain text default password
        $response = $this->postJson('/api/v1/auth/change-password', [
            'new_password' => '12345678',
            'new_password_confirmation' => '12345678',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);

        $this->assertStringContainsString('12345678', $response->json('errors.new_password.0'));

        // Frontend hashed default password
        $hashedDefault = '119f6226667c1bc87396838134392ef4f4d38e68f1719aed7b2dff13be62d5ed';
        $responseHashed = $this->postJson('/api/v1/auth/change-password', [
            'new_password' => $hashedDefault,
            'new_password_confirmation' => $hashedDefault,
        ]);

        $responseHashed->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);

        $this->assertStringContainsString('12345678', $responseHashed->json('errors.new_password.0'));
    }

    public function test_reset_password_stores_hash_and_allows_login()
    {
        $admin = $this->createUser([
            'username' => 'admin_tester',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $user = $this->createUser([
            'username' => 'tec-ply-10012',
            'password' => bcrypt('oldpassword123'),
            'must_change_password' => false,
        ]);

        Sanctum::actingAs($admin);

        $hashedDefault = '119f6226667c1bc87396838134392ef4f4d38e68f1719aed7b2dff13be62d5ed';

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'user_id' => $user->id,
            'password' => $hashedDefault,
        ]);

        $response->assertStatus(200);
        $this->assertTrue((bool) $user->fresh()->must_change_password);

        // Login with hashed password (from web frontend)
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'tec-ply-10012',
            'password' => $hashedDefault,
        ]);
        $loginResponse->assertStatus(200);

        // Also login with plain '12345678' (from mobile / direct API)
        $loginPlainResponse = $this->postJson('/api/v1/auth/login', [
            'username' => 'tec-ply-10012',
            'password' => '12345678',
        ]);
        $loginPlainResponse->assertStatus(200);
    }
}



