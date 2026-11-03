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
    protected static ?int $userId = null;
    protected static ?int $serviceTypeId = null;
    protected static ?int $laundryOrderId = null;
    protected static ?int $paymentId = null;
    protected static ?int $pickupScheduleId = null;
    protected static ?int $orderStatusHistoryId = null;

    /**
     * 1. Test Endpoint Root API
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
     * 2. Test User CRUD & Password Not Exposed
     */
    public function test_user_crud_and_password_hidden(): void
    {
        // CREATE
        $uniqueEmail = 'tester_' . time() . '@washpoint.test';
        $createResponse = $this->postJson('/api/users', [
            'name' => 'Budi Santoso',
            'email' => $uniqueEmail,
            'password' => 'secret123',
        ]);

        $createResponse->assertStatus(201)
                       ->assertJson([
                           'success' => true,
                           'message' => 'User berhasil ditambahkan',
                       ]);

        // Verifikasi password TIDAK bocor di response
        $this->assertArrayNotHasKey('password', $createResponse->json('data'));

        self::$userId = $createResponse->json('data.id');
        $this->assertNotNull(self::$userId);

        // GET ALL
        $indexResponse = $this->getJson('/api/users');
        $indexResponse->assertStatus(200)
                      ->assertJson(['success' => true]);
        
        foreach ($indexResponse->json('data') as $u) {
            $this->assertArrayNotHasKey('password', $u);
        }

        // GET DETAIL
        $showResponse = $this->getJson('/api/users/' . self::$userId);
        $showResponse->assertStatus(200)
                     ->assertJson([
                         'success' => true,
                         'data' => [
                             'id' => self::$userId,
                             'name' => 'Budi Santoso',
                         ]
                     ]);
        $this->assertArrayNotHasKey('password', $showResponse->json('data'));

        // UPDATE (PUT)
        $updateResponse = $this->putJson('/api/users/' . self::$userId, [
            'name' => 'Budi Santoso Updated',
        ]);
        $updateResponse->assertStatus(200)
                       ->assertJson([
                           'success' => true,
                           'data' => [
                               'name' => 'Budi Santoso Updated',
                           ]
                       ]);
    }

    /**
     * 3. Test Anggota 1: Service Types CRUD & Eager Loading
     */
    public function test_service_type_crud_and_eager_loading(): void
    {
        $this->assertNotNull(self::$userId, 'User must exist for foreign key');

        // CREATE
        $createResponse = $this->postJson('/api/service-types', [
            'user_id' => self::$userId,
            'name' => 'Cuci Komplit Reguler',
            'price_per_kg' => 7000,
            'estimated_hours' => 24,
            'is_active' => true,
        ]);

        $createResponse->assertStatus(201)
                       ->assertJson([
                           'success' => true,
                           'message' => 'Jenis layanan berhasil ditambahkan',
                           'data' => [
                               'name' => 'Cuci Komplit Reguler',
                               'price_per_kg' => 7000,
                           ]
                       ]);

        self::$serviceTypeId = $createResponse->json('data.id');

        // GET ALL (with user)
        $indexResponse = $this->getJson('/api/service-types');
        $indexResponse->assertStatus(200)
                      ->assertJsonStructure([
                          'success',
                          'message',
                          'data' => [
                              '*' => ['id', 'user_id', 'name', 'price_per_kg', 'user']
                          ]
                      ]);

        // GET DETAIL (with user & laundryOrders)
        $showResponse = $this->getJson('/api/service-types/' . self::$serviceTypeId);
        $showResponse->assertStatus(200)
                     ->assertJson([
                         'success' => true,
                         'data' => [
                             'id' => self::$serviceTypeId,
                             'name' => 'Cuci Komplit Reguler',
                         ]
                     ]);
        $this->assertArrayHasKey('user', $showResponse->json('data'));
        $this->assertArrayHasKey('laundry_orders', $showResponse->json('data'));

        // UPDATE (PATCH)
        $updateResponse = $this->patchJson('/api/service-types/' . self::$serviceTypeId, [
            'price_per_kg' => 8000,
        ]);
        $updateResponse->assertStatus(200)
                       ->assertJson([
                           'success' => true,
                           'data' => [
                               'price_per_kg' => 8000,
                           ]
                       ]);
    }

    /**
     * 4. Test Anggota 2: Laundry Orders CRUD & Eager Loading
     */
    public function test_laundry_order_crud_and_eager_loading(): void
    {
        // CREATE
        $createResponse = $this->postJson('/api/laundry-orders', [
            'user_id' => self::$userId,
            'service_type_id' => self::$serviceTypeId,
            'customer_name' => 'Siti Rahma',
            'customer_phone' => '081234567890',
            'weight_kg' => 3.50,
            'total_price' => 28000,
            'status' => 'received',
            'notes' => 'Pakaian warna putih tolong dipisah',
        ]);

        $createResponse->assertStatus(201)
                       ->assertJson([
                           'success' => true,
                           'message' => 'Pesanan laundry berhasil dibuat',
                           'data' => [
                               'customer_name' => 'Siti Rahma',
                               'total_price' => 28000,
                           ]
                       ]);

        self::$laundryOrderId = $createResponse->json('data.id');

        // GET ALL (with user & serviceType)
        $indexResponse = $this->getJson('/api/laundry-orders');
        $indexResponse->assertStatus(200)
                      ->assertJsonStructure([
                          'success',
                          'data' => [
                              '*' => ['id', 'user', 'service_type']
                          ]
                      ]);

        // GET DETAIL (with all relations)
        $showResponse = $this->getJson('/api/laundry-orders/' . self::$laundryOrderId);
        $showResponse->assertStatus(200);
        $this->assertArrayHasKey('user', $showResponse->json('data'));
        $this->assertArrayHasKey('service_type', $showResponse->json('data'));
        $this->assertArrayHasKey('payments', $showResponse->json('data'));
        $this->assertArrayHasKey('pickup_schedules', $showResponse->json('data'));
        $this->assertArrayHasKey('order_status_histories', $showResponse->json('data'));

        // UPDATE (PUT)
        $updateResponse = $this->putJson('/api/laundry-orders/' . self::$laundryOrderId, [
            'status' => 'processing',
        ]);
        $updateResponse->assertStatus(200)
                       ->assertJson([
                           'success' => true,
                           'data' => [
                               'status' => 'processing',
                           ]
                       ]);
    }

    /**
     * 5. Test Anggota 3: Payments CRUD & Eager Loading
     */
    public function test_payment_crud_and_eager_loading(): void
    {
        // CREATE
        $createResponse = $this->postJson('/api/payments', [
            'user_id' => self::$userId,
            'laundry_order_id' => self::$laundryOrderId,
            'amount' => 28000,
            'method' => 'cash',
            'payment_status' => 'paid',
            'paid_at' => now()->toDateTimeString(),
        ]);

        $createResponse->assertStatus(201)
                       ->assertJson([
                           'success' => true,
                           'message' => 'Pembayaran berhasil dicatat',
                           'data' => [
                               'amount' => 28000,
                               'payment_status' => 'paid',
                           ]
                       ]);

        self::$paymentId = $createResponse->json('data.id');

        // GET ALL
        $indexResponse = $this->getJson('/api/payments');
        $indexResponse->assertStatus(200)
                      ->assertJsonStructure([
                          'success',
                          'data' => [
                              '*' => ['id', 'user', 'laundry_order']
                          ]
                      ]);

        // GET DETAIL
        $showResponse = $this->getJson('/api/payments/' . self::$paymentId);
        $showResponse->assertStatus(200)
                     ->assertJson([
                         'success' => true,
                         'data' => ['id' => self::$paymentId]
                     ]);

        // UPDATE
        $updateResponse = $this->patchJson('/api/payments/' . self::$paymentId, [
            'method' => 'qris',
        ]);
        $updateResponse->assertStatus(200)
                       ->assertJson([
                           'success' => true,
                           'data' => ['method' => 'qris']
                       ]);
    }

    /**
     * 6. Test Anggota 4: Pickup Schedules CRUD & Eager Loading
     */
    public function test_pickup_schedule_crud_and_eager_loading(): void
    {
        // CREATE
        $createResponse = $this->postJson('/api/pickup-schedules', [
            'user_id' => self::$userId,
            'laundry_order_id' => self::$laundryOrderId,
            'scheduled_at' => now()->addDays(1)->toDateTimeString(),
            'type' => 'delivery',
            'address' => 'Jl. Kaliurang KM 9, Sleman, Yogyakarta',
            'status' => 'scheduled',
        ]);

        $createResponse->assertStatus(201)
                       ->assertJson([
                           'success' => true,
                           'message' => 'Jadwal penjemputan berhasil dibuat',
                           'data' => [
                               'type' => 'delivery',
                               'address' => 'Jl. Kaliurang KM 9, Sleman, Yogyakarta',
                           ]
                       ]);

        self::$pickupScheduleId = $createResponse->json('data.id');

        // GET ALL
        $indexResponse = $this->getJson('/api/pickup-schedules');
        $indexResponse->assertStatus(200)
                      ->assertJsonStructure([
                          'success',
                          'data' => [
                              '*' => ['id', 'user', 'laundry_order']
                          ]
                      ]);

        // GET DETAIL
        $showResponse = $this->getJson('/api/pickup-schedules/' . self::$pickupScheduleId);
        $showResponse->assertStatus(200)
                     ->assertJson([
                         'success' => true,
                         'data' => ['id' => self::$pickupScheduleId]
                     ]);

        // UPDATE
        $updateResponse = $this->patchJson('/api/pickup-schedules/' . self::$pickupScheduleId, [
            'status' => 'completed',
        ]);
        $updateResponse->assertStatus(200)
                       ->assertJson([
                           'success' => true,
                           'data' => ['status' => 'completed']
                       ]);
    }

    /**
     * 7. Test Anggota 5: Order Status Histories CRUD & Eager Loading
     */
    public function test_order_status_history_crud_and_eager_loading(): void
    {
        // CREATE
        $createResponse = $this->postJson('/api/order-status-histories', [
            'user_id' => self::$userId,
            'laundry_order_id' => self::$laundryOrderId,
            'status' => 'washing',
            'note' => 'Cucian sedang dalam proses pencucian',
        ]);

        $createResponse->assertStatus(201)
                       ->assertJson([
                           'success' => true,
                           'message' => 'Riwayat status pesanan berhasil ditambahkan',
                           'data' => [
                               'status' => 'washing',
                               'note' => 'Cucian sedang dalam proses pencucian',
                           ]
                       ]);

        self::$orderStatusHistoryId = $createResponse->json('data.id');

        // GET ALL
        $indexResponse = $this->getJson('/api/order-status-histories');
        $indexResponse->assertStatus(200)
                      ->assertJsonStructure([
                          'success',
                          'data' => [
                              '*' => ['id', 'user', 'laundry_order']
                          ]
                      ]);

        // GET DETAIL
        $showResponse = $this->getJson('/api/order-status-histories/' . self::$orderStatusHistoryId);
        $showResponse->assertStatus(200)
                     ->assertJson([
                         'success' => true,
                         'data' => ['id' => self::$orderStatusHistoryId]
                     ]);

        // UPDATE
        $updateResponse = $this->patchJson('/api/order-status-histories/' . self::$orderStatusHistoryId, [
            'note' => 'Cucian selesai dicuci, masuk tahap pengeringan',
        ]);
        $updateResponse->assertStatus(200)
                       ->assertJson([
                           'success' => true,
                           'data' => [
                               'note' => 'Cucian selesai dicuci, masuk tahap pengeringan'
                           ]
                       ]);
    }

    /**
     * 8. Test Validation Error Responses (422)
     */
    public function test_validation_errors_return_422(): void
    {
        $response = $this->postJson('/api/service-types', [
            // missing required fields
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Validasi gagal',
                 ])
                 ->assertJsonStructure(['errors']);
    }

    /**
     * 9. Test Not Found Responses (404)
     */
    public function test_not_found_returns_404(): void
    {
        $response = $this->getJson('/api/service-types/999999');
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Jenis layanan tidak ditemukan',
                     'data' => null
                 ]);
    }

    /**
     * 10. Test Deletion for Resources
     */
    public function test_delete_resources(): void
    {
        if (self::$orderStatusHistoryId) {
            $this->deleteJson('/api/order-status-histories/' . self::$orderStatusHistoryId)
                 ->assertStatus(200)
                 ->assertJson(['success' => true]);
        }

        if (self::$pickupScheduleId) {
            $this->deleteJson('/api/pickup-schedules/' . self::$pickupScheduleId)
                 ->assertStatus(200)
                 ->assertJson(['success' => true]);
        }

        if (self::$paymentId) {
            $this->deleteJson('/api/payments/' . self::$paymentId)
                 ->assertStatus(200)
                 ->assertJson(['success' => true]);
        }

        if (self::$laundryOrderId) {
            $this->deleteJson('/api/laundry-orders/' . self::$laundryOrderId)
                 ->assertStatus(200)
                 ->assertJson(['success' => true]);
        }

        if (self::$serviceTypeId) {
            $this->deleteJson('/api/service-types/' . self::$serviceTypeId)
                 ->assertStatus(200)
                 ->assertJson(['success' => true]);
        }

        if (self::$userId) {
            $this->deleteJson('/api/users/' . self::$userId)
                 ->assertStatus(200)
                 ->assertJson(['success' => true]);
        }
    }
}

