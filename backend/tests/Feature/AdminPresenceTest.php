<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPresenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_update_their_online_heartbeat(): void
    {
        $user = User::factory()->create([
            'last_active_at' => now()->subMinutes(10),
        ]);
        $token = $user->createToken('session_token')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/user/heartbeat');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'online_window_seconds' => 300,
            ]);

        $this->assertTrue($user->fresh()->last_active_at->greaterThan(now()->subSeconds(5)));
    }

    public function test_active_admins_marks_recent_heartbeats_as_online(): void
    {
        $viewer = User::factory()->create([
            'role' => 'admin',
            'last_active_at' => now()->subSeconds(5),
        ]);
        $onlineAdmin = User::factory()->create([
            'role' => 'admin',
            'last_active_at' => now()->subSeconds(30),
        ]);
        $offlineAdmin = User::factory()->create([
            'role' => 'admin',
            'last_active_at' => now()->subSeconds(360),
        ]);
        $token = $viewer->createToken('session_token')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/account/active-admins');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'online_window_seconds' => 300,
            ]);

        $admins = collect($response->json('data'))->keyBy('id');

        $this->assertTrue($admins[$onlineAdmin->id]['is_online']);
        $this->assertFalse($admins[$offlineAdmin->id]['is_online']);
    }
}
