<?php

namespace Tests\Unit;

use App\Http\Middleware\AdminMiddleware;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AdminMiddlewareSecurityTest extends TestCase
{
    #[DataProvider('forbiddenStaffRoutes')]
    public function test_staff_cannot_access_admin_routes_without_the_required_permission(string $path, string $method): void
    {
        $request = Request::create($path, $method);
        $request->setUserResolver(fn () => (object) [
            'vaitro' => 'accountant',
            'trangthai' => 'active',
            'cac_quyen' => ['don_hang_xem', 'hoa_don_xem'],
        ]);

        $response = (new AdminMiddleware)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public static function forbiddenStaffRoutes(): array
    {
        return [
            'user management' => ['/api/admin/users', 'GET'],
            'order mutation with view-only permission' => ['/api/admin/orders/1/status', 'PUT'],
            'unmapped admin endpoint fails closed' => ['/api/admin/future-sensitive-endpoint', 'GET'],
        ];
    }

    public function test_staff_can_access_an_endpoint_with_the_required_permission(): void
    {
        $request = Request::create('/api/admin/orders', 'GET');
        $request->setUserResolver(fn () => (object) [
            'vaitro' => 'accountant',
            'trangthai' => 'active',
            'cac_quyen' => ['don_hang_xem', 'hoa_don_xem'],
        ]);

        $response = (new AdminMiddleware)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function test_super_admin_retains_full_access(): void
    {
        $request = Request::create('/api/admin/future-sensitive-endpoint', 'DELETE');
        $request->setUserResolver(fn () => (object) [
            'vaitro' => 'admin',
            'trangthai' => 'active',
        ]);

        $response = (new AdminMiddleware)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
