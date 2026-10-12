<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_user_can_retrieve_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'me.test@geoattend.test',
            'is_active' => true,
        ]);

        $token = $user->createToken('Device')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'work_location_id',
                        'is_active',
                    ],
                    'location',
                ],
                'message',
            ])
            ->assertJson([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'me.test@geoattend.test',
                    ],
                    'location' => null,
                ],
                'message' => 'Success',
            ]);
    }

    public function test_unauthenticated_request_to_me_returns_401(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
    }
}
