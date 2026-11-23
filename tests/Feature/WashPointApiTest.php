<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ServiceType;
use App\Models\LaundryOrder;
use App\Models\Payment;
use App\Models\PickupSchedule;
use App\Models\OrderStatusHistory;

class WashPointApiTest extends TestCase
{
    protected static ?string $userToken = null;
    protected static ?string $adminToken = null;
    protected static ?int $regularUserId = null;
    protected static ?int $adminUserId = null;

    protected static ?int $serviceTypeId = null;
    protected static ?int $laundryOrderId = null;
    protected static ?int $paymentId = null;
    protected static ?int $pickupScheduleId = null;
    protected static ?int $orderStatusHistoryId = null;

    /**
     * 1. Test Endpoint Root API (Public)
     */
    public function test_api_root_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api');
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'WashPoint REST API is running successfully',
                 ]);
    }

    /**
     * 2. Test Unauthenticated Requests are Rejected (401)
     */
    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/service-types');
        $response->assertStatus(401);
    }

    /**
     * 3. Test Register & Login (Authentication) for Regular User & Admin
     */
    public function test_authentication_register_and_login(): void
    {
        $timestamp = time();

        // Register Regular User (Role: user)
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

        // Register Admin (Role: admin)
        $adminResponse = $this->postJson('/api/register', [
            'name' => 'Admin WashPoint',
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
     * 4. Test Anggota 1: Service Types (CRU oleh Regular User)
     */
    public function test_service_type_cru_by_regular_user(): void
    {
        // CREATE (Post) - Regular user boleh
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                               ->postJson('/api/service-types', [
                                   'user_id' => self::$regularUserId,
                                   'name' => 'Cuci Kering Setrika Reguler',
                                   'price_per_kg' => 7000,
                                   'estimated_hours' => 24,
                                   'is_active' => true,
                               ]);

        $createResponse->assertStatus(201)
                       ->assertJson([
                           'success' => true,
                           'message' => 'Jenis layanan berhasil ditambahkan',
                       ]);

        self::$serviceTypeId = $createResponse->json('data.id');

        // READ (Get Index & Show) - Regular user boleh
        $indexResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                              ->getJson('/api/service-types');
        $indexResponse->assertStatus(200);

        $showResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                             ->getJson('/api/service-types/' . self::$serviceTypeId);
        $showResponse->assertStatus(200)
                     ->assertJson([
                         'success' => true,
                         'data' => ['id' => self::$serviceTypeId]
                     ]);

        // UPDATE (Patch) - Regular user boleh
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                               ->patchJson('/api/service-types/' . self::$serviceTypeId, [
                                   'price_per_kg' => 7500,
                               ]);
        $updateResponse->assertStatus(200)
                       ->assertJson([
                           'success' => true,
                           'data' => ['price_per_kg' => 7500]
                       ]);
    }

    /**
     * 5. Test Anggota 2: Laundry Orders (CRU oleh Regular User)
     */
    public function test_laundry_order_cru_by_regular_user(): void
    {
        // CREATE
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                               ->postJson('/api/laundry-orders', [
                                   'user_id' => self::$regularUserId,
                                   'service_type_id' => self::$serviceTypeId,
                                   'customer_name' => 'Siti Aminah',
                                   'customer_phone' => '081234567890',
                                   'weight_kg' => 3.50,
                                   'total_price' => 26250,
                                   'status' => 'received',
                                   'notes' => 'Tolong pisahkan pakaian putih',
                               ]);

        $createResponse->assertStatus(201)
                       ->assertJson(['success' => true]);

        self::$laundryOrderId = $createResponse->json('data.id');

        // READ
        $showResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                             ->getJson('/api/laundry-orders/' . self::$laundryOrderId);
        $showResponse->assertStatus(200)
                     ->assertJson(['success' => true]);

        // UPDATE
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                               ->putJson('/api/laundry-orders/' . self::$laundryOrderId, [
                                   'status' => 'processing',
                               ]);
        $updateResponse->assertStatus(200)
                       ->assertJson([
                           'success' => true,
                           'data' => ['status' => 'processing']
                       ]);
    }

    /**
     * 6. Test Anggota 3: Payments (CRU oleh Regular User)
     */
    public function test_payment_cru_by_regular_user(): void
    {
        // CREATE
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                               ->postJson('/api/payments', [
                                   'user_id' => self::$regularUserId,
                                   'laundry_order_id' => self::$laundryOrderId,
                                   'amount' => 26250,
                                   'method' => 'qris',
                                   'payment_status' => 'paid',
                                   'paid_at' => now()->toDateTimeString(),
                               ]);

        $createResponse->assertStatus(201)
                       ->assertJson(['success' => true]);

        self::$paymentId = $createResponse->json('data.id');

        // READ
        $showResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                             ->getJson('/api/payments/' . self::$paymentId);
        $showResponse->assertStatus(200);

        // UPDATE
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                               ->patchJson('/api/payments/' . self::$paymentId, [
                                   'payment_status' => 'paid',
                               ]);
        $updateResponse->assertStatus(200);
    }

    /**
     * 7. Test Anggota 4: Pickup Schedules (CRU oleh Regular User)
     */
    public function test_pickup_schedule_cru_by_regular_user(): void
    {
        // CREATE
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                               ->postJson('/api/pickup-schedules', [
                                   'user_id' => self::$regularUserId,
                                   'laundry_order_id' => self::$laundryOrderId,
                                   'scheduled_at' => now()->addDays(1)->toDateTimeString(),
                                   'type' => 'pickup',
                                   'address' => 'Jl. Kaliurang KM 9, Sleman',
                                   'status' => 'scheduled',
                               ]);

        $createResponse->assertStatus(201);
        self::$pickupScheduleId = $createResponse->json('data.id');

        // READ
        $showResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                             ->getJson('/api/pickup-schedules/' . self::$pickupScheduleId);
        $showResponse->assertStatus(200);

        // UPDATE
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                               ->patchJson('/api/pickup-schedules/' . self::$pickupScheduleId, [
                                   'status' => 'completed',
                               ]);
        $updateResponse->assertStatus(200);
    }

    /**
     * 8. Test Anggota 5: Order Status Histories (CRU oleh Regular User)
     */
    public function test_order_status_history_cru_by_regular_user(): void
    {
        // CREATE
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                               ->postJson('/api/order-status-histories', [
                                   'user_id' => self::$regularUserId,
                                   'laundry_order_id' => self::$laundryOrderId,
                                   'status' => 'washing',
                                   'note' => 'Cucian sedang dicuci',
                               ]);

        $createResponse->assertStatus(201);
        self::$orderStatusHistoryId = $createResponse->json('data.id');

        // READ
        $showResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                             ->getJson('/api/order-status-histories/' . self::$orderStatusHistoryId);
        $showResponse->assertStatus(200);

        // UPDATE
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                               ->patchJson('/api/order-status-histories/' . self::$orderStatusHistoryId, [
                                   'note' => 'Cucian masuk tahap pengeringan',
                               ]);
        $updateResponse->assertStatus(200);
    }

    /**
     * 9. TEST AUTHORIZATION: Regular User DILARANG melakukan DELETE (403 Forbidden)
     */
    public function test_regular_user_cannot_delete_resource_returns_403(): void
    {
        // Mencoba DELETE service-types menggunakan token Regular User
        $response = $this->withHeader('Authorization', 'Bearer ' . self::$userToken)
                         ->deleteJson('/api/service-types/' . self::$serviceTypeId);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                 ]);
        $this->assertStringContainsString('CRU', $response->json('message'));
    }

    /**
     * 10. TEST AUTHORIZATION: Admin DIIZINKAN melakukan DELETE (CRUD Penuh)
     */
    public function test_admin_can_delete_resources_crud_success(): void
    {
        // Admin menghapus order-status-histories
        $delHistory = $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
                           ->deleteJson('/api/order-status-histories/' . self::$orderStatusHistoryId);
        $delHistory->assertStatus(200)
                   ->assertJson(['success' => true]);

        // Admin menghapus pickup-schedules
        $delPickup = $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
                          ->deleteJson('/api/pickup-schedules/' . self::$pickupScheduleId);
        $delPickup->assertStatus(200)
                  ->assertJson(['success' => true]);

        // Admin menghapus payments
        $delPayment = $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
                           ->deleteJson('/api/payments/' . self::$paymentId);
        $delPayment->assertStatus(200)
                   ->assertJson(['success' => true]);

        // Admin menghapus laundry-orders
        $delOrder = $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
                         ->deleteJson('/api/laundry-orders/' . self::$laundryOrderId);
        $delOrder->assertStatus(200)
                 ->assertJson(['success' => true]);

        // Admin menghapus service-types
        $delService = $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
                          ->deleteJson('/api/service-types/' . self::$serviceTypeId);
        $delService->assertStatus(200)
                   ->assertJson(['success' => true]);

        // Admin membersihkan user testing
        $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
             ->deleteJson('/api/users/' . self::$regularUserId);
        $this->withHeader('Authorization', 'Bearer ' . self::$adminToken)
             ->deleteJson('/api/users/' . self::$adminUserId);
    }
}
