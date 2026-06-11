# Dokumentasi API - NalaSeva API

Dokumen ini berisi daftar lengkap dan penjelasan detail seluruh endpoint API yang tersedia pada sistem **NalaSeva**, diverifikasi langsung dari source code `routes/api.php` dan masing-masing controller yang aktif di production.

> [!NOTE]
> **Base URL:** `https://<domain>/api/` — Semua endpoint di bawah ini relatif terhadap prefix `/api/`.
>
> **Throttle Limits:** Login/Register/OTP dibatasi `throttle:auth`. Booking antrean & upload bukti bayar dibatasi `5 request/menit` per IP.
>
> **Status Enum Penting:**
> - Antrean (`queues.status`): `booked` → `waiting` → `examining` → `completed` atau `cancelled`
> - Pembayaran (`payments.status`): `pending` → `waiting_verification` → `paid` atau `failed`
>
> **Roles yang tersedia:** `admin`, `doctor`, `patient`, `pharmacist`

---

## 🔑 Autentikasi & Akun (`Auth`)

| No | API | Informasi |
| :--- | :--- | :--- |
| **1** | Nama | Login Pengguna |
| | URL | `/api/auth/login` |
| | Method | `POST` |
| | Type | Public |
| | Authentifikasi | Tidak |
| | Authorisasi | Semua Pengguna (Belum masuk) |
| | Parameters | **Request Body (JSON):**<br>- `email` (string, required, format email)<br>- `password` (string, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Login berhasil",<br>  "data": {<br>    "user": { "id": 1, "name": "Admin", "email": "admin@nalaseva.com", "role": "admin", ... },<br>    "access_token": "1\|abc...",<br>    "token_type": "Bearer"<br>  }<br>}</code></pre><br>`401 Unauthorized`:<br><pre><code>{<br>  "status": "error",<br>  "message": "Email atau password salah"<br>}</code></pre> |
| | Keterangan | Mengotentikasi pengguna menggunakan email dan password untuk mendapatkan token akses Bearer (Laravel Sanctum). |
| --- | --- | --- |
| **2** | Nama | Registrasi Pasien Baru |
| | URL | `/api/auth/register` |
| | Method | `POST` |
| | Type | Public |
| | Authentifikasi | Tidak |
| | Authorisasi | Pasien Mandiri |
| | Parameters | **Request Body (JSON):**<br>- `name` (string, required)<br>- `email` (string, required, unique)<br>- `password` (string, required, min:8)<br>- `national_id` (string, required, unique, 16 digit NIK)<br>- `phone_number` (string, required)<br>- `gender` (string, required, "Laki-laki"/"Perempuan")<br>- `birth_date` (string, required, format YYYY-MM-DD)<br>- `address` (string, required) |
| | Return value | `201 Created`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Registrasi berhasil",<br>  "data": {<br>    "user": { "id": 2, "name": "Budi", "role": "patient", ... },<br>    "access_token": "2\|xyz...",<br>    "token_type": "Bearer"<br>  }<br>}</code></pre> |
| | Keterangan | Mendaftarkan pasien baru secara mandiri. Menggunakan database transaction untuk membuat baris di tabel `users` (dengan role `patient`) dan baris baru di tabel `patients` secara bersamaan. |
| --- | --- | --- |
| **3** | Nama | Minta OTP Reset Password |
| | URL | `/api/auth/forgot-password/otp` |
| | Method | `POST` |
| | Type | Public |
| | Authentifikasi | Tidak |
| | Authorisasi | Semua Pengguna |
| | Parameters | **Request Body (JSON):**<br>- `email` (string, required, format email)<br>- `national_id` (string, required, 16 digit NIK) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Kode OTP verifikasi berhasil dikirim ke email Anda.",<br>  "data": { "otp_code_testing": "123456" }<br>}</code></pre><br>*Catatan: `data` hanya dikembalikan pada environment non-produksi.* |
| | Keterangan | Memverifikasi apakah email dan NIK yang dikirimkan cocok dan terdaftar di database. Jika cocok, sistem akan mengirimkan 6 digit kode OTP ke email. |
| --- | --- | --- |
| **4** | Nama | Lupa Password (Reset via OTP) |
| | URL | `/api/auth/forgot-password` |
| | Method | `POST` |
| | Type | Public |
| | Authentifikasi | Tidak |
| | Authorisasi | Semua Pengguna |
| | Parameters | **Request Body (JSON):**<br>- `email` (string, required, format email)<br>- `national_id` (string, required, 16 digit NIK)<br>- `otp_code` (string, required, 6 digit)<br>- `new_password` (string, required, min:8) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Password berhasil diperbarui, silakan login kembali"<br>}</code></pre> |
| | Keterangan | Memperbarui password lama dengan password baru setelah kode OTP yang dikirimkan melalui email berhasil divalidasi dan cocok dengan database. |
| --- | --- | --- |
| **5** | Nama | Keluar / Logout |
| | URL | `/api/auth/logout` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Logout berhasil"<br>}</code></pre> |
| | Keterangan | Menghapus token akses aktif milik pengguna yang sedang login untuk mengakhiri sesi. |
| --- | --- | --- |
| **6** | Nama | Ambil Profil Aktif |
| | URL | `/api/auth/profile` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Profil berhasil diambil",<br>  "data": { "id": 2, "name": "Budi", "email": "budi@example.com", "role": "patient", "patient": { ... } }<br>}</code></pre> |
| | Keterangan | Mengambil data detail akun milik pengguna yang sedang login beserta relasi profil tambahannya (pasien/dokter). |
| --- | --- | --- |
| **7** | Nama | Perbarui Profil Aktif |
| | URL | `/api/auth/update-profile` |
| | Method | `POST` / `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Request Body (JSON/Multipart):**<br>- `name` (string, optional)<br>- `email` (string, optional)<br>- `phone` (string, optional)<br>- `address` (string, optional)<br>- `national_id` (string, optional, hanya diproses jika data sebelumnya kosong)<br>- `gender` (string, optional, "Laki-laki"/"Perempuan")<br>- `birth_date` (string, optional, format YYYY-MM-DD) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Profil berhasil diperbarui",<br>  "data": { "id": 2, "name": "Budi Edit", ... }<br>}</code></pre> |
| | Keterangan | Memperbarui data profil mandiri pengguna yang sedang login. Perubahan NIK dilindungi aturan ketat agar tidak disalahgunakan. |
| --- | --- | --- |
| **8** | Nama | Perbarui Token FCM |
| | URL | `/api/auth/fcm-token` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Request Body (JSON):**<br>- `fcm_token` (string, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "FCM Token berhasil diperbarui"<br>}</code></pre> |
| | Keterangan | Menyimpan atau memperbarui token Firebase Cloud Messaging milik perangkat pengguna untuk tujuan pengiriman notifikasi push. |

---

## 📊 Dashboard & Profil Puskesmas (`Dashboard & Profile`)

| No | API | Informasi |
| :--- | :--- | :--- |
| **9** | Nama | Ambil Statistik Dashboard |
| | URL | `/api/dashboard-stats` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `doctor` |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Statistik dashboard berhasil diambil",<br>  "data": {<br>    "total_patients": 142,<br>    "total_doctors": 12,<br>    "active_queues_today": 25,<br>    "completed_queues_today": 38,<br>    "cancelled_queues_today": 3,<br>    "polyclinic_stats": [ ... ]<br>  }<br>}</code></pre> |
| | Keterangan | Mengambil rekapitulasi data statistik harian untuk keperluan visualisasi dashboard Admin dan Dokter tanpa N+1 query. |
| --- | --- | --- |
| **10** | Nama | Ambil Profil Puskesmas |
| | URL | `/api/puskesmas-profile` |
| | Method | `GET` |
| | Type | Public |
| | Authentifikasi | Tidak |
| | Authorisasi | Semua Pengguna (Termasuk Publik) |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Detail profil Puskesmas berhasil diambil",<br>  "data": {<br>    "id": 1,<br>    "name": "Puskesmas Nalaseva",<br>    "address": "Jl. Raya Jember No. 45",<br>    "phone": "0331-123456",<br>    "email": "info@nalaseva.go.id",<br>    "latitude": -8.165143,<br>    "longitude": 113.716255,<br>    "logo_url": "..."<br>  }<br>}</code></pre> |
| | Keterangan | Mengambil data detail informasi dasar Puskesmas, kontak, dan koordinat lokasi peta. |
| --- | --- | --- |
| **11** | Nama | Perbarui Profil Puskesmas |
| | URL | `/api/puskesmas-profile` |
| | Method | `PUT` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `name` (string, required)<br>- `address` (string, required)<br>- `phone` (string, required)<br>- `email` (string, required, format email)<br>- `logo_url` (string, optional)<br>- `latitude` (number, optional, between -90 and 90)<br>- `longitude` (number, optional, between -180 and 180) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Profil Puskesmas berhasil diperbarui",<br>  "data": { ... }<br>}</code></pre> |
| | Keterangan | Memperbarui data profil resmi Puskesmas. Hanya boleh diakses oleh administrator. |

---

## 🏥 Manajemen Poliklinik (`Polyclinics`)

| No | API | Informasi |
| :--- | :--- | :--- |
| **12** | Nama | Ambil Semua Poliklinik |
| | URL | `/api/polyclinics` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": [<br>    { "id": 1, "code": "GIG", "name": "Poli Gigi", ... }<br>  ]<br>}</code></pre> |
| | Keterangan | Menampilkan daftar seluruh poliklinik aktif di Puskesmas. |
| --- | --- | --- |
| **13** | Nama | Ambil Detail Poliklinik |
| | URL | `/api/polyclinics/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 1, "code": "GIG", "name": "Poli Gigi", "description": "..." }<br>}</code></pre> |
| | Keterangan | Mengambil rincian detail poliklinik tertentu berdasarkan ID. |
| --- | --- | --- |
| **14** | Nama | Buat Poliklinik Baru |
| | URL | `/api/polyclinics` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `code` (string, required, unique, maks 5 karakter A-Z/0-9)<br>- `name` (string, required)<br>- `description` (string, optional) |
| | Return value | `201 Created`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 2, "code": "UMM", "name": "Poli Umum", ... }<br>}</code></pre> |
| | Keterangan | Menambahkan poliklinik baru ke sistem. |
| --- | --- | --- |
| **15** | Nama | Perbarui Poliklinik |
| | URL | `/api/polyclinics/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `code` (string, optional)<br>- `name` (string, optional)<br>- `description` (string, optional) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Poliklinik berhasil diperbarui",<br>  "data": { ... }<br>}</code></pre> |
| | Keterangan | Memperbarui data poliklinik terdaftar. |
| --- | --- | --- |
| **16** | Nama | Hapus Poliklinik |
| | URL | `/api/polyclinics/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Poliklinik berhasil dihapus"<br>}</code></pre> |
| | Keterangan | Menghapus poliklinik menggunakan mekanisme *soft delete* (masuk tempat sampah). |
| --- | --- | --- |
| **17** | Nama | Pulihkan Poliklinik Terhapus |
| | URL | `/api/polyclinics/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Data poliklinik berhasil dikembalikan"<br>}</code></pre> |
| | Keterangan | Mengaktifkan kembali data poliklinik yang sebelumnya telah di-soft delete. |

---

## 🩺 Manajemen Dokter & Jadwal (`Doctors & Schedules`)

| No | API | Informasi |
| :--- | :--- | :--- |
| **18** | Nama | Ambil Semua Dokter |
| | URL | `/api/doctors` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": [<br>    { "id": 1, "user_id": 3, "polyclinic_id": 1, "specialization": "Gigi", "is_online": true, "user": { ... } }<br>  ]<br>}</code></pre> |
| | Keterangan | Mengambil daftar seluruh dokter yang aktif bekerja beserta relasi akun personalnya. |
| --- | --- | --- |
| **19** | Nama | Ambil Detail Dokter |
| | URL | `/api/doctors/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Detail dokter berhasil diambil",<br>  "data": { "id": 1, "specialization": "Gigi", ... }<br>}</code></pre> |
| | Keterangan | Menampilkan detail profil dan penugasan poliklinik dokter tertentu. |
| --- | --- | --- |
| **20** | Nama | Tambahkan Dokter Baru |
| | URL | `/api/doctors` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `name` (string, required)<br>- `email` (string, required, unique)<br>- `password` (string, required)<br>- `polyclinic_id` (integer, required)<br>- `specialization` (string, required)<br>- `license_number` (string, required, unique SIP)<br>- `national_id` (string, required, unique NIK)<br>- `gender` (string, required, "Laki-laki"/"Perempuan")<br>- `birth_date` (string, required, format YYYY-MM-DD)<br>- `phone` (string, required)<br>- `address` (string, required) |
| | Return value | `201 Created`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 2, "user_id": 5, "license_number": "SIP-1234", "user": { ... } }<br>}</code></pre> |
| | Keterangan | Mendaftarkan dokter baru secara transaksional (membuat user ber-role `doctor` dan entitas `doctors` pendukung). |
| --- | --- | --- |
| **21** | Nama | Perbarui Akun Dokter |
| | URL | `/api/doctors/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `name` (string, optional)<br>- `specialization` (string, optional)<br>- `phone` (string, optional)<br>- `address` (string, optional)<br>- `polyclinic_id` (integer, optional)<br>- `license_number` (string, optional)<br>- `national_id` (string, optional, unique NIK, 16 digit)<br>- `gender` (string, optional, "Laki-laki"/"Perempuan")<br>- `birth_date` (string, optional, format YYYY-MM-DD) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Data dokter berhasil diperbarui"<br>}</code></pre> |
| | Keterangan | Memperbarui profil dan penugasan poliklinik dokter terdaftar. |
| --- | --- | --- |
| **22** | Nama | Hapus Akun Dokter |
| | URL | `/api/doctors/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Dokter dan akun berhasil dihapus"<br>}</code></pre> |
| | Keterangan | Menghapus secara lunak data dokter beserta user login-nya (*soft delete*). |
| --- | --- | --- |
| **23** | Nama | Pulihkan Akun Dokter Terhapus |
| | URL | `/api/doctors/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Data dokter berhasil dikembalikan"<br>}</code></pre> |
| | Keterangan | Memulihkan data dokter dan mengaktifkan kembali akun login dokter terkait. |
| --- | --- | --- |
| **24** | Nama | Ambil Profil Dokter Aktif |
| | URL | `/api/doctors/profile` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `doctor` |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 1, "specialization": "Gigi", "user": { "name": "Dr. Dian", ... } }<br>}</code></pre> |
| | Keterangan | Diambil oleh dokter yang sedang login untuk melihat data penugasan kliniknya sendiri. |
| --- | --- | --- |
| **25** | Nama | Perbarui Status Online Dokter |
| | URL | `/api/doctors/me/status` |
| | Method | `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `doctor` |
| | Parameters | **Request Body (JSON):**<br>- `is_online` (boolean, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Status berhasil diperbarui",<br>  "data": { "id": 1, "is_online": false }<br>}</code></pre> |
| | Keterangan | Mengubah status dokter (online untuk melayani antrean / offline untuk istirahat). Status ini memicu notifikasi real-time. |
| --- | --- | --- |
| **26** | Nama | Ambil Semua Jadwal Dokter |
| | URL | `/api/doctor-schedules` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Query Parameter:**<br>- `polyclinic_id` (integer, optional, filter berdasarkan poliklinik) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": [<br>    { "id": 1, "doctor_id": 1, "day_of_week": "Senin", "start_time": "08:00", "end_time": "12:00" }<br>  ]<br>}</code></pre> |
| | Keterangan | Mengambil data jadwal shift praktik seluruh dokter. |
| --- | --- | --- |
| **27** | Nama | Ambil Detail Jadwal |
| | URL | `/api/doctor-schedules/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 1, "doctor_id": 1, "day_of_week": "Senin", ... }<br>}</code></pre> |
| | Keterangan | Mengambil detail shift dokter tertentu. |
| --- | --- | --- |
| **28** | Nama | Tambah Jadwal Praktik Baru |
| | URL | `/api/doctor-schedules` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `doctor_id` (integer, required)<br>- `day_of_week` (string, required, "Senin"-"Minggu" atau EN enum)<br>- `start_time` (string, required, HH:MM)<br>- `end_time` (string, required, HH:MM) |
| | Return value | `201 Created`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 2, "doctor_id": 1, "day_of_week": "Selasa", ... }<br>}</code></pre> |
| | Keterangan | Menambahkan shift praktik mingguan baru untuk dokter. Memiliki validasi bentrok jadwal agar dokter tidak didaftarkan pada jam yang saling bertubrukan. |
| --- | --- | --- |
| **29** | Nama | Perbarui Jadwal Praktik |
| | URL | `/api/doctor-schedules/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `doctor_id` (integer, optional)<br>- `day_of_week` (string, optional)<br>- `start_time` (string, optional, HH:MM)<br>- `end_time` (string, optional, HH:MM) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Jadwal berhasil diperbarui"<br>}</code></pre> |
| | Keterangan | Memperbarui jam shift dokter yang sudah terdaftar. |
| --- | --- | --- |
| **30** | Nama | Hapus Jadwal Praktik |
| | URL | `/api/doctor-schedules/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Jadwal berhasil dihapus"<br>}</code></pre> |
| | Keterangan | Menghapus shift praktik dokter secara lunak. |
| --- | --- | --- |
| **31** | Nama | Pulihkan Jadwal Terhapus |
| | URL | `/api/doctor-schedules/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Data jadwal berhasil dikembalikan"<br>}</code></pre> |
| | Keterangan | Memulihkan jadwal dokter yang sebelumnya di-soft delete. |

---

## 📅 Hari Libur & Cuti (`Holidays & Leaves`)

| No | API | Informasi |
| :--- | :--- | :--- |
| **32** | Nama | Ambil Daftar Libur Klinik |
| | URL | `/api/clinic-holidays` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": [<br>    { "id": 1, "holiday_date": "2026-06-25", "description": "Hari Raya" }<br>  ]<br>}</code></pre> |
| | Keterangan | Mengambil daftar hari libur operasional puskesmas (sejak hari ini ke depan). |
| --- | --- | --- |
| **33** | Nama | Ambil Detail Hari Libur |
| | URL | `/api/clinic-holidays/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 1, "holiday_date": "2026-06-25", "description": "Hari Raya" }<br>}</code></pre> |
| | Keterangan | Mengambil data spesifik hari libur tertentu. |
| --- | --- | --- |
| **34** | Nama | Tetapkan Tanggal Libur Klinik |
| | URL | `/api/clinic-holidays` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `holiday_date` (string, required, format YYYY-MM-DD)<br>- `description` (string, optional) |
| | Return value | `201 Created`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 2, "holiday_date": "2026-06-25", ... }<br>}</code></pre> |
| | Keterangan | Mendaftarkan hari libur klinik baru. Membatalkan ketersediaan booking antrean pasien pada tanggal tersebut. |
| --- | --- | --- |
| **35** | Nama | Perbarui Hari Libur Klinik |
| | URL | `/api/clinic-holidays/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `holiday_date` (string, optional)<br>- `description` (string, optional) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Hari libur klinik berhasil diperbarui"<br>}</code></pre> |
| | Keterangan | Memperbarui deskripsi atau tanggal libur puskesmas. |
| --- | --- | --- |
| **36** | Nama | Hapus Hari Libur Klinik |
| | URL | `/api/clinic-holidays/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Hari libur klinik berhasil dihapus"<br>}</code></pre> |
| | Keterangan | Menghapus penanggalan hari libur sehingga hari tersebut kembali aktif beroperasi. |
| --- | --- | --- |
| **37** | Nama | Ambil Daftar Cuti Dokter |
| | URL | `/api/doctor-leaves` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Query Parameter:**<br>- `doctor_id` (integer, optional, filter cuti dokter tertentu) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": [<br>    { "id": 1, "doctor_id": 1, "leave_date": "2026-06-01", "reason": "Sakit" }<br>  ]<br>}</code></pre> |
| | Keterangan | Mengambil rencana cuti dokter di puskesmas. |
| --- | --- | --- |
| **38** | Nama | Ambil Detail Cuti Dokter |
| | URL | `/api/doctor-leaves/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 1, "doctor_id": 1, "leave_date": "2026-06-01", "reason": "Sakit" }<br>}</code></pre> |
| | Keterangan | Mengambil rincian pengajuan cuti dokter tertentu. |
| --- | --- | --- |
| **39** | Nama | Ajukan Cuti Dokter |
| | URL | `/api/doctor-leaves` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `doctor_id` (integer, required)<br>- `leave_date` (string, required, format YYYY-MM-DD)<br>- `reason` (string, optional) |
| | Return value | `201 Created`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 2, "doctor_id": 1, "leave_date": "2026-06-01", ... }<br>}</code></pre> |
| | Keterangan | Mendaftarkan hari absen/cuti dokter agar jadwal praktiknya diblokir pada tanggal terkait dan pasien tidak dapat memesan antrean dokter tersebut. |
| --- | --- | --- |
| **40** | Nama | Perbarui Pengajuan Cuti Dokter |
| | URL | `/api/doctor-leaves/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `doctor_id` (integer, optional)<br>- `leave_date` (string, optional)<br>- `reason` (string, optional) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Cuti dokter berhasil diperbarui"<br>}</code></pre> |
| | Keterangan | Memperbarui tanggal atau keterangan cuti dokter. |
| --- | --- | --- |
| **41** | Nama | Hapus/Batalkan Cuti Dokter |
| | URL | `/api/doctor-leaves/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Cuti dokter berhasil dihapus"<br>}</code></pre> |
| | Keterangan | Menghapus pengajuan cuti dokter agar dokter tersebut dinyatakan kembali bertugas melayani pasien. |

