# PANDUAN PENGUJIAN REST API DENGAN POSTMAN (CRUD & ROLE AUTH)

**Mata Kuliah:** CIE61207 - Sistem Manajemen Laundry (WashPoint)  
**Base URL:** `http://127.0.0.1:8000/api`  
**File Koleksi:** `WashPoint_API.postman_collection.json`  

---

## CARA CEPAT IMPORT KE POSTMAN:
1. Buka aplikasi **Postman**.
2. Klik tombol **Import** (di kiri atas).
3. Pilih atau drag file **`WashPoint_API.postman_collection.json`** yang ada di folder project.
4. Pastikan server Laravel Anda menyala dengan perintah di terminal:
   ```bash
   php artisan serve
   ```
5. URL otomatis menggunakan variabel `{{base_url}}` = `http://127.0.0.1:8000/api`.

---

## 1. TAHAP AUTENTIKASI (MENDAPATKAN TOKEN)

Semua endpoint resource dilindungi oleh token Sanctum. Anda harus login untuk mendapatkan token Bearer.

### A. Register Akun Role `user`
- **Method:** `POST`
- **URL:** `{{base_url}}/register`
- **Headers:**
  - `Content-Type: application/json`
  - `Accept: application/json`
- **Body (raw JSON):**
  ```json
  {
      "name": "Petugas Budi",
      "email": "budi@washpoint.test",
      "password": "password123",
      "role": "user"
  }
  ```
- **Response Sukses (201 Created):**
  ```json
  {
      "success": true,
      "message": "Registrasi berhasil",
      "data": {
          "user": {
              "id": 1,
              "name": "Petugas Budi",
              "email": "budi@washpoint.test",
              "role": "user"
          },
          "access_token": "1|pZ9x...token...",
          "token_type": "Bearer"
      }
  }
  ```

---

### B. Register Akun Role `admin`
- **Method:** `POST`
- **URL:** `{{base_url}}/register`
- **Body (raw JSON):**
  ```json
  {
      "name": "Super Admin",
      "email": "admin@washpoint.test",
      "password": "password123",
      "role": "admin"
  }
  ```
- **Response Sukses (201 Created):**
  ```json
  {
      "success": true,
      "message": "Registrasi berhasil",
      "data": {
          "user": {
              "id": 2,
              "name": "Super Admin",
              "email": "admin@washpoint.test",
              "role": "admin"
          },
          "access_token": "2|kLm7...token...",
          "token_type": "Bearer"
      }
  }
  ```

---

### C. Login (Mendapatkan Token Aktif)
- **Method:** `POST`
- **URL:** `{{base_url}}/login`
- **Body (raw JSON):**
  ```json
  {
      "email": "admin@washpoint.test",
      "password": "password123"
  }
  ```
- **Response Sukses (200 OK):**
  Salin nilai `access_token` dari respon ini untuk digunakan pada tab **Authorization** -> **Bearer Token** di request selanjutnya.

---

## 2. PENGUJIAN HAK AKSES CRU VS CRUD (USER VS ADMIN)

> [!IMPORTANT]
> **Aturan Otorisasi:**
> - Role **`user`** hanya bisa **CRU** (*Create, Read, Update*). Jika mencoba **DELETE**, akan ditolak dengan status **403 Forbidden**.
> - Role **`admin`** bisa **CRUD** (*Create, Read, Update, Delete*) secara penuh.

### Uji 1: Role `user` Melakukan DELETE (Harus Ditolak 403 Forbidden)
- **Token:** Gunakan token milik `Petugas Budi` (role `user`).
- **Method:** `DELETE`
- **URL:** `{{base_url}}/service-types/1`
- **Response (403 Forbidden):**
  ```json
  {
      "success": false,
      "message": "Akses ditolak (Forbidden). Role 'user' hanya memiliki hak akses CRU (Create, Read, Update). Tindakan DELETE hanya dapat dilakukan oleh role 'admin'."
  }
  ```

### Uji 2: Role `admin` Melakukan DELETE (Harus Berhasil 200 OK)
- **Token:** Gunakan token milik `Super Admin` (role `admin`).
- **Method:** `DELETE`
- **URL:** `{{base_url}}/service-types/1`
- **Response (200 OK):**
  ```json
  {
      "success": true,
      "message": "Jenis layanan berhasil dihapus",
      "data": null
  }
  ```

---

## 3. PENGUJIAN CRUD PER ANGGOTA KELOMPOK

Setiap request di bawah ini wajib menyertakan header:
- `Authorization: Bearer <token>`
- `Content-Type: application/json`
- `Accept: application/json`

---

### ANGGOTA 1: `service_types` (Jenis Layanan Laundry)

#### 1. [CREATE] Tambah Layanan Baru
- **Method:** `POST`
- **URL:** `{{base_url}}/service-types`
- **Body:**
  ```json
  {
      "user_id": 1,
      "name": "Cuci Kering Setrika Express",
      "price_per_kg": 10000,
      "estimated_hours": 12,
      "is_active": true
  }
  ```
- **Response (201 Created):**
  ```json
  {
      "success": true,
      "message": "Jenis layanan berhasil ditambahkan",
      "data": {
          "id": 1,
          "user_id": 1,
          "name": "Cuci Kering Setrika Express",
          "price_per_kg": 10000,
          "estimated_hours": 12,
          "is_active": true
      }
  }
  ```

#### 2. [READ ALL] Tampilkan Semua Layanan
- **Method:** `GET`
- **URL:** `{{base_url}}/service-types`
- **Response (200 OK):** Menampilkan array list layanan beserta data relasi `user`.

