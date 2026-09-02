<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('removedPublicAdminRoutes')]
    public function test_dangerous_public_admin_routes_do_not_exist(
        string $method,
        string $uri,
        int $expectedStatus
    ): void {
        $this->json($method, $uri)->assertStatus($expectedStatus);
    }

    public static function removedPublicAdminRoutes(): array
    {
        return [
            'contact list' => ['GET', '/api/contacts', 404],
            'contact reply' => ['POST', '/api/contacts/1/reply', 404],
            'user list' => ['GET', '/api/users', 404],
            'user update' => ['PUT', '/api/users/1', 404],
            'category create' => ['POST', '/api/danhmuc', 405],
            'brand delete' => ['DELETE', '/api/thuonghieu/1', 405],
            'attribute create' => ['POST', '/api/thuoctinh', 405],
            'color update' => ['PUT', '/api/colors/1', 405],
            'product delete' => ['DELETE', '/api/sanpham/1', 405],
            'variant create' => ['POST', '/api/bienthe', 405],
            'variant image delete' => ['DELETE', '/api/bienthe-hinhanh/1', 405],
        ];
    }

    #[DataProvider('protectedAdminRoutes')]
    public function test_admin_mutations_require_sanctum_authentication(
        string $method,
        string $uri
    ): void {
        $this->json($method, $uri)->assertUnauthorized();
    }

    public static function protectedAdminRoutes(): array
    {
        return [
            'contact reply' => ['POST', '/api/admin/lien-he/reply/1'],
            'contact delete' => ['DELETE', '/api/admin/contacts/1'],
            'user create' => ['POST', '/api/admin/users'],
            'category create' => ['POST', '/api/admin/danhmuc'],
            'brand delete' => ['DELETE', '/api/admin/thuonghieu/1'],
            'attribute create' => ['POST', '/api/admin/thuoctinh'],
            'color update' => ['PUT', '/api/admin/colors/1'],
            'product delete' => ['DELETE', '/api/admin/sanpham/1'],
            'variant create' => ['POST', '/api/admin/bienthe'],
            'variant image delete' => ['DELETE', '/api/admin/bienthe-hinhanh/1'],
        ];
    }

    public function test_admin_delete_accepts_numeric_admin_id_for_staff_accounts(): void
    {
        $actor = Admin::create([
            'ten' => 'Quản trị viên test',
            'email' => 'admin.delete.' . uniqid() . '@example.com',
            'matkhau' => 'password123',
            'sodienthoai' => '0910000000',
            'vaitro' => 'admin',
            'trangthai' => 'active',
        ]);

        $staff = Admin::create([
            'ten' => 'Nhân viên test',
            'email' => 'staff.delete.' . uniqid() . '@example.com',
            'matkhau' => 'password123',
            'sodienthoai' => '0900000000',
            'vaitro' => 'marketing',
            'trangthai' => 'active',
        ]);

        $this->actingAs($actor, 'sanctum')
            ->deleteJson('/api/admin/users/' . $staff->id)
            ->assertOk()
            ->assertJsonPath('message', 'Xóa người dùng thành công');

        $this->assertDatabaseMissing('admins', ['id' => $staff->id]);
    }
}