---

## 👥 Manajemen Pasien (`Patients`)

| No | API | Informasi |
| :--- | :--- | :--- |
| **42** | Nama | Ambil Daftar Pasien |
| | URL | `/api/patients` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": [<br>    { "id": 1, "user_id": 4, "medical_record_number": "RM-001", "user": { ... } }<br>  ]<br>}</code></pre><br>*Catatan: Jika diakses oleh user ber-role `patient`, data otomatis dibatasi hanya menampilkan data miliknya sendiri (IDOR Protection).* |
| | Keterangan | Mengambil list katalog pasien puskesmas beserta No Rekam Medisnya. |
| --- | --- | --- |
| **43** | Nama | Ambil Detail Pasien |
| | URL | `/api/patients/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 1, "medical_record_number": "RM-001", ... }<br>}</code></pre><br>*Catatan: Dilindungi IDOR Protection ketat (pasien tidak bisa mengintip pasien lain).* |
| | Keterangan | Mengambil detail profil medis pasien. |
| --- | --- | --- |
| **44** | Nama | Daftarkan Pasien Baru (Oleh Admin) |
| | URL | `/api/patients` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `name` (string, required)<br>- `email` (string, required, unique)<br>- `password` (string, required)<br>- `national_id` (string, required, unique NIK)<br>- `gender` (string, required, "Laki-laki"/"Perempuan")<br>- `birth_date` (string, required, format YYYY-MM-DD)<br>- `phone` (string, required)<br>- `address` (string, required) |
| | Return value | `201 Created`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Pasien berhasil didaftarkan",<br>  "data": { "id": 3, "medical_record_number": "RM-003", ... }<br>}</code></pre> |
| | Keterangan | Admin membantu melakukan registrasi manual pasien baru dari loket puskesmas. |
| --- | --- | --- |
| **45** | Nama | Perbarui Profil Pasien |
| | URL | `/api/patients/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `name` / `full_name` (string, optional)<br>- `email` (string, optional, unique)<br>- `phone` / `phone_number` (string, optional)<br>- `gender` (string, optional, "Laki-laki"/"Perempuan")<br>- `birth_date` (string, optional, format YYYY-MM-DD)<br>- `address` (string, optional)<br>- `national_id` (string, optional, 16 digit NIK, unique) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Profil berhasil diperbarui"<br>}</code></pre><br>*Catatan: Dilindungi IDOR Protection.* |
| | Keterangan | Memperbarui profil personal pasien. Pasien biasa hanya bisa mengubah miliknya sendiri. |
| --- | --- | --- |
| **46** | Nama | Hapus Pasien |
| | URL | `/api/patients/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Pasien berhasil dihapus"<br>}</code></pre> |
| | Keterangan | Melakukan soft delete terhadap data pasien dan akun user terkait. |
| --- | --- | --- |
| **47** | Nama | Pulihkan Pasien Terhapus |
| | URL | `/api/patients/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Data pasien berhasil dikembalikan"<br>}</code></pre> |
| | Keterangan | Mengaktifkan kembali data pasien dan user yang telah dihapus secara lunak. |