#### 3. [READ DETAIL] Tampilkan Detail Layanan
- **Method:** `GET`
- **URL:** `{{base_url}}/service-types/1`
- **Response (200 OK):** Menampilkan detail layanan beserta data relasi `user` dan `laundry_orders`.

#### 4. [UPDATE] Perbarui Harga / Estimasi Waktu
- **Method:** `PATCH` (atau `PUT`)
- **URL:** `{{base_url}}/service-types/1`
- **Body:**
  ```json
  {
      "price_per_kg": 11000,
      "estimated_hours": 8
  }
  ```
- **Response (200 OK):** Data berhasil diperbarui.

#### 5. [DELETE] Hapus Layanan (Admin Only)
- **Method:** `DELETE`
- **URL:** `{{base_url}}/service-types/1`
- **Response (200 OK jika admin, 403 jika user)**

---

### ANGGOTA 2: `laundry_orders` (Pesanan Laundry)

#### 1. [CREATE] Buat Pesanan Baru
- **Method:** `POST`
- **URL:** `{{base_url}}/laundry-orders`
- **Body:**
  ```json
  {
      "user_id": 1,
      "service_type_id": 1,
      "customer_name": "Rian Ardiansyah",
      "customer_phone": "081298765432",
      "weight_kg": 4.5,
      "total_price": 49500,
      "status": "received",
      "notes": "Pakaian kemeja tolong disetrika rapi"
  }
  ```
- **Response (201 Created)**

#### 2. [READ ALL] Tampilkan Daftar Pesanan
- **Method:** `GET`
- **URL:** `{{base_url}}/laundry-orders`
- **Response (200 OK):** Menampilkan pesanan dengan relasi eager loading `user` dan `service_type`.

#### 3. [READ DETAIL] Tampilkan Detail Pesanan
- **Method:** `GET`
- **URL:** `{{base_url}}/laundry-orders/1`
- **Response (200 OK):** Menampilkan detail pesanan lengkap beserta seluruh relasi: `service_type`, `payments`, `pickup_schedules`, dan `order_status_histories`.

#### 4. [UPDATE] Perbarui Status Pesanan
- **Method:** `PATCH`
- **URL:** `{{base_url}}/laundry-orders/1`
- **Body:**
  ```json
  {
      "status": "processing"
  }
  ```
- **Response (200 OK)**

#### 5. [DELETE] Hapus Pesanan (Admin Only)
- **Method:** `DELETE`
- **URL:** `{{base_url}}/laundry-orders/1`

---

### ANGGOTA 3: `payments` (Pembayaran Laundry)

#### 1. [CREATE] Catat Pembayaran
- **Method:** `POST`
- **URL:** `{{base_url}}/payments`
- **Body:**
  ```json
  {
      "user_id": 1,
      "laundry_order_id": 1,
      "amount": 49500,
      "method": "qris",
      "payment_status": "paid"
  }
  ```
- **Response (201 Created)**

#### 2. [READ ALL & DETAIL]
- `GET {{base_url}}/payments`
- `GET {{base_url}}/payments/1`

#### 3. [UPDATE] Perbarui Status Pembayaran
- **Method:** `PATCH`
- **URL:** `{{base_url}}/payments/1`
- **Body:**
  ```json
  {
      "payment_status": "paid"
  }
  ```

#### 4. [DELETE] Hapus Pembayaran (Admin Only)
- **Method:** `DELETE`
- **URL:** `{{base_url}}/payments/1`

---

### ANGGOTA 4: `pickup_schedules` (Jadwal Penjemputan / Pengantaran)

#### 1. [CREATE] Buat Jadwal
- **Method:** `POST`
- **URL:** `{{base_url}}/pickup-schedules`
- **Body:**
  ```json
  {
      "user_id": 1,
      "laundry_order_id": 1,
      "scheduled_at": "2026-11-25 09:30:00",
      "type": "delivery",
      "address": "Kost Graha Mahasiswa No. 15, Sleman",
      "status": "scheduled"
  }
  ```
- **Response (201 Created)**

#### 2. [READ ALL & DETAIL]
- `GET {{base_url}}/pickup-schedules`
- `GET {{base_url}}/pickup-schedules/1`

#### 3. [UPDATE] Ubah Status Jadwal
- **Method:** `PATCH`
- **URL:** `{{base_url}}/pickup-schedules/1`
- **Body:**
  ```json
  {
      "status": "completed"
  }
  ```

#### 4. [DELETE] Hapus Jadwal (Admin Only)
- **Method:** `DELETE`
- **URL:** `{{base_url}}/pickup-schedules/1`

---

### ANGGOTA 5: `order_status_histories` (Riwayat Status Pesanan)

#### 1. [CREATE] Tambah Riwayat Log Status
- **Method:** `POST`
- **URL:** `{{base_url}}/order-status-histories`
- **Body:**
  ```json
  {
      "user_id": 1,
      "laundry_order_id": 1,
      "status": "ironing",
      "note": "Pakaian sedang dalam proses setrika uap"
  }
  ```
- **Response (201 Created)**

#### 2. [READ ALL & DETAIL]
- `GET {{base_url}}/order-status-histories`
- `GET {{base_url}}/order-status-histories/1`

#### 3. [UPDATE] Perbarui Catatan Log Status
- **Method:** `PATCH`
- **URL:** `{{base_url}}/order-status-histories/1`
- **Body:**
  ```json
  {
      "note": "Proses setrika selesai, pakaian siap dikemas rapi"
  }
  ```

#### 4. [DELETE] Hapus Riwayat Status (Admin Only)
- **Method:** `DELETE`
- **URL:** `{{base_url}}/order-status-histories/1`
