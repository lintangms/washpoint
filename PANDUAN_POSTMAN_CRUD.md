# PANDUAN PENGUJIAN REST API DENGAN POSTMAN (CRUD & ROLE AUTH)

**Mata Kuliah:** CIE61207 - Sistem Manajemen Laundry (WashPoint)  
**Base URL:** `http://127.0.0.1:8000/api`  
**File Koleksi:** `WashPoint_API.postman_collection.json`  

---

> [!TIP]
> **Pembaruan Otomatisasi ID:**
> - Saat melakukan **CREATE (POST)**, Anda **TIDAK PERLU** lagi memasukkan `id` maupun `user_id`!
> - `id` otomatis bertambah (**Auto Increment** di database).
> - `user_id` otomatis diambil dari akun yang sedang login melalui **Bearer Token**.
> - ID hanya digunakan saat **READ Detail (GET)**, **UPDATE (PUT/PATCH)**, dan **DELETE** di bagian URL: `/api/{resource}/{id}`.

---

## 1. TAHAP AUTENTIKASI (MENDAPATKAN TOKEN)

### A. Register Akun Role `user`
- **Method:** `POST`
- **URL:** `{{base_url}}/register`
- **Body (raw JSON):**
  ```json
  {
      "name": "Petugas Budi",
      "email": "budi.kasir@washpoint.test",
      "password": "password123",
      "role": "user"
  }
  ```
- **Response (201 Created):** Salin `access_token` untuk token role user (CRU).

---

### B. Register Akun Role `admin`
- **Method:** `POST`
- **URL:** `{{base_url}}/register`
- **Body (raw JSON):**
  ```json
  {
      "name": "Super Admin",
      "email": "admin.utama@washpoint.test",
      "password": "password123",
      "role": "admin"
  }
  ```
- **Response (201 Created):** Salin `access_token` untuk token role admin (CRUD Penuh).

---

## 2. PENGUJIAN HAK AKSES CRU VS CRUD

- **Role `user`:** Hanya bisa **CRU** (*Create, Read, Update*). Request **DELETE** ditolak **403 Forbidden**.
- **Role `admin`:** Bisa **CRUD** penuh (*Create, Read, Update, Delete*).

---

## 3. CONTOH BODY REQUEST CREATE (TANPA PERLU INPUT ID)

Setiap request menyertakan Header:
`Authorization: Bearer <token>` | `Content-Type: application/json` | `Accept: application/json`

---

### 1. Anggota 1: `service_types` (Jenis Layanan Laundry)

#### [CREATE] Tambah Layanan
- **Method:** `POST`
- **URL:** `{{base_url}}/service-types`
- **Body (Tidak perlu input ID apapun):**
  ```json
  {
      "name": "Cuci Kering Setrika Express",
      "price_per_kg": 10000,
      "estimated_hours": 12,
      "is_active": true
  }
  ```

#### [UPDATE] Perbarui Layanan (ID ditaruh di URL)
- **Method:** `PATCH`
- **URL:** `{{base_url}}/service-types/1`
- **Body:**
  ```json
  {
      "price_per_kg": 11000
  }
  ```

#### [DELETE] Hapus Layanan (ID ditaruh di URL, Admin Only)
- **Method:** `DELETE`
- **URL:** `{{base_url}}/service-types/1`

---

### 2. Anggota 2: `laundry_orders` (Pesanan Laundry)

#### [CREATE] Buat Pesanan Baru
- **Method:** `POST`
- **URL:** `{{base_url}}/laundry-orders`
- **Body (Tidak perlu input ID):**
  ```json
  {
      "customer_name": "Rian Ardiansyah",
      "customer_phone": "081298765432",
      "weight_kg": 4.5,
      "notes": "Pakaian kemeja tolong disetrika rapi"
  }
  ```
  *(Total harga dan jenis layanan otomatis dikalkulasikan jika tidak diisi)*

#### [UPDATE] Ubah Status Pesanan
- **Method:** `PATCH`
- **URL:** `{{base_url}}/laundry-orders/1`
- **Body:** `{"status": "processing"}`

#### [DELETE] Hapus Pesanan (Admin Only)
- **Method:** `DELETE`
- **URL:** `{{base_url}}/laundry-orders/1`

---

### 3. Anggota 3: `payments` (Pembayaran Laundry)

#### [CREATE] Catat Pembayaran
- **Method:** `POST`
- **URL:** `{{base_url}}/payments`
- **Body (Praktis, tanpa perlu input ID):**
  ```json
  {
      "amount": 45000,
      "method": "qris",
      "payment_status": "paid"
  }
  ```
  *(Otomatis terhubung ke user yang sedang login dan pesanan terkait)*

#### [UPDATE] Perbarui Status Pembayaran
- **Method:** `PATCH`
- **URL:** `{{base_url}}/payments/1`
- **Body:** `{"payment_status": "paid"}`

#### [DELETE] Hapus Pembayaran (Admin Only)
- **Method:** `DELETE`
- **URL:** `{{base_url}}/payments/1`

---

### 4. Anggota 4: `pickup_schedules` (Jadwal Penjemputan / Delivery)

#### [CREATE] Buat Jadwal
- **Method:** `POST`
- **URL:** `{{base_url}}/pickup-schedules`
- **Body (Tanpa perlu input ID):**
  ```json
  {
      "type": "delivery",
      "address": "Kost Graha Mahasiswa No. 15, Sleman",
      "status": "scheduled"
  }
  ```

#### [UPDATE] Ubah Status Jadwal
- **Method:** `PATCH`
- **URL:** `{{base_url}}/pickup-schedules/1`
- **Body:** `{"status": "completed"}`

#### [DELETE] Hapus Jadwal (Admin Only)
- **Method:** `DELETE`
- **URL:** `{{base_url}}/pickup-schedules/1`

---

### 5. Anggota 5: `order_status_histories` (Riwayat Status Pesanan)

#### [CREATE] Tambah Catatan Status
- **Method:** `POST`
- **URL:** `{{base_url}}/order-status-histories`
- **Body (Tanpa perlu input ID):**
  ```json
  {
      "status": "ironing",
      "note": "Pakaian sedang dalam proses setrika uap"
  }
  ```

#### [UPDATE] Perbarui Catatan Status
- **Method:** `PATCH`
- **URL:** `{{base_url}}/order-status-histories/1`
- **Body:** `{"note": "Proses setrika selesai, pakaian siap diambil"}`

#### [DELETE] Hapus Log Status (Admin Only)
- **Method:** `DELETE`
- **URL:** `{{base_url}}/order-status-histories/1`
