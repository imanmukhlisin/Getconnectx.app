<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class RegisterTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Helper default payload to get a passing basis dataset.
     */
    protected function getValidPayload(): array
    {
        return [

            'email'                 => 'hello' . uniqid() . '@gmail.com',
            'password'              => 'C0nn3ctX!_Test_2026_SecurePwd',
            'password_confirmation' => 'C0nn3ctX!_Test_2026_SecurePwd',
        ];
    }

    public function test_user_can_register_successfully()
    {
        $payload = $this->getValidPayload();

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'next_step',
                     'data' => [
                         'user' => [
                             'id',

                             'email',
                             'registration_step',
                             'is_active',
                         ]
                     ],
                     'token',
                     'token_type'
                 ])
                 ->assertJsonPath('next_step', 'NEED_EMAIL_OTP');

        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],

            'registration_step' => User::STEP_REGISTERED,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'registration-token',
        ]);
    }


    public function test_registration_requires_email()
    {
        $payload = $this->getValidPayload();
        unset($payload['email']);

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_valid_email()
    {
        $payload = array_merge($this->getValidPayload(), [
            'email' => 'not-an-email',
        ]);

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_blocks_duplicate_email()
    {
        // Setup initial user
        $firstPayload = $this->getValidPayload();
        $this->postJson('/api/v1/auth/register', $firstPayload);

        // Attempt second registration with same email
        $secondPayload = array_merge($this->getValidPayload(), [
            'email' => $firstPayload['email'],
        ]);

        $response = $this->postJson('/api/v1/auth/register', $secondPayload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_password_minimum_8_characters()
    {
        $payload = array_merge($this->getValidPayload(), [
            'password' => 'Sh0rt!',
            'password_confirmation' => 'Sh0rt!',
        ]);

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_password_with_numbers()
    {
        $payload = array_merge($this->getValidPayload(), [
            'password' => 'NoNumbersHere!',
            'password_confirmation' => 'NoNumbersHere!',
        ]);

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_password_with_symbols()
    {
        $payload = array_merge($this->getValidPayload(), [
            'password' => 'NoSymbols123',
            'password_confirmation' => 'NoSymbols123',
        ]);

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_password_mixed_case()
    {
        $payload = array_merge($this->getValidPayload(), [
            'password' => 'nocapitals123!',
            'password_confirmation' => 'nocapitals123!',
        ]);

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_matching_password_confirmation()
    {
        $payload = array_merge($this->getValidPayload(), [
            'password' => 'ValidPass123!',
            'password_confirmation' => 'Mismatch123!',
        ]);

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }
}