---

## 🎫 Sistem Antrean & Check-In (`Queues`)

| No | API | Informasi |
| :--- | :--- | :--- |
| **48** | Nama | Ambil Semua Antrean |
| | URL | `/api/queues` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": [<br>    { "id": 1, "queue_number": "A-01", "date": "2026-06-03", "status": "booked", "patient": { ... } }<br>  ]<br>}</code></pre> |
| | Keterangan | Menampilkan riwayat/daftar antrean aktif. |
| --- | --- | --- |
| **49** | Nama | Ambil Detail Antrean |
| | URL | `/api/queues/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 1, "queue_number": "A-01", ... }<br>}</code></pre><br>*Catatan: Dilindungi IDOR Protection.* |
| | Keterangan | Mengambil detail tiket antrean. Pasien dilarang melihat milik orang lain. |
| --- | --- | --- |
| **50** | Nama | Daftar Antrean Online (Booking) |
| | URL | `/api/queues` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi (Terutama Pasien/Admin) |
| | Parameters | **Request Body (JSON):**<br>- `patient_id` (integer, required)<br>- `polyclinic_id` (integer, required)<br>- `doctor_id` (integer, required)<br>- `doctor_schedule_id` (integer, required)<br>- `date` (string, required, format YYYY-MM-DD)<br>- `is_priority` (boolean, optional) |
| | Return value | `201 Created`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Antrian berhasil dibuat",<br>  "data": { "id": 3, "queue_number": "GIG-001", "date": "2026-06-05", ... }<br>}</code></pre> |
| | Keterangan | Melakukan pemesanan tiket antrean. Dilengkapi aturan bisnis ketat (Booking H-7 s/d H-1, kuota penuh, pencegahan antrean ganda pada hari yang sama di poli yang sama, dan lock data saat penomoran antrean). |
| --- | --- | --- |
| **51** | Nama | Perbarui Status Antrean / Panggil Pasien |
| | URL | `/api/queues/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `doctor` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `status` (string, required, "booked"/"waiting"/"examining"/"completed"/"cancelled") |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 1, "status": "examining", "called_time": "2026-06-03 09:00:00", ... }<br>}</code></pre> |
| | Keterangan | Mengubah status antrean pelayanan. Ketika diubah ke `examining`, sistem otomatis mengisi `called_time` dan mengirim notifikasi push ke pasien tujuan. |
| --- | --- | --- |
| **52** | Nama | Batalkan Antrean Pasien |
| | URL | `/api/queues/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Antrian berhasil dibatalkan"<br>}</code></pre> |
| | Keterangan | Membatalkan antrean. Hanya antrean berstatus `booked` (belum checkin) yang dapat dibatalkan secara mandiri oleh pasien. |
| --- | --- | --- |
| **53** | Nama | Pulihkan Antrean Terhapus |
| | URL | `/api/queues/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Data antrian berhasil dikembalikan"<br>}</code></pre> |
| | Keterangan | Memulihkan antrean terhapus. |
| --- | --- | --- |
| **54** | Nama | Verifikasi Check-In Antrean |
| | URL | `/api/queues/{id}/checkin` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON, Opsional):**<br>- `reason` (string, optional, alasan check-in darurat) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Check-in berhasil via QR Scanner",<br>  "data": { "id": 1, "status": "waiting", "check_in_time": "..." }<br>}</code></pre> |
| | Keterangan | Verifikasi kedatangan fisik pasien di klinik pada hari H kunjungan. Status antrean berubah menjadi `waiting` dan antrean masuk ke dalam antrean ruang periksa. |
| --- | --- | --- |
| **55** | Nama | Geser Antrean Pasien (Skip) |
| | URL | `/api/queues/{id}/skip` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Antrean berhasil digeser ke urutan paling belakang",<br>  "data": { ... }<br>}</code></pre> |
| | Keterangan | Menggeser nomor antrean pasien yang terlewat/tidak hadir ke posisi paling akhir pada hari tersebut dan me-reset statusnya kembali ke `booked`. |
| --- | --- | --- |
| **56** | Nama | Panggil Ulang Antrean Pasien (Recall) |
| | URL | `/api/queues/{id}/recall` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Nomor antrean berhasil dipanggil kembali",<br>  "data": { "id": 1, "recall_count": 1, ... }<br>}</code></pre> |
| | Keterangan | Memanggil kembali nomor urut antrean pasien ke pengeras suara dan menambahkan nilai `recall_count`. |

