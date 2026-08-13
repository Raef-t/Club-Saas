<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authentication\Models\User;
use Modules\Authentication\Services\UsernameSuggestionService;
use Laravel\Sanctum\Sanctum;

class UsernameValidationAndSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_username_suggestion_service_validates_format()
    {
        $this->assertTrue(UsernameSuggestionService::isValidFormat('ahmed_99'));
        $this->assertTrue(UsernameSuggestionService::isValidFormat('player.one'));
        $this->assertTrue(UsernameSuggestionService::isValidFormat('user-name'));
        $this->assertTrue(UsernameSuggestionService::isValidFormat('coach123'));

        // Invalid cases
        $this->assertFalse(UsernameSuggestionService::isValidFormat('ab')); // too short (< 3)
        $this->assertFalse(UsernameSuggestionService::isValidFormat('this_is_a_very_very_long_username_exceeding_thirty_chars')); // > 30
        $this->assertFalse(UsernameSuggestionService::isValidFormat('_invalid_start')); // starts with _
        $this->assertFalse(UsernameSuggestionService::isValidFormat('invalid_end_')); // ends with _
        $this->assertFalse(UsernameSuggestionService::isValidFormat('user@name!')); // illegal symbols
        $this->assertFalse(UsernameSuggestionService::isValidFormat('اسم_عربي')); // non-ascii
    }

    public function test_username_suggestion_service_checks_availability()
    {
        $user1 = User::create([
            'username' => 'tec-ply-10001',
            'custom_username' => 'existing_player',
            'password' => 'secret123',
        ]);

        $this->assertFalse(UsernameSuggestionService::isAvailable('tec-ply-10001'));
        $this->assertFalse(UsernameSuggestionService::isAvailable('existing_player'));
        $this->assertTrue(UsernameSuggestionService::isAvailable('completely_new_username'));

        // Ignore current user
        $this->assertTrue(UsernameSuggestionService::isAvailable('existing_player', $user1->id));
    }

    public function test_username_suggestion_service_generates_valid_unique_suggestions()
    {
        User::create([
            'username' => 'tec-ply-10002',
            'custom_username' => 'ahmed',
            'password' => 'secret123',
        ]);

        $suggestions = UsernameSuggestionService::generateSuggestions('ahmed', 'Ahmed Ali');

        $this->assertNotEmpty($suggestions);
        $this->assertCount(5, $suggestions);

        foreach ($suggestions as $suggestion) {
            $this->assertTrue(UsernameSuggestionService::isValidFormat($suggestion), "Suggestion '{$suggestion}' failed format validation");
            $this->assertTrue(UsernameSuggestionService::isAvailable($suggestion), "Suggestion '{$suggestion}' is already taken in DB");
        }
    }

    public function test_check_username_api_returns_available_for_new_username()
    {
        $response = $this->getJson('/api/v1/auth/check-username?username=brand_new_user');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'username' => 'brand_new_user',
                    'is_available' => true,
                    'suggestions' => [],
                ]
            ]);
    }

    public function test_check_username_api_returns_suggestions_when_username_is_taken()
    {
        User::create([
            'username' => 'tec-ply-10003',
            'custom_username' => 'taken_username',
            'password' => 'secret123',
        ]);

        $response = $this->getJson('/api/v1/auth/check-username?username=taken_username');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'username' => 'taken_username',
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

    public function test_set_custom_username_endpoint_returns_suggestions_on_duplicate()
    {
        User::create([
            'username' => 'tec-ply-10004',
            'custom_username' => 'first_star',
            'password' => 'secret123',
        ]);

        $currentUser = User::create([
            'username' => 'tec-ply-10005',
            'custom_username' => null,
            'password' => 'secret123',
        ]);

        Sanctum::actingAs($currentUser);

        $response = $this->postJson('/api/v1/auth/set-custom-username', [
            'custom_username' => 'first_star',
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

    public function test_set_custom_username_endpoint_succeeds_for_unique_username()
    {
        $currentUser = User::create([
            'username' => 'tec-ply-10006',
            'custom_username' => null,
            'password' => 'secret123',
        ]);

        Sanctum::actingAs($currentUser);

        $response = $this->postJson('/api/v1/auth/set-custom-username', [
            'custom_username' => 'unique_star_99',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'username' => 'tec-ply-10006',
                    'custom_username' => 'unique_star_99',
                ]
            ]);

        $this->assertEquals('unique_star_99', $currentUser->fresh()->custom_username);
    }
}
