<?php

namespace Tests\Feature;

use Tests\TestCase;

class WashPointApiTest extends TestCase
{
    protected static ?string $userToken = null;
    protected static ?string $otherUserToken = null;
    protected static ?string $adminToken = null;
    protected static ?int $regularUserId = null;
    protected static ?int $otherUserId = null;
    protected static ?int $adminUserId = null;

    protected static ?int $serviceTypeId = null;
    protected static ?int $laundryOrderId = null;
    protected static ?int $paymentId = null;
    protected static ?int $pickupScheduleId = null;
    protected static ?int $orderStatusHistoryId = null;

    /**
     * 1. Test Endpoint Publik (Tanpa Login): /api & /api/public/service-types
     */
    public function test_public_endpoints_return_success_without_login(): void
    {
        $rootResponse = $this->getJson('/api');
        $rootResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'WashPoint REST API is running successfully',
            ]);

        $catalogResponse = $this->getJson('/api/public/service-types');
        $catalogResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * 2. Test Unauthenticated & Forged JWT Requests are Rejected (401)
     */
    public function test_unauthenticated_and_forged_jwt_requests_are_rejected(): void
    {
        $response = $this->getJson('/api/service-types');
        $response->assertStatus(401);

        // Token JWT palsu dengan signature tidak valid harus ditolak 401
        $header = rtrim(strtr(base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['sub' => '1', 'role' => 'admin', 'exp' => time() + 3600])), '+/', '-_'), '=');
        $forgedToken = "{$header}.{$payload}.forged_invalid_signature";

        $forgedResponse = $this->withHeader('Authorization', 'Bearer ' . $forgedToken)
            ->getJson('/api/service-types');
        $forgedResponse->assertStatus(401);
    }

    /**
     * 3. Test Login menggunakan akun hasil DatabaseSeeder (admin & user)
     */
    public function test_seeded_admin_and_user_can_login(): void
    {
        $adminLogin = $this->postJson('/api/login', [
            'email' => 'admin@washpoint.test',
            'password' => 'admin123',
        ]);
        $adminLogin->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['user' => ['role' => 'admin']],
            ]);

        $userLogin = $this->postJson('/api/login', [
            'email' => 'user@washpoint.test',
            'password' => 'user123',
        ]);
        $userLogin->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['user' => ['role' => 'user']],
            ]);
    }

    /**
     * 4. Test Register & Login (Authentication) untuk Regular User 1, User 2, & Admin
     */
    public function test_authentication_register_and_login(): void
    {
        $timestamp = time();

        // Register Regular User 1 (Role: user)
        $userResponse = $this->postJson('/api/register', [
            'name' => 'Budi Santoso',
            'email' => "budi_{$timestamp}@washpoint.test",
            'password' => 'secret123',
            'role' => 'user',
        ]);

        $userResponse->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['user', 'access_token']]);

        self::$userToken = $userResponse->json('data.access_token');
        self::$regularUserId = $userResponse->json('data.user.id');
        $this->assertEquals('user', $userResponse->json('data.user.role'));
        $this->assertArrayNotHasKey('password', $userResponse->json('data.user'));

        // Register Regular User 2 (Role: user, untuk uji Ownership)
        $otherUserResponse = $this->postJson('/api/register', [
            'name' => 'Siti Rahma',
            'email' => "siti_{$timestamp}@washpoint.test",
            'password' => 'secret123',
            'role' => 'user',
        ]);

        $otherUserResponse->assertStatus(201)
            ->assertJson(['success' => true]);

        self::$otherUserToken = $otherUserResponse->json('data.access_token');
        self::$otherUserId = $otherUserResponse->json('data.user.id');

        // Register Admin (Role: admin)
        $adminResponse = $this->postJson('/api/register', [
            'name' => 'Admin WashPoint Test',
            'email' => "admin_{$timestamp}@washpoint.test",
            'password' => 'secret123',
            'role' => 'admin',
        ]);

        $adminResponse->assertStatus(201)
            ->assertJson(['success' => true]);

        self::$adminToken = $adminResponse->json('data.access_token');
        self::$adminUserId = $adminResponse->json('data.user.id');
        $this->assertEquals('admin', $adminResponse->json('data.user.role'));

        // Test Endpoint /api/me dengan token
        $meResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->getJson('/api/me');
        $meResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => self::$regularUserId,
                    'role' => 'user',
                ]
            ]);
    }

    /**
     * 5. Test Anggota 1: Service Types (CRU oleh Regular User)
     */
    public function test_service_type_cru_by_regular_user(): void
    {
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->postJson('/api/service-types', [
                'name' => 'Cuci Kering Setrika Uji',
                'price_per_kg' => 7000,
                'estimated_hours' => 24,
                'is_active' => true,
            ]);

        $createResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Jenis layanan berhasil ditambahkan',
                'data' => ['user_id' => self::$regularUserId],
            ]);

        self::$serviceTypeId = $createResponse->json('data.id');

        $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->getJson('/api/service-types')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->getJson('/api/service-types/' . self::$serviceTypeId)
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => self::$serviceTypeId],
            ]);

        $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->patchJson('/api/service-types/' . self::$serviceTypeId, [
                'price_per_kg' => 7500,
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['price_per_kg' => 7500],
            ]);
    }

    /**
     * 6. Test Anggota 2: Laundry Orders (CRU oleh Regular User)
     */
    public function test_laundry_order_cru_by_regular_user(): void
    {
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->postJson('/api/laundry-orders', [
                'service_type_id' => self::$serviceTypeId,
                'customer_name' => 'Siti Aminah',
                'customer_phone' => '081234567890',
                'weight_kg' => 3.50,
                'total_price' => 26250,
                'status' => 'received',
                'notes' => 'Tolong pisahkan pakaian putih',
            ]);

        $createResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => ['user_id' => self::$regularUserId],
            ]);

        self::$laundryOrderId = $createResponse->json('data.id');

        $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->getJson('/api/laundry-orders/' . self::$laundryOrderId)
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->putJson('/api/laundry-orders/' . self::$laundryOrderId, [
                'status' => 'processing',
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['status' => 'processing'],
            ]);
    }

    /**
     * 7. Test Anggota 3: Payments (CRU oleh Regular User)
     */
    public function test_payment_cru_by_regular_user(): void
    {
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->postJson('/api/payments', [
                'laundry_order_id' => self::$laundryOrderId,
                'amount' => 26250,
                'method' => 'qris',
                'payment_status' => 'paid',
                'paid_at' => now()->toDateTimeString(),
            ]);

        $createResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => ['user_id' => self::$regularUserId],
            ]);

        self::$paymentId = $createResponse->json('data.id');

        $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->getJson('/api/payments/' . self::$paymentId)
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->patchJson('/api/payments/' . self::$paymentId, [
                'payment_status' => 'paid',
            ])
            ->assertStatus(200);
    }

    /**
     * 8. Test Anggota 4: Pickup Schedules (CRU oleh Regular User)
     */
    public function test_pickup_schedule_cru_by_regular_user(): void
    {
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->postJson('/api/pickup-schedules', [
                'laundry_order_id' => self::$laundryOrderId,
                'scheduled_at' => now()->addDays(1)->toDateTimeString(),
                'type' => 'pickup',
                'address' => 'Jl. Veteran No. 12, Malang',
                'status' => 'scheduled',
            ]);

        $createResponse->assertStatus(201);
        self::$pickupScheduleId = $createResponse->json('data.id');

        $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->getJson('/api/pickup-schedules/' . self::$pickupScheduleId)
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->patchJson('/api/pickup-schedules/' . self::$pickupScheduleId, [
                'status' => 'completed',
            ])
            ->assertStatus(200);
    }

    /**
     * 9. Test Anggota 5: Order Status Histories (CRU oleh Regular User)
     */
    public function test_order_status_history_cru_by_regular_user(): void
    {
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->postJson('/api/order-status-histories', [
                'laundry_order_id' => self::$laundryOrderId,
                'status' => 'washing',
                'note' => 'Cucian sedang dicuci',
            ]);

        $createResponse->assertStatus(201);
        self::$orderStatusHistoryId = $createResponse->json('data.id');

        $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->getJson('/api/order-status-histories/' . self::$orderStatusHistoryId)
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->patchJson('/api/order-status-histories/' . self::$orderStatusHistoryId, [
                'note' => 'Cucian masuk tahap pengeringan',
            ])
            ->assertStatus(200);
    }

    /**
     * 10. TEST NILAI TAMBAH (OWNERSHIP): User lain DILARANG Update data milik User 1 (403), Admin BOLEH (200)
     */
    public function test_ownership_blocks_other_user_update_and_allows_admin(): void
    {
        // User 2 tidak boleh update data milik User 1 di seluruh resource
        $this->withHeader('Authorization', 'Bearer ' . self::$otherUserToken)
            ->patchJson('/api/service-types/' . self::$serviceTypeId, ['price_per_kg' => 99999])
            ->assertStatus(403);

        $this->withHeader('Authorization', 'Bearer ' . self::$otherUserToken)
            ->patchJson('/api/laundry-orders/' . self::$laundryOrderId, ['status' => 'cancelled'])
            ->assertStatus(403);

        $this->withHeader('Authorization', 'Bearer ' . self::$otherUserToken)
            ->patchJson('/api/payments/' . self::$paymentId, ['payment_status' => 'pending'])
            ->assertStatus(403);

        $this->withHeader('Authorization', 'Bearer ' . self::$otherUserToken)
            ->patchJson('/api/pickup-schedules/' . self::$pickupScheduleId, ['status' => 'cancelled'])
            ->assertStatus(403);

        $this->withHeader('Authorization', 'Bearer ' . self::$otherUserToken)
            ->patchJson('/api/order-status-histories/' . self::$orderStatusHistoryId, ['note' => 'hacked'])
            ->assertStatus(403);

        $this->withHeader('Authorization', 'Bearer ' . self::$otherUserToken)
            ->patchJson('/api/users/' . self::$regularUserId, ['name' => 'Hacked'])
            ->assertStatus(403);

        // User 1 tidak boleh menaikkan role sendiri menjadi admin
        $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->patchJson('/api/users/' . self::$regularUserId, ['role' => 'admin'])
            ->assertStatus(403);

        // User 1 boleh update nama profilnya sendiri
        $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->patchJson('/api/users/' . self::$regularUserId, ['name' => 'Budi Santoso Updated'])
            ->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['name' => 'Budi Santoso Updated']]);

        // Admin bebas mengubah data milik User 1
        $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
            ->patchJson('/api/service-types/' . self::$serviceTypeId, ['price_per_kg' => 8000])
            ->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['price_per_kg' => 8000]]);
    }

    /**
     * 11. TEST AUTHORIZATION: Regular User DILARANG melakukan DELETE (403 Forbidden)
     */
    public function test_regular_user_cannot_delete_resource_returns_403(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
            ->deleteJson('/api/service-types/' . self::$serviceTypeId);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
        $this->assertStringContainsString('CRU', $response->json('message'));
    }

    /**
     * 12. TEST AUTHORIZATION: Admin DIIZINKAN melakukan DELETE (CRUD Penuh)
     */
    public function test_admin_can_delete_resources_crud_success(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
            ->deleteJson('/api/order-status-histories/' . self::$orderStatusHistoryId)
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
            ->deleteJson('/api/pickup-schedules/' . self::$pickupScheduleId)
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
            ->deleteJson('/api/payments/' . self::$paymentId)
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
            ->deleteJson('/api/laundry-orders/' . self::$laundryOrderId)
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
            ->deleteJson('/api/service-types/' . self::$serviceTypeId)
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Admin membersihkan user testing sementara
        $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
            ->deleteJson('/api/users/' . self::$regularUserId)
            ->assertStatus(200);
        $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
            ->deleteJson('/api/users/' . self::$otherUserId)
            ->assertStatus(200);
        $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
            ->deleteJson('/api/users/' . self::$adminUserId)
            ->assertStatus(200);
    }
}