---

## 📝 Rekam Medis & Pemeriksaan (`Examinations`)

| No | API | Informasi |
| :--- | :--- | :--- |
| **57** | Nama | Ambil Rekam Medis / Pemeriksaan |
| | URL | `/api/examinations` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Query Parameter:**<br>- `patient_user_id` (integer, optional, filter berdasarkan pasien) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": [<br>    { "id": 1, "queue_id": 1, "complaint": "Demam", "diagnosis": "Influenza", ... }<br>  ]<br>}</code></pre><br>*Catatan: Dilindungi kepemilikan data ketat (Pasien hanya melihat miliknya, Dokter hanya melihat pasien di polikliniknya).* |
| | Keterangan | Mengambil daftar riwayat hasil pemeriksaan medis pasien. |
| --- | --- | --- |
| **58** | Nama | Ambil Detail Rekam Medis |
| | URL | `/api/examinations/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 1, "complaint": "Demam", ... }<br>}</code></pre><br>*Catatan: Dilindungi otorisasi kepemilikan rekam medis.* |
| | Keterangan | Mengambil rincian detail rekam pemeriksaan pasien tertentu. |
| --- | --- | --- |
| **59** | Nama | Simpan Hasil Pemeriksaan (Rekam Medis) |
| | URL | `/api/examinations` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `doctor` |
| | Parameters | **Request Body (JSON):**<br>- `queue_id` (integer, required)<br>- `complaint` (string, required)<br>- `diagnosis` (string, required)<br>- `treatment` (string, required)<br>- `prescription_items` (array, optional, daftar obat resep)<br>  *Format item:* `{ "medicine_id": 1, "quantity": 10, "instruction": "3x1" }`<br>*Catatan: `doctor_id` tidak perlu dikirim — otomatis diambil dari token login dokter.* |
| | Return value | `201 Created`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Data pemeriksaan dan tagihan pembayaran berhasil disimpan",<br>  "data": { "id": 1, "diagnosis": "Influenza", ... }<br>}</code></pre> |
| | Keterangan | Menyimpan rekam medis dan keluhan periksa pasien. Secara otomatis mengubah status antrean (`queues.status`) terkait menjadi `completed` dan membuat tagihan pembayaran otomatis. Stok obat akan dikurangi saat penyerahan oleh apoteker (bukan saat pemeriksaan). |
| --- | --- | --- |
| **60** | Nama | Perbarui Hasil Pemeriksaan |
| | URL | `/api/examinations/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `doctor` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `queue_id` (integer, optional)<br>- `doctor_id` (integer, optional)<br>- `complaint` (string, optional)<br>- `diagnosis` (string, optional)<br>- `treatment` (string, optional) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Data rekam medis berhasil diperbarui"<br>}</code></pre> |
| | Keterangan | Memperbarui diagnosis atau deskripsi tindakan pengobatan pasien. |
| --- | --- | --- |
| **61** | Nama | Hapus Rekam Medis |
| | URL | `/api/examinations/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `doctor` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Data pemeriksaan berhasil dihapus"<br>}</code></pre> |
| | Keterangan | Melakukan soft delete terhadap data rekam pemeriksaan. |
| --- | --- | --- |
| **62** | Nama | Pulihkan Rekam Medis Terhapus |
| | URL | `/api/examinations/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Data pemeriksaan berhasil dikembalikan"<br>}</code></pre> |
| | Keterangan | Memulihkan kembali data pemeriksaan yang di-soft delete. |

