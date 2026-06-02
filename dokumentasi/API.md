# Dokumentasi API - NalaSeva API

Dokumen ini berisi daftar lengkap dan penjelasan detail seluruh endpoint API yang tersedia pada sistem **NalaSeva**, disusun berdasarkan struktur tabel yang telah disepakati.

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
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Login berhasil",\n  "data": {\n    "user": { "id": 1, "name": "Admin", "email": "admin@nalaseva.com", "role": "admin", ... },\n    "access_token": "1|abc...",\n    "token_type": "Bearer"\n  }\n}\n```<br>`401 Unauthorized`:<br>```json\n{\n  "status": "error",\n  "message": "Email atau password salah"\n}\n``` |
| | Keterangan | Mengotentikasi pengguna menggunakan email dan password untuk mendapatkan token akses Bearer (Laravel Sanctum). |
| --- | --- | --- |
| **2** | Nama | Registrasi Pasien Baru |
| | URL | `/api/auth/register` |
| | Method | `POST` |
| | Type | Public |
| | Authentifikasi | Tidak |
| | Authorisasi | Pasien Mandiri |
| | Parameters | **Request Body (JSON):**<br>- `name` (string, required)<br>- `email` (string, required, unique)<br>- `password` (string, required, min:8)<br>- `national_id` (string, required, unique, 16 digit NIK)<br>- `phone_number` (string, required)<br>- `gender` (string, required, "Laki-laki"/"Perempuan")<br>- `birth_date` (string, required, format YYYY-MM-DD)<br>- `address` (string, required) |
| | Return value | `201 Created`:<br>```json\n{\n  "status": "success",\n  "message": "Registrasi berhasil",\n  "data": {\n    "user": { "id": 2, "name": "Budi", "role": "patient", ... },\n    "access_token": "2|xyz...",\n    "token_type": "Bearer"\n  }\n}\n``` |
| | Keterangan | Mendaftarkan pasien baru secara mandiri. Menggunakan database transaction untuk membuat baris di tabel `users` (dengan role `patient`) dan baris baru di tabel `patients` secara bersamaan. |
| --- | --- | --- |
| **3** | Nama | Minta OTP Reset Password |
| | URL | `/api/auth/forgot-password/otp` |
| | Method | `POST` |
| | Type | Public |
| | Authentifikasi | Tidak |
| | Authorisasi | Semua Pengguna |
| | Parameters | **Request Body (JSON):**<br>- `email` (string, required, format email)<br>- `national_id` (string, required, 16 digit NIK) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Kode OTP verifikasi berhasil dikirim ke email Anda.",\n  "data": { "otp_code_testing": "123456" }\n}\n```<br>*Catatan: `data` hanya dikembalikan pada environment non-produksi.* |
| | Keterangan | Memverifikasi apakah email dan NIK yang dikirimkan cocok dan terdaftar di database. Jika cocok, sistem akan mengirimkan 6 digit kode OTP ke email. |
| --- | --- | --- |
| **4** | Nama | Lupa Password (Reset via OTP) |
| | URL | `/api/auth/forgot-password` |
| | Method | `POST` |
| | Type | Public |
| | Authentifikasi | Tidak |
| | Authorisasi | Semua Pengguna |
| | Parameters | **Request Body (JSON):**<br>- `email` (string, required, format email)<br>- `national_id` (string, required, 16 digit NIK)<br>- `otp_code` (string, required, 6 digit)<br>- `new_password` (string, required, min:8) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Password berhasil diperbarui, silakan login kembali"\n}\n``` |
| | Keterangan | Memperbarui password lama dengan password baru setelah kode OTP yang dikirimkan melalui email berhasil divalidasi dan cocok dengan database. |
| --- | --- | --- |
| **5** | Nama | Keluar / Logout |
| | URL | `/api/auth/logout` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Logout berhasil"\n}\n``` |
| | Keterangan | Menghapus token akses aktif milik pengguna yang sedang login untuk mengakhiri sesi. |
| --- | --- | --- |
| **6** | Nama | Ambil Profil Aktif |
| | URL | `/api/auth/profile` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Profil berhasil diambil",\n  "data": { "id": 2, "name": "Budi", "email": "budi@example.com", "role": "patient", "patient": { ... } }\n}\n``` |
| | Keterangan | Mengambil data detail akun milik pengguna yang sedang login beserta relasi profil tambahannya (pasien/dokter). |
| --- | --- | --- |
| **7** | Nama | Perbarui Profil Aktif |
| | URL | `/api/auth/update-profile` |
| | Method | `POST` / `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Request Body (JSON/Multipart):**<br>- `name` (string, optional)<br>- `email` (string, optional)<br>- `phone` (string, optional)<br>- `address` (string, optional)<br>- `national_id` (string, optional, hanya diproses jika data sebelumnya kosong)<br>- `gender` (string, optional, "Laki-laki"/"Perempuan")<br>- `birth_date` (string, optional, format YYYY-MM-DD) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Profil berhasil diperbarui",\n  "data": { "id": 2, "name": "Budi Edit", ... }\n}\n``` |
| | Keterangan | Memperbarui data profil mandiri pengguna yang sedang login. Perubahan NIK dilindungi aturan ketat agar tidak disalahgunakan. |
| --- | --- | --- |
| **8** | Nama | Perbarui Token FCM |
| | URL | `/api/auth/fcm-token` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Request Body (JSON):**<br>- `fcm_token` (string, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "FCM Token berhasil diperbarui"\n}\n``` |
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
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Statistik dashboard berhasil diambil",\n  "data": {\n    "total_patients": 142,\n    "total_doctors": 12,\n    "active_queues_today": 25,\n    "completed_queues_today": 38,\n    "cancelled_queues_today": 3,\n    "polyclinic_stats": [ ... ]\n  }\n}\n``` |
| | Keterangan | Mengambil rekapitulasi data statistik harian untuk keperluan visualisasi dashboard Admin dan Dokter tanpa N+1 query. |
| --- | --- | --- |
| **10** | Nama | Ambil Profil Puskesmas |
| | URL | `/api/puskesmas-profile` |
| | Method | `GET` |
| | Type | Public |
| | Authentifikasi | Tidak |
| | Authorisasi | Semua Pengguna (Termasuk Publik) |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Detail profil Puskesmas berhasil diambil",\n  "data": {\n    "id": 1,\n    "name": "Puskesmas Nalaseva",\n    "address": "Jl. Raya Jember No. 45",\n    "phone": "0331-123456",\n    "email": "info@nalaseva.go.id",\n    "latitude": -8.165143,\n    "longitude": 113.716255,\n    "logo_url": "..."\n  }\n}\n``` |
| | Keterangan | Mengambil data detail informasi dasar Puskesmas, kontak, dan koordinat lokasi peta. |
| --- | --- | --- |
| **11** | Nama | Perbarui Profil Puskesmas |
| | URL | `/api/puskesmas-profile` |
| | Method | `PUT` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `name` (string, required)<br>- `address` (string, required)<br>- `phone` (string, required)<br>- `email` (string, required, format email)<br>- `logo_url` (string, optional)<br>- `latitude` (number, optional, between -90 and 90)<br>- `longitude` (number, optional, between -180 and 180) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Profil Puskesmas berhasil diperbarui",\n  "data": { ... }\n}\n``` |
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
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": [\n    { "id": 1, "code": "GIG", "name": "Poli Gigi", ... }\n  ]\n}\n``` |
| | Keterangan | Menampilkan daftar seluruh poliklinik aktif di Puskesmas. |
| --- | --- | --- |
| **13** | Nama | Ambil Detail Poliklinik |
| | URL | `/api/polyclinics/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 1, "code": "GIG", "name": "Poli Gigi", "description": "..." }\n}\n``` |
| | Keterangan | Mengambil rincian detail poliklinik tertentu berdasarkan ID. |
| --- | --- | --- |
| **14** | Nama | Buat Poliklinik Baru |
| | URL | `/api/polyclinics` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `code` (string, required, unique, maks 5 karakter A-Z/0-9)<br>- `name` (string, required)<br>- `description` (string, optional) |
| | Return value | `201 Created`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 2, "code": "UMM", "name": "Poli Umum", ... }\n}\n``` |
| | Keterangan | Menambahkan poliklinik baru ke sistem. |
| --- | --- | --- |
| **15** | Nama | Perbarui Poliklinik |
| | URL | `/api/polyclinics/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `code` (string, optional)<br>- `name` (string, optional)<br>- `description` (string, optional) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Poliklinik berhasil diperbarui",\n  "data": { ... }\n}\n``` |
| | Keterangan | Memperbarui data poliklinik terdaftar. |
| --- | --- | --- |
| **16** | Nama | Hapus Poliklinik |
| | URL | `/api/polyclinics/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Poliklinik berhasil dihapus secara logis"\n}\n``` |
| | Keterangan | Menghapus poliklinik menggunakan mekanisme *soft delete* (masuk tempat sampah). |
| --- | --- | --- |
| **17** | Nama | Pulihkan Poliklinik Terhapus |
| | URL | `/api/polyclinics/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Poliklinik berhasil dipulihkan"\n}\n``` |
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
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": [\n    { "id": 1, "user_id": 3, "polyclinic_id": 1, "specialization": "Gigi", "is_online": true, "user": { ... } }\n  ]\n}\n``` |
| | Keterangan | Mengambil daftar seluruh dokter yang aktif bekerja beserta relasi akun personalnya. |
| --- | --- | --- |
| **19** | Nama | Ambil Detail Dokter |
| | URL | `/api/doctors/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 1, "specialization": "Gigi", ... }\n}\n``` |
| | Keterangan | Menampilkan detail profil dan penugasan poliklinik dokter tertentu. |
| --- | --- | --- |
| **20** | Nama | Tambahkan Dokter Baru |
| | URL | `/api/doctors` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `name` (string, required)<br>- `email` (string, required, unique)<br>- `password` (string, required)<br>- `polyclinic_id` (integer, required)<br>- `specialization` (string, required)<br>- `license_number` (string, required, unique SIP)<br>- `national_id` (string, required, unique NIK)<br>- `gender` (string, required, "Laki-laki"/"Perempuan")<br>- `birth_date` (string, required, format YYYY-MM-DD)<br>- `phone` (string, required)<br>- `address` (string, required) |
| | Return value | `201 Created`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 2, "user_id": 5, "license_number": "SIP-1234", "user": { ... } }\n}\n``` |
| | Keterangan | Mendaftarkan dokter baru secara transaksional (membuat user ber-role `doctor` dan entitas `doctors` pendukung). |
| --- | --- | --- |
| **21** | Nama | Perbarui Akun Dokter |
| | URL | `/api/doctors/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `name` (string, optional)<br>- `specialization` (string, optional)<br>- `phone` (string, optional)<br>- `address` (string, optional)<br>- `polyclinic_id` (integer, optional) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Data dokter berhasil diperbarui"\n}\n``` |
| | Keterangan | Memperbarui profil dan penugasan poliklinik dokter terdaftar. |
| --- | --- | --- |
| **22** | Nama | Hapus Akun Dokter |
| | URL | `/api/doctors/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Dokter dan akun berhasil dihapus secara logis"\n}\n``` |
| | Keterangan | Menghapus secara lunak data dokter beserta user login-nya (*soft delete*). |
| --- | --- | --- |
| **23** | Nama | Pulihkan Akun Dokter Terhapus |
| | URL | `/api/doctors/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Dokter berhasil dipulihkan"\n}\n``` |
| | Keterangan | Memulihkan data dokter dan mengaktifkan kembali akun login dokter terkait. |
| --- | --- | --- |
| **24** | Nama | Ambil Profil Dokter Aktif |
| | URL | `/api/doctors/profile` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `doctor` |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 1, "specialization": "Gigi", "user": { "name": "Dr. Dian", ... } }\n}\n``` |
| | Keterangan | Diambil oleh dokter yang sedang login untuk melihat data penugasan kliniknya sendiri. |
| --- | --- | --- |
| **25** | Nama | Perbarui Status Online Dokter |
| | URL | `/api/doctors/me/status` |
| | Method | `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `doctor` |
| | Parameters | **Request Body (JSON):**<br>- `is_online` (boolean, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Status berhasil diperbarui",\n  "data": { "id": 1, "is_online": false }\n}\n``` |
| | Keterangan | Mengubah status dokter (online untuk melayani antrean / offline untuk istirahat). Status ini memicu notifikasi real-time. |
| --- | --- | --- |
| **26** | Nama | Ambil Semua Jadwal Dokter |
| | URL | `/api/doctor-schedules` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Query Parameter:**<br>- `polyclinic_id` (integer, optional, filter berdasarkan poliklinik) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": [\n    { "id": 1, "doctor_id": 1, "day_of_week": "Senin", "start_time": "08:00", "end_time": "12:00" }\n  ]\n}\n``` |
| | Keterangan | Mengambil data jadwal shift praktik seluruh dokter. |
| --- | --- | --- |
| **27** | Nama | Ambil Detail Jadwal |
| | URL | `/api/doctor-schedules/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 1, "doctor_id": 1, "day_of_week": "Senin", ... }\n}\n``` |
| | Keterangan | Mengambil detail shift dokter tertentu. |
| --- | --- | --- |
| **28** | Nama | Tambah Jadwal Praktik Baru |
| | URL | `/api/doctor-schedules` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `doctor_id` (integer, required)<br>- `day_of_week` (string, required, "Senin"-"Minggu" atau EN enum)<br>- `start_time` (string, required, HH:MM)<br>- `end_time` (string, required, HH:MM) |
| | Return value | `201 Created`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 2, "doctor_id": 1, "day_of_week": "Selasa", ... }\n}\n``` |
| | Keterangan | Menambahkan shift praktik mingguan baru untuk dokter. Memiliki validasi bentrok jadwal agar dokter tidak didaftarkan pada jam yang saling bertubrukan. |
| --- | --- | --- |
| **29** | Nama | Perbarui Jadwal Praktik |
| | URL | `/api/doctor-schedules/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `start_time` (string, optional)<br>- `end_time` (string, optional) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Jadwal berhasil diperbarui"\n}\n``` |
| | Keterangan | Memperbarui jam shift dokter yang sudah terdaftar. |
| --- | --- | --- |
| **30** | Nama | Hapus Jadwal Praktik |
| | URL | `/api/doctor-schedules/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Jadwal berhasil dihapus secara logis"\n}\n``` |
| | Keterangan | Menghapus shift praktik dokter secara lunak. |
| --- | --- | --- |
| **31** | Nama | Pulihkan Jadwal Terhapus |
| | URL | `/api/doctor-schedules/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Jadwal berhasil dipulihkan"\n}\n``` |
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
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": [\n    { "id": 1, "holiday_date": "2026-06-25", "description": "Hari Raya" }\n  ]\n}\n``` |
| | Keterangan | Mengambil daftar hari libur operasional puskesmas (sejak hari ini ke depan). |
| --- | --- | --- |
| **33** | Nama | Ambil Detail Hari Libur |
| | URL | `/api/clinic-holidays/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 1, "holiday_date": "2026-06-25", ... }\n}\n``` |
| | Keterangan | Mengambil data spesifik hari libur tertentu. |
| --- | --- | --- |
| **34** | Nama | Tetapkan Tanggal Libur Klinik |
| | URL | `/api/clinic-holidays` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `holiday_date` (string, required, format YYYY-MM-DD)<br>- `description` (string, optional) |
| | Return value | `201 Created`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 2, "holiday_date": "2026-06-25", ... }\n}\n``` |
| | Keterangan | Mendaftarkan hari libur klinik baru. Membatalkan ketersediaan booking antrean pasien pada tanggal tersebut. |
| --- | --- | --- |
| **35** | Nama | Perbarui Hari Libur Klinik |
| | URL | `/api/clinic-holidays/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `holiday_date` (string, optional)<br>- `description` (string, optional) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Hari libur klinik berhasil diperbarui"\n}\n``` |
| | Keterangan | Memperbarui deskripsi atau tanggal libur puskesmas. |
| --- | --- | --- |
| **36** | Nama | Hapus Hari Libur Klinik |
| | URL | `/api/clinic-holidays/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Hari libur klinik berhasil dihapus"\n}\n``` |
| | Keterangan | Menghapus penanggalan hari libur sehingga hari tersebut kembali aktif beroperasi. |
| --- | --- | --- |
| **37** | Nama | Ambil Daftar Cuti Dokter |
| | URL | `/api/doctor-leaves` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Query Parameter:**<br>- `doctor_id` (integer, optional, filter cuti dokter tertentu) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": [\n    { "id": 1, "doctor_id": 1, "leave_date": "2026-06-01", "reason": "Sakit" }\n  ]\n}\n``` |
| | Keterangan | Mengambil rencana cuti dokter di puskesmas. |
| --- | --- | --- |
| **38** | Nama | Ambil Detail Cuti Dokter |
| | URL | `/api/doctor-leaves/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 1, "doctor_id": 1, "leave_date": "2026-06-01", ... }\n}\n``` |
| | Keterangan | Mengambil rincian pengajuan cuti dokter tertentu. |
| --- | --- | --- |
| **39** | Nama | Ajukan Cuti Dokter |
| | URL | `/api/doctor-leaves` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `doctor_id` (integer, required)<br>- `leave_date` (string, required, format YYYY-MM-DD)<br>- `reason` (string, optional) |
| | Return value | `201 Created`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 2, "doctor_id": 1, "leave_date": "2026-06-01", ... }\n}\n``` |
| | Keterangan | Mendaftarkan hari absen/cuti dokter agar jadwal praktiknya diblokir pada tanggal terkait dan pasien tidak dapat memesan antrean dokter tersebut. |
| --- | --- | --- |
| **40** | Nama | Perbarui Pengajuan Cuti Dokter |
| | URL | `/api/doctor-leaves/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `leave_date` (string, optional)<br>- `reason` (string, optional) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Cuti dokter berhasil diperbarui"\n}\n``` |
| | Keterangan | Memperbarui tanggal atau keterangan cuti dokter. |
| --- | --- | --- |
| **41** | Nama | Hapus/Batalkan Cuti Dokter |
| | URL | `/api/doctor-leaves/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Cuti dokter berhasil dihapus"\n}\n``` |
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
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": [\n    { "id": 1, "user_id": 4, "medical_record_number": "RM-001", "user": { ... } }\n  ]\n}\n```<br>*Catatan: Jika diakses oleh user ber-role `patient`, data otomatis dibatasi hanya menampilkan data miliknya sendiri (IDOR Protection).* |
| | Keterangan | Mengambil list katalog pasien puskesmas beserta No Rekam Medisnya. |
| --- | --- | --- |
| **43** | Nama | Ambil Detail Pasien |
| | URL | `/api/patients/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 1, "medical_record_number": "RM-001", ... }\n}\n```<br>*Catatan: Dilindungi IDOR Protection ketat (pasien tidak bisa mengintip pasien lain).* |
| | Keterangan | Mengambil detail profil medis pasien. |
| --- | --- | --- |
| **44** | Nama | Daftarkan Pasien Baru (Oleh Admin) |
| | URL | `/api/patients` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `name` (string, required)<br>- `email` (string, required, unique)<br>- `password` (string, required)<br>- `national_id` (string, required, unique NIK)<br>- `gender` (string, required, "Laki-laki"/"Perempuan")<br>- `birth_date` (string, required, format YYYY-MM-DD)<br>- `phone` (string, required)<br>- `address` (string, required) |
| | Return value | `201 Created`:<br>```json\n{\n  "status": "success",\n  "message": "Pasien berhasil didaftarkan",\n  "data": { "id": 3, "medical_record_number": "RM-003", ... }\n}\n``` |
| | Keterangan | Admin membantu melakukan registrasi manual pasien baru dari loket puskesmas. |
| --- | --- | --- |
| **45** | Nama | Perbarui Profil Pasien |
| | URL | `/api/patients/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `name` (string, optional)<br>- `phone` (string, optional)<br>- `address` (string, optional) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Profil berhasil diperbarui"\n}\n```<br>*Catatan: Dilindungi IDOR Protection.* |
| | Keterangan | Memperbarui profil personal pasien. Pasien biasa hanya bisa mengubah miliknya sendiri. |
| --- | --- | --- |
| **46** | Nama | Hapus Pasien |
| | URL | `/api/patients/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Pasien berhasil dihapus secara logis"\n}\n``` |
| | Keterangan | Melakukan soft delete terhadap data pasien dan akun user terkait. |
| --- | --- | --- |
| **47** | Nama | Pulihkan Pasien Terhapus |
| | URL | `/api/patients/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Pasien berhasil dipulihkan"\n}\n``` |
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
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": [\n    { "id": 1, "queue_number": "A-01", "date": "2026-06-03", "status": "booked", "patient": { ... } }\n  ]\n}\n``` |
| | Keterangan | Menampilkan riwayat/daftar antrean aktif. |
| --- | --- | --- |
| **49** | Nama | Ambil Detail Antrean |
| | URL | `/api/queues/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 1, "queue_number": "A-01", ... }\n}\n```<br>*Catatan: Dilindungi IDOR Protection.* |
| | Keterangan | Mengambil detail tiket antrean. Pasien dilarang melihat milik orang lain. |
| --- | --- | --- |
| **50** | Nama | Daftar Antrean Online (Booking) |
| | URL | `/api/queues` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi (Terutama Pasien/Admin) |
| | Parameters | **Request Body (JSON):**<br>- `patient_id` (integer, required)<br>- `polyclinic_id` (integer, required)<br>- `doctor_id` (integer, required)<br>- `date` (string, required, format YYYY-MM-DD)<br>- `is_priority` (boolean, optional) |
| | Return value | `201 Created`:<br>```json\n{\n  "status": "success",\n  "message": "Antrian berhasil dibuat",\n  "data": { "id": 3, "queue_number": "GIG-001", "date": "2026-06-05", ... }\n}\n``` |
| | Keterangan | Melakukan pemesanan tiket antrean. Dilengkapi aturan bisnis ketat (Booking H-7 s/d H-1, kuota penuh, pencegahan antrean ganda pada hari yang sama di poli yang sama, dan lock data saat penomoran antrean). |
| --- | --- | --- |
| **51** | Nama | Perbarui Status Antrean / Panggil Pasien |
| | URL | `/api/queues/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `doctor` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `status` (string, required, "booked"/"waiting"/"examining"/"completed"/"cancelled") |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 1, "status": "examining", "called_time": "2026-06-03 09:00:00", ... }\n}\n``` |
| | Keterangan | Mengubah status antrean pelayanan. Ketika diubah ke `examining`, sistem otomatis mengisi `called_time` dan mengirim notifikasi push ke pasien tujuan. |
| --- | --- | --- |
| **52** | Nama | Batalkan Antrean Pasien |
| | URL | `/api/queues/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Antrian berhasil dibatalkan"\n}\n``` |
| | Keterangan | Membatalkan antrean. Hanya antrean berstatus `booked` (belum checkin) yang dapat dibatalkan secara mandiri oleh pasien. |
| --- | --- | --- |
| **53** | Nama | Pulihkan Antrean Terhapus |
| | URL | `/api/queues/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Antrean berhasil dipulihkan"\n}\n``` |
| | Keterangan | Memulihkan antrean terhapus. |
| --- | --- | --- |
| **54** | Nama | Verifikasi Check-In Antrean |
| | URL | `/api/queues/{id}/checkin` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Check-in berhasil via QR Scanner",\n  "data": { "id": 1, "status": "waiting", "check_in_time": "..." }\n}\n``` |
| | Keterangan | Verifikasi kedatangan fisik pasien di klinik pada hari H kunjungan. Status antrean berubah menjadi `waiting` dan antrean masuk ke dalam antrean ruang periksa. |
| --- | --- | --- |
| **55** | Nama | Geser Antrean Pasien (Skip) |
| | URL | `/api/queues/{id}/skip` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `doctor` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Antrean berhasil digeser ke urutan paling belakang",\n  "data": { ... }\n}\n``` |
| | Keterangan | Menggeser nomor antrean pasien yang terlewat/tidak hadir ke posisi paling akhir pada hari tersebut dan me-reset statusnya kembali ke `booked`. |
| --- | --- | --- |
| **56** | Nama | Panggil Ulang Antrean Pasien (Recall) |
| | URL | `/api/queues/{id}/recall` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `doctor` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Nomor antrean berhasil dipanggil kembali",\n  "data": { "id": 1, "recall_count": 1, ... }\n}\n``` |
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
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": [\n    { "id": 1, "queue_id": 1, "complaint": "Demam", "diagnosis": "Influenza", ... }\n  ]\n}\n```<br>*Catatan: Dilindungi kepemilikan data ketat (Pasien hanya melihat miliknya, Dokter hanya melihat pasien di polikliniknya).* |
| | Keterangan | Mengambil daftar riwayat hasil pemeriksaan medis pasien. |
| --- | --- | --- |
| **58** | Nama | Ambil Detail Rekam Medis |
| | URL | `/api/examinations/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 1, "complaint": "Demam", ... }\n}\n```<br>*Catatan: Dilindungi otorisasi kepemilikan rekam medis.* |
| | Keterangan | Mengambil rincian detail rekam pemeriksaan pasien tertentu. |
| --- | --- | --- |
| **59** | Nama | Simpan Hasil Pemeriksaan (Rekam Medis) |
| | URL | `/api/examinations` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `doctor` |
| | Parameters | **Request Body (JSON):**<br>- `queue_id` (integer, required)<br>- `doctor_id` (integer, required)<br>- `complaint` (string, required)<br>- `diagnosis` (string, required)<br>- `treatment` (string, required)<br>- `medicines` (array, optional, daftar obat resep)<br>  *Format item obat:* `{ "medicine_id": 1, "quantity": 10, "instruction": "3x1" }` |
| | Return value | `201 Created`:<br>```json\n{\n  "status": "success",\n  "message": "Data pemeriksaan berhasil disimpan",\n  "data": { "id": 1, "diagnosis": "Influenza", ... }\n}\n``` |
| | Keterangan | Menyimpan rekam medis dan keluhan periksa pasien. Secara otomatis mengubah status antrean (`queues.status`) terkait menjadi `completed` dan memotong stok obat jika terdapat obat yang diresepkan. |
| --- | --- | --- |
| **60** | Nama | Perbarui Hasil Pemeriksaan |
| | URL | `/api/examinations/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `doctor` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `diagnosis` (string, optional)<br>- `treatment` (string, optional) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Data rekam medis berhasil diperbarui"\n}\n``` |
| | Keterangan | Memperbarui diagnosis atau deskripsi tindakan pengobatan pasien. |
| --- | --- | --- |
| **61** | Nama | Hapus Rekam Medis |
| | URL | `/api/examinations/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `doctor` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Data pemeriksaan berhasil dihapus secara logis"\n}\n``` |
| | Keterangan | Melakukan soft delete terhadap data rekam pemeriksaan. |
| --- | --- | --- |
| **62** | Nama | Pulihkan Rekam Medis Terhapus |
| | URL | `/api/examinations/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Data rekam medis berhasil dikembalikan"\n}\n``` |
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
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": [\n    { "id": 1, "transaction_number": "INV-001", "total_amount": "15000.00", "status": "pending" }\n  ]\n}\n```<br>*Catatan: Pasien hanya dapat melihat transaksinya sendiri.* |
| | Keterangan | Mengambil daftar tagihan pembayaran (pendaftaran awal maupun biaya tebus obat). |
| --- | --- | --- |
| **64** | Nama | Ambil Detail Pembayaran |
| | URL | `/api/payments/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 1, "transaction_number": "INV-001", "total_amount": "15000", ... }\n}\n```<br>*Catatan: Dilindungi IDOR Protection.* |
| | Keterangan | Mengambil data rincian tagihan beserta statusnya (pending, lunas, menunggu verifikasi). |
| --- | --- | --- |
| **65** | Nama | Upload Bukti Pembayaran |
| | URL | `/api/payments/{id}/upload-proof` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `patient` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (Multipart Form-Data):**<br>- `payment_proof` (file, required, gambar jpg/png/pdf maks 2MB) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Bukti pembayaran berhasil diunggah",\n  "data": { "id": 1, "status": "waiting_verification", ... }\n}\n``` |
| | Keterangan | Digunakan oleh pasien umum untuk mengunggah foto struk transfer atau bukti bayar QRIS. Mengubah status transaksi menjadi `waiting_verification`. |
| --- | --- | --- |
| **66** | Nama | Verifikasi Pembayaran Online |
| | URL | `/api/payments/{id}/verify` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Pembayaran berhasil diverifikasi",\n  "data": { "id": 1, "status": "paid", "paid_at": "..." }\n}\n``` |
| | Keterangan | Menandai transaksi yang bukti bayar digitalnya sudah sesuai sebagai lunas (`paid`) dan mencatat `paid_at`. |
| --- | --- | --- |
| **67** | Nama | Verifikasi Pembayaran Tunai |
| | URL | `/api/payments/{id}/cash-pay` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Pembayaran tunai berhasil diverifikasi",\n  "data": { "id": 1, "status": "paid", "payment_method": "cash" }\n}\n``` |
| | Keterangan | Petugas klinik menandai tagihan dibayar tunai di kasir sebagai lunas (`paid`). |

