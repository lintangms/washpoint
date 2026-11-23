<?php

namespace Database\Seeders;

use App\Models\LaundryOrder;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PickupSchedule;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for WashPoint (Kelompok 5).
     *
     * Akun testing yang otomatis dibuat (sesuai INFO_KELOMPOK5.txt):
     * - Admin : admin@washpoint.test / admin123
     * - User 1: user@washpoint.test  / user123
     * - User 2: user2@washpoint.test / user123 (untuk uji ownership)
     */
    public function run(): void
    {
        // 1. Seed Users (minimal 1 admin dan 1 user, plus user2 untuk uji ownership)
        $admin = User::updateOrCreate(
            ['email' => 'admin@washpoint.test'],
            [
                'name' => 'Admin WashPoint',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        $user1 = User::updateOrCreate(
            ['email' => 'user@washpoint.test'],
            [
                'name' => 'Budi Kasir',
                'password' => Hash::make('user123'),
                'role' => 'user',
            ]
        );

        $user2 = User::updateOrCreate(
            ['email' => 'user2@washpoint.test'],
            [
                'name' => 'Siti Petugas',
                'password' => Hash::make('user123'),
                'role' => 'user',
            ]
        );

        // 2. Seed Anggota 1: service_types
        $serviceReguler = ServiceType::firstOrCreate(
            ['name' => 'Cuci Kering Setrika Reguler'],
            [
                'user_id' => $user1->id,
                'price_per_kg' => 7000,
                'estimated_hours' => 24,
                'is_active' => true,
            ]
        );

        $serviceExpress = ServiceType::firstOrCreate(
            ['name' => 'Cuci Express 6 Jam'],
            [
                'user_id' => $user2->id,
                'price_per_kg' => 12000,
                'estimated_hours' => 6,
                'is_active' => true,
            ]
        );

        ServiceType::firstOrCreate(
            ['name' => 'Cuci Bed Cover & Selimut'],
            [
                'user_id' => $admin->id,
                'price_per_kg' => 15000,
                'estimated_hours' => 48,
                'is_active' => true,
            ]
        );

        // 3. Seed Anggota 2: laundry_orders
        $order1 = LaundryOrder::firstOrCreate(
            [
                'customer_name' => 'Andi Pratama',
                'customer_phone' => '081234567801',
            ],
            [
                'user_id' => $user1->id,
                'service_type_id' => $serviceReguler->id,
                'weight_kg' => 3.50,
                'total_price' => 24500,
                'status' => 'washing',
                'notes' => 'Pisahkan baju putih',
            ]
        );

        $order2 = LaundryOrder::firstOrCreate(
            [
                'customer_name' => 'Dewi Lestari',
                'customer_phone' => '081234567802',
            ],
            [
                'user_id' => $user2->id,
                'service_type_id' => $serviceExpress->id,
                'weight_kg' => 2.00,
                'total_price' => 24000,
                'status' => 'received',
                'notes' => 'Ambil sore hari',
            ]
        );

        // 4. Seed Anggota 3: payments
        Payment::firstOrCreate(
            ['laundry_order_id' => $order1->id],
            [
                'user_id' => $user1->id,
                'amount' => $order1->total_price,
                'method' => 'qris',
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]
        );

        Payment::firstOrCreate(
            ['laundry_order_id' => $order2->id],
            [
                'user_id' => $user2->id,
                'amount' => $order2->total_price,
                'method' => 'cash',
                'payment_status' => 'pending',
                'paid_at' => null,
            ]
        );

        // 5. Seed Anggota 4: pickup_schedules
        PickupSchedule::firstOrCreate(
            ['laundry_order_id' => $order1->id],
            [
                'user_id' => $user1->id,
                'scheduled_at' => now()->addDay(),
                'type' => 'delivery',
                'address' => 'Jl. Veteran No. 12, Malang',
                'status' => 'scheduled',
            ]
        );

        PickupSchedule::firstOrCreate(
            ['laundry_order_id' => $order2->id],
            [
                'user_id' => $user2->id,
                'scheduled_at' => now()->addHours(4),
                'type' => 'pickup',
                'address' => 'Jl. Soekarno Hatta No. 45, Malang',
                'status' => 'scheduled',
            ]
        );

        // 6. Seed Anggota 5: order_status_histories
        OrderStatusHistory::firstOrCreate(
            [
                'laundry_order_id' => $order1->id,
                'status' => 'washing',
            ],
            [
                'user_id' => $user1->id,
                'note' => 'Pakaian sedang dalam proses pencucian',
            ]
        );

        OrderStatusHistory::firstOrCreate(
            [
                'laundry_order_id' => $order2->id,
                'status' => 'received',
            ],
            [
                'user_id' => $user2->id,
                'note' => 'Pesanan baru diterima di loket',
            ]
        );
    }
}