---

## 💳 Manajemen Transaksi & Pembayaran (`Payments`)

| No | API | Informasi |
| :--- | :--- | :--- |
| **63** | Nama | Ambil Semua Transaksi Pembayaran |
| | URL | `/api/payments` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": [<br>    { "id": 1, "transaction_number": "INV-001", "total_amount": "15000.00", "status": "pending" }<br>  ]<br>}</code></pre><br>*Catatan: Pasien hanya dapat melihat transaksinya sendiri.* |
| | Keterangan | Mengambil daftar tagihan pembayaran (pendaftaran awal maupun biaya tebus obat). |
| --- | --- | --- |
| **64** | Nama | Ambil Detail Pembayaran |
| | URL | `/api/payments/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 1, "transaction_number": "INV-001", "total_amount": "15000", ... }<br>}</code></pre><br>*Catatan: Dilindungi IDOR Protection.* |
| | Keterangan | Mengambil data rincian tagihan beserta statusnya (pending, lunas, menunggu verifikasi). |
| --- | --- | --- |
| **65** | Nama | Upload Bukti Pembayaran |
| | URL | `/api/payments/{id}/upload-proof` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi (IDOR Protection: pasien hanya bisa upload untuk tagihan miliknya) |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (Multipart Form-Data):**<br>- `payment_proof` (file, required, gambar **jpeg/png/jpg saja**, maks **2MB**) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Bukti pembayaran berhasil diunggah, menunggu verifikasi admin.",<br>  "data": { "id": 1, "status": "waiting_verification", ... }<br>}</code></pre><br>`400 Bad Request` (jika tagihan sudah lunas):<br><pre><code>{ "status": "error", "message": "Pembayaran sudah lunas." }</code></pre><br>`403 Forbidden` (jika tagihan bukan milik pasien ini) |
| | Keterangan | Pasien mengunggah foto bukti transfer atau QRIS. File **tidak disimpan di filesystem** — di-encode ke **Base64 Data URI** lalu disimpan langsung ke kolom `payment_proof` (longText) di database, karena sistem di-deploy di Railway dengan *ephemeral filesystem*. Status berubah menjadi `waiting_verification`. Rate-limited: `5 request/menit`.
| --- | --- | --- |
| **66** | Nama | Verifikasi Pembayaran Online |
| | URL | `/api/payments/{id}/verify` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `status` (string, required, "paid"/"failed") |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Pembayaran berhasil diverifikasi",<br>  "data": { "id": 1, "status": "paid", "paid_at": "..." }<br>}</code></pre> |
| | Keterangan | Menandai transaksi yang bukti bayar digitalnya sudah sesuai sebagai lunas (`paid`) dan mencatat `paid_at`. |
| --- | --- | --- |
| **67** | Nama | Verifikasi Pembayaran Tunai |
| | URL | `/api/payments/{id}/cash-pay` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Pembayaran tunai berhasil dicatat dan lunas.",<br>  "data": { "id": 1, "status": "paid", "payment_method": "cash", "paid_at": "..." }<br>}</code></pre><br>`400 Bad Request` (jika sudah lunas sebelumnya) |
| | Keterangan | Petugas kasir menandai tagihan dibayar tunai di loket sebagai lunas (`paid`). Otomatis mengisi `payment_method: cash` dan `paid_at`. Mengirim notifikasi push FCM ke pasien bahwa pembayaran lunas dan resep dikirim ke apotek. |
| --- | --- | --- |
| **68 (Baru)** | Nama | Tampilkan Gambar Bukti Pembayaran |
| | URL | `/api/payments/{id}/proof-image` |
| | Method | `GET` |
| | Type | **Public** (tanpa autentikasi) |
| | Authentifikasi | **Tidak** |
| | Authorisasi | Publik (siapa saja yang memiliki ID payment) |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK` — Binary image response dengan header `Content-Type: image/jpeg` (atau `image/png`).<br>`404 Not Found`:<br><pre><code>{ "message": "Bukti pembayaran belum diunggah." }</code></pre> |
| | Keterangan | Endpoint publik yang mengembalikan gambar bukti bayar langsung dari Base64 Data URI yang tersimpan di database. Digunakan oleh Flutter admin untuk menampilkan gambar bukti transfer tanpa autentikasi token. Header CORS `Access-Control-Allow-Origin: *` diaktifkan. |

