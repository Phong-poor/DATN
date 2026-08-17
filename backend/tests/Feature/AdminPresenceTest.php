<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Admin;
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
        $viewer = Admin::create([
            'ten' => 'Admin Viewer',
            'name' => 'Admin Viewer',
            'email' => 'viewer@test.com',
            'matkhau' => bcrypt('password'),
            'vaitro' => 'admin',
            'last_active_at' => now()->subSeconds(5),
        ]);
        $onlineAdmin = Admin::create([
            'ten' => 'Online Admin',
            'name' => 'Online Admin',
            'email' => 'online@test.com',
            'matkhau' => bcrypt('password'),
            'vaitro' => 'admin',
            'last_active_at' => now()->subSeconds(30),
        ]);
        $offlineAdmin = Admin::create([
            'ten' => 'Offline Admin',
            'name' => 'Offline Admin',
            'email' => 'offline@test.com',
            'matkhau' => bcrypt('password'),
            'vaitro' => 'admin',
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