---

## 💊 Inventaris Obat & Farmasi (`Pharmacy & Medicines`)

| No | API | Informasi |
| :--- | :--- | :--- |
| **68** | Nama | Daftar Antrean Obat Apotek |
| | URL | `/api/pharmacy/queues` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `pharmacist` |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": [\n    { "id": 5, "queue_number": "POL-003", "patient_name": "Budi", "status_pembayaran": "paid" }\n  ]\n}\n``` |
| | Keterangan | Apoteker melihat daftar resep pasien yang sudah selesai diperiksa dan siap diracik serta diserahkan. |
| --- | --- | --- |
| **69** | Nama | Penyerahan Obat Pasien |
| | URL | `/api/pharmacy/queues/{id}/dispense` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `pharmacist` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required, ID antrean) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Obat berhasil diserahkan kepada pasien"\n}\n``` |
| | Keterangan | Apoteker menandai penyerahan obat fisik ke pasien (mengisi nilai `dispensed_at` pada transaksi pembayaran terkait). |
| --- | --- | --- |
| **70** | Nama | Ambil Semua Daftar Obat |
| | URL | `/api/medicines` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": [\n    { "id": 1, "name": "Paracetamol", "stock": 150, "unit": "Tablet", "price": "3500.00" }\n  ]\n}\n``` |
| | Keterangan | Menampilkan seluruh inventaris obat-obatan aktif. |
| --- | --- | --- |
| **71** | Nama | Ambil Detail Obat |
| | URL | `/api/medicines/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | Semua Pengguna Terautentikasi |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 1, "name": "Paracetamol", ... }\n}\n``` |
| | Keterangan | Mengambil detail info satu jenis obat. |
| --- | --- | --- |
| **72** | Nama | Tambah Obat Baru |
| | URL | `/api/medicines` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `pharmacist` |
| | Parameters | **Request Body (JSON):**<br>- `name` (string, required, unique)<br>- `stock` (integer, required, min:0)<br>- `unit` (string, required, cth: "Tablet"/"Botol")<br>- `price` (number, required, min:0) |
| | Return value | `201 Created`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 2, "name": "Amoxicillin", "stock": 100, ... }\n}\n``` |
| | Keterangan | Mendaftarkan variasi obat baru ke katalog sistem apotek. |
| --- | --- | --- |
| **73** | Nama | Perbarui Data/Stok Obat |
| | URL | `/api/medicines/{id}` |
| | Method | `PUT` / `PATCH` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `pharmacist` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `name` (string, optional)<br>- `stock` (integer, optional)<br>- `unit` (string, optional)<br>- `price` (number, optional) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Obat berhasil diperbarui"\n}\n``` |
| | Keterangan | Memperbarui info nama, satuan, harga, atau menambah stok obat di apotek. |
| --- | --- | --- |
| **74** | Nama | Hapus Obat |
| | URL | `/api/medicines/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `pharmacist` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Obat berhasil dihapus secara logis"\n}\n``` |
| | Keterangan | Soft delete data obat dari daftar aktif apotek. |
| --- | --- | --- |
| **75** | Nama | Pulihkan Obat Terhapus |
| | URL | `/api/medicines/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin`, `pharmacist` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Obat berhasil diaktifkan kembali"\n}\n``` |
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
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Pengaturan puskesmas berhasil diambil",\n  "data": { "registration_fee": "10000", "slot_duration_minutes": "15" }\n}\n``` |
| | Keterangan | Mengambil seluruh setelan parameter operasional puskesmas (seperti tarif pendaftaran dan estimasi waktu slot per pasien). |
| --- | --- | --- |
| **77** | Nama | Perbarui Pengaturan Puskesmas |
| | URL | `/api/settings` |
| | Method | `PUT` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `registration_fee` (number, optional)<br>- `slot_duration_minutes` (integer, optional) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Pengaturan puskesmas berhasil diperbarui",\n  "data": { ... }\n}\n``` |
| | Keterangan | Mengubah setelan operasional puskesmas yang disimpan secara dinamis di database. |
| --- | --- | --- |
| **78** | Nama | Ambil Semua Pengguna (Admin) |
| | URL | `/api/users` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | Tidak ada |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": [\n    { "id": 1, "name": "Admin", "email": "admin@example.com", "role": "admin", ... }\n  ]\n}\n``` |
| | Keterangan | Admin mengintip daftar seluruh akun pengguna terdaftar di sistem. |
| --- | --- | --- |
| **79** | Nama | Buat Pengguna Baru (Admin) |
| | URL | `/api/users` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Request Body (JSON):**<br>- `name` (string, required)<br>- `email` (string, required, unique)<br>- `password` (string, required)<br>- `role` (string, required, "doctor"/"patient"/"pharmacist")<br>- `phone` (string, required)<br>- `address` (string, required)<br>- `national_id` (string, required, unique NIK)<br>- `gender` (string, required)<br>- `birth_date` (string, required, format YYYY-MM-DD) |
| | Return value | `201 Created`:<br>```json\n{\n  "status": "success",\n  "message": "User berhasil ditambahkan",\n  "data": { ... }\n}\n``` |
| | Keterangan | Membantu menambahkan pengguna (misal dokter/pasien baru) secara manual dari sisi Admin. |
| --- | --- | --- |
| **80** | Nama | Ambil Detail Pengguna (Admin) |
| | URL | `/api/users/{id}` |
| | Method | `GET` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "data": { "id": 1, "name": "Admin", ... }\n}\n``` |
| | Keterangan | Mengambil detail profil dan role pengguna tertentu. |
| --- | --- | --- |
| **81** | Nama | Perbarui Pengguna (Admin) |
| | URL | `/api/users/{id}` |
| | Method | `PUT` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required)<br>**Request Body (JSON):**<br>- `name` (string, optional)<br>- `email` (string, optional)<br>- `phone` (string, optional) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Data user berhasil diperbarui"\n}\n``` |
| | Keterangan | Admin mengedit profil user tertentu. |
| --- | --- | --- |
| **82** | Nama | Hapus Pengguna (Admin — Kebijakan Baru: Dilarang) |
| | URL | `/api/users/{id}` |
| | Method | `DELETE` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `403 Forbidden`:<br>```json\n{\n  "status": "error",\n  "message": "Akses ditolak. Kebijakan baru melarang penghapusan user oleh Admin."\n}\n``` |
| | Keterangan | Percobaan menghapus user secara langsung oleh Admin akan ditolak sistem untuk mengamankan riwayat audit (*audit trail*). |
| --- | --- | --- |
| **83** | Nama | Pulihkan Pengguna Terhapus |
| | URL | `/api/users/{id}/restore` |
| | Method | `POST` |
| | Type | Protected |
| | Authentifikasi | Ya (Bearer Token) |
| | Authorisasi | `admin` |
| | Parameters | **Path Parameter:**<br>- `id` (integer, required) |
| | Return value | `200 OK`:<br>```json\n{\n  "status": "success",\n  "message": "Data user berhasil dikembalikan"\n}\n``` |
| | Keterangan | Mengembalikan data user login yang dinonaktifkan/dihapus secara logis sebelumnya. |