---

## 💊 Inventaris Obat & Farmasi (`Pharmacy & Medicines`)

> [!NOTE]
> Endpoint farmasi menggunakan **ID Payment** (bukan ID queue/antrean) sebagai parameter untuk operasi penyerahan obat.

| No | API | Informasi |
| :--- | :--- | :--- |
| **68** | Nama | Daftar Antrean Obat Apotek |
| | URL | `/api/pharmacy/queues` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `pharmacist` |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": [<br>    { "id": 5, "queue_number": "POL-003", "patient_name": "Budi", "status_pembayaran": "paid" }<br>  ]<br>}</code></pre> |
| | Keterangan | Apoteker melihat daftar resep pasien yang sudah selesai diperiksa dan siap diracik serta diserahkan. |
| --- | --- | --- |
| **69** | Nama | Penyerahan Obat Pasien |
| | URL | `/api/pharmacy/queues/{id}/dispense` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `pharmacist` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required, **ID Payment** — bukan ID antrean) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Resep obat berhasil diserahkan dan stok obat diperbarui."<br>}</code></pre> |
| | Keterangan | Apoteker menandai penyerahan obat fisik ke pasien (mengisi nilai `dispensed_at` pada transaksi pembayaran terkait). |
| --- | --- | --- |
| **70** | Nama | Ambil Semua Daftar Obat |
| | URL | `/api/medicines` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Query Parameter (Opsional):**<br>- `search` (string, optional, filter nama obat)<br>- `page` (integer, optional, aktifkan paginasi)<br>- `per_page` (integer, optional, jumlah data per halaman, default: 20) |
| | Return value | `200 OK` (tanpa paginasi):<br><pre><code>{<br>  "status": "success",<br>  "data": [<br>    { "id": 1, "name": "Paracetamol", "stock": 150, "unit": "Tablet", "price": "3500.00" }<br>  ]<br>}</code></pre> |
| | Keterangan | Menampilkan seluruh inventaris obat-obatan aktif. Mendukung pencarian nama (`?search=parasetamol`) dan paginasi (`?page=1&per_page=20`). |
| --- | --- | --- |
| **71** | Nama | Ambil Detail Obat |
| | URL | `/api/medicines/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 1, "name": "Paracetamol", ... }<br>}</code></pre> |
| | Keterangan | Mengambil detail info satu jenis obat. |
| --- | --- | --- |
| **72** | Nama | Tambah Obat Baru |
| | URL | `/api/medicines` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `pharmacist` |
| | Parameters | **Request Body (JSON):**<br>- `name` (string, required, unique)<br>- `stock` (integer, required, min:0)<br>- `unit` (string, required, cth: "Tablet"/"Botol")<br>- `price` (number, required, min:0) |
| | Return value | `201 Created`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 2, "name": "Amoxicillin", "stock": 100, ... }<br>}</code></pre> |
| | Keterangan | Mendaftarkan variasi obat baru ke katalog sistem apotek. |
| --- | --- | --- |
| **73** | Nama | Perbarui Data/Stok Obat |
| | URL | `/api/medicines/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `pharmacist` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `name` (string, optional)<br>- `stock` (integer, optional)<br>- `unit` (string, optional)<br>- `price` (number, optional) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Obat berhasil diperbarui"<br>}</code></pre> |
| | Keterangan | Memperbarui info nama, satuan, harga, atau menambah stok obat di apotek. |
| --- | --- | --- |
| **74** | Nama | Hapus Obat |
| | URL | `/api/medicines/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `pharmacist` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Obat berhasil dihapus (soft delete)"<br>}</code></pre> |
| | Keterangan | Soft delete data obat dari daftar aktif apotek. |
| --- | --- | --- |
| **75** | Nama | Pulihkan Obat Terhapus |
| | URL | `/api/medicines/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `pharmacist` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Obat berhasil dikembalikan",<br>  "data": { ... }<br>}</code></pre> |
| | Keterangan | Mengaktifkan kembali obat yang telah dinonaktifkan secara logis. |

---

## ⚙️ Konfigurasi & Pengguna Umum (`Settings & Users`)

| No | API | Informasi |
| :--- | :--- | :--- |
| **76** | Nama | Ambil Pengaturan Puskesmas |
| | URL | `/api/settings` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Pengaturan puskesmas berhasil diambil",<br>  "data": { "registration_fee": "10000", "slot_duration_minutes": "15" }<br>}</code></pre> |
| | Keterangan | Mengambil seluruh setelan parameter operasional puskesmas (seperti tarif pendaftaran dan estimasi waktu slot per pasien). |
| --- | --- | --- |
| **77** | Nama | Perbarui Pengaturan Puskesmas |
| | URL | `/api/settings` |
| | Method | `PUT` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `registration_fee` (number, optional)<br>- `slot_duration_minutes` (integer, optional) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Pengaturan puskesmas berhasil diperbarui",<br>  "data": { ... }<br>}</code></pre> |
| | Keterangan | Mengubah setelan operasional puskesmas yang disimpan secara dinamis di database. |
| --- | --- | --- |
| **78** | Nama | Ambil Semua Pengguna (Admin) |
| | URL | `/api/users` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Query Parameter (Opsional):**<br>- `page` (integer, optional, aktifkan paginasi)<br>- `per_page` (integer, optional, jumlah data per halaman, default: 20) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": [<br>    { "id": 1, "name": "Admin", "email": "admin@example.com", "role": "admin", ... }<br>  ]<br>}</code></pre> |
| | Keterangan | Admin melihat daftar seluruh akun pengguna terdaftar di sistem (semua role). Mendukung paginasi. |
| --- | --- | --- |
| **79** | Nama | Buat Pengguna Baru (Admin) |
| | URL | `/api/users` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `name` (string, required)<br>- `email` (string, required, unique)<br>- `password` (string, required)<br>- `role` (string, required, "doctor"/"patient")<br>- `phone` (string, required)<br>- `address` (string, required)<br>- `national_id` (string, required, unique NIK)<br>- `gender` (string, required)<br>- `birth_date` (string, required, format YYYY-MM-DD) |
| | Return value | `201 Created`:<br><pre><code>{<br>  "status": "success",<br>  "message": "User berhasil ditambahkan",<br>  "data": { ... }<br>}</code></pre> |
| | Keterangan | Membantu menambahkan pengguna (misal dokter/pasien baru) secara manual dari sisi Admin. |
| --- | --- | --- |
| **80** | Nama | Ambil Detail Pengguna (Admin) |
| | URL | `/api/users/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "data": { "id": 1, "name": "Admin", ... }<br>}</code></pre> |
| | Keterangan | Mengambil detail profil dan role pengguna tertentu. |
| --- | --- | --- |
| **81** | Nama | Perbarui Pengguna (Admin) |
| | URL | `/api/users/{id}` |
| | Method | `PUT` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `name` (string, optional)<br>- `email` (string, optional, unique)<br>- `password` (string, optional, nullable)<br>- `phone` (string, optional)<br>- `address` (string, optional)<br>- `national_id` (string, optional, unique, 16 digit NIK)<br>- `gender` (string, optional, "Laki-laki"/"Perempuan")<br>- `birth_date` (string, optional, format YYYY-MM-DD) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Data user berhasil diperbarui"<br>}</code></pre><br>`403 Forbidden`:<br><pre><code>{ "status": "error", "message": "Akses ditolak. Anda tidak memiliki otoritas untuk mengubah data user lain." }</code></pre> |
| | Keterangan | Memperbarui profil akun pengguna. **Catatan kode:** Implementasi saat ini membatasi update hanya jika `id` target sama dengan `id` user yang login — artinya admin hanya bisa mengubah profil dirinya sendiri melalui endpoint ini (bukan akun lain). Untuk mengubah data user lain, gunakan endpoint manajemen dokter atau pasien. |
| --- | --- | --- |
| **82** | Nama | Hapus Pengguna (Admin — Soft Delete) |
| | URL | `/api/users/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "User berhasil dihapus"<br>}</code></pre><br>`403 Forbidden` (jika target adalah admin atau diri sendiri):<br><pre><code>{<br>  "status": "error",<br>  "message": "Akses ditolak. Anda tidak diperkenankan menghapus akun administrator lain."<br>}</code></pre> |
| | Keterangan | Menghapus user secara lunak (*soft delete*). Admin **tidak** diperkenankan menghapus akunnya sendiri maupun akun administrator lain demi menjaga keamanan *audit trail*. |
| --- | --- | --- |
| **83** | Nama | Pulihkan Pengguna Terhapus |
| | URL | `/api/users/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br><pre><code>{<br>  "status": "success",<br>  "message": "Data user berhasil dikembalikan"<br>}</code></pre> |
| | Keterangan | Mengembalikan data user login yang dinonaktifkan/dihapus secara logis sebelumnya. |
