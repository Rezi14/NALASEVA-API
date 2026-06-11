# Pembagian Tugas Kelompok (Jobdesk) - Projek NalaSeva

Pembagian ini menggunakan **Model Vertikal (Vertical Slicing)** — setiap anggota mengerjakan Backend (Laravel) dan Frontend (Flutter) untuk modulnya masing-masing.

---

## ⚖️ Analisis Keseimbangan Beban Kerja

Sebelum pembagian, semua file diinventarisasi dan dibobot berdasarkan **jumlah + ukuran/kompleksitas** file:

| Anggota | Controllers | Services | Models | Form Requests | Migrations | Seeders | Flutter UI |
| :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **A** | 3 | 3 | 3 | 3 | 4 | 5 | 11 |
| **B** | 5 | 1 | 5 | 10 | 5 | 3 | 10 |
| **C** | 2 | 1 | 2 | 4 | 2 | 0 | 7 |
| **D** | 6 | 0 | 4 | 2 | 4 | 1 | 13 |

> [!NOTE]
> Migrations dan FormRequests (Request Validation) turut dibagi secara merata karena keduanya adalah bagian nyata dari pekerjaan backend Laravel.

---

## 🚦 Sprint 0 — Setup Fondasi Bersama (Sebelum Mulai Coding Fitur)

> [!IMPORTANT]
> Kerjakan tabel di bawah ini **terlebih dahulu secara bersama-sama** sebelum masing-masing anggota mulai mengerjakan fitur modul. Ini memastikan tidak ada yang *stuck* menunggu.

| Tugas | PIC | File |
| :--- | :--- | :--- |
| Setup HTTP client Flutter (base URL, auth header, error handling) | **A** | [api_client.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/core/api/api_client.dart) |
| Setup router & navigasi Flutter (semua named routes didaftarkan di sini) | **C** | [app_router.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/core/router/app_router.dart) |
| Jalankan semua migration & seeder, pastikan DB lokal tim berjalan | **B** | [DatabaseSeeder.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/database/seeders/DatabaseSeeder.php) |
| Bagikan file Postman Collection dari API.md ke seluruh anggota | **A** | [API.md](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/dokumentasi/API.md) |

---

## 📊 Ringkasan File per Anggota

| Anggota | Modul | Controllers | Services | Models | Request Forms | Migrations | Seeders | Flutter UI | Flutter Models |
| :---: | :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **A** | Auth, User Management & Push Notification | 3 | 3 | 3 | 3 | 4 | 5 | 6 | 3 |
| **B** | Master Data (Poli & Dokter) | 5 | 1 | 5 | 10 | 5 | 3 | 5 | 3 |
| **C** | Antrean & Pasien | 2 | 1 | 2 | 4 | 2 | 0 | 7 | 2 |
| **D** | Pemeriksaan, Bayar, Farmasi & Dashboard | 6 | 0 | 4 | 2 | 4 | 1 | 14 | 5 |

---

## 📄 Rincian File Kode per Anggota

---

### 🛡️ Anggota A — Auth, User Management & Push Notification
**Peran Tambahan: 🔑 Git Coordinator** (Me-*merge* branch semua anggota ke `main` & menjaga `routes/api.php` bebas konflik)

Mengerjakan fondasi keamanan: login, registrasi, reset password via OTP, manajemen profil user, data puskesmas, konfigurasi setting, dan push notification.

#### Backend (Laravel)
*   **Controllers:**
    *   [AuthController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/AuthController.php) — Login, register pasien mandiri, logout, profil aktif, update profil, request OTP, reset password via OTP, update FCM token.
    *   [UserController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/UserController.php) — CRUD lengkap akun user (index, show, store, update, destroy, restore). Dilengkapi perlindungan: admin tidak bisa menghapus dirinya sendiri maupun sesama admin.
    *   [PuskesmasProfileController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/PuskesmasProfileController.php) — Ambil (publik) & perbarui (admin) data klinik (alamat, logo, koordinat GPS).
*   **Services:**
    *   [AuthService.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Services/AuthService.php) — Logic token Sanctum, validasi OTP, registrasi transaksional (user + patient).
    *   [FirebaseNotificationService.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Services/FirebaseNotificationService.php) — Kirim push notification server → perangkat via FCM (dipakai juga oleh QueueService, PaymentController, dan PharmacyController).
    *   [PuskesmasProfileService.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Services/PuskesmasProfileService.php) — Logic update profil klinik.
*   **Models:**
    *   [User.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/User.php)
    *   [PuskesmasProfile.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/PuskesmasProfile.php)
    *   [Setting.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/Setting.php)
*   **Form Requests (Validasi):**
    *   [StoreUserRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/StoreUserRequest.php)
    *   [UpdateUserRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/UpdateUserRequest.php)
    *   [UpdatePuskesmasProfileRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/UpdatePuskesmasProfileRequest.php)
*   **Migrations:**
    *   `2026_04_21_123101_create_users_table.php`
    *   `2026_04_21_171135_create_personal_access_tokens_table.php`
    *   `2026_05_30_000001_create_password_reset_otps_table.php`
    *   `2026_06_01_000004_create_settings_table.php`
*   **Seeders:**
    *   [DatabaseSeeder.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/database/seeders/DatabaseSeeder.php)
    *   [AdminSeeder.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/database/seeders/AdminSeeder.php)
    *   [PharmacistSeeder.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/database/seeders/PharmacistSeeder.php)
    *   [PuskesmasProfileSeeder.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/database/seeders/PuskesmasProfileSeeder.php)
    *   [SettingSeeder.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/database/seeders/SettingSeeder.php)
*   **Routes (dikelola A sebagai Git Coordinator):**
    *   [api.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/routes/api.php)

#### Frontend (Flutter)
*   **Core (Sprint 0):**
    *   [api_client.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/core/api/api_client.dart)
    *   [firebase_messaging_service.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/core/services/firebase_messaging_service.dart)
    *   [main.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/main.dart)
*   **Auth UI (`lib/features/auth/ui/`):**
    *   [login_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/auth/ui/login_screen.dart)
    *   [register_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/auth/ui/register_screen.dart)
    *   [forgot_password_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/auth/ui/forgot_password_screen.dart)
*   **User Management & Settings (Admin UI):**
    *   [user_management_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/user/ui/user_management_screen.dart) — Daftar, cari, blokir/aktifkan, hapus akun pengguna.
    *   [admin_settings_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/settings/ui/admin_settings_screen.dart) — Konfigurasi tarif pendaftaran, durasi slot, dan profil puskesmas.
*   **Flutter Shared Models (terkait modul A):**
    *   [user_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/user_model.dart)
    *   [puskesmas_profile_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/puskesmas_profile_model.dart)
    *   [puskesmas_profile_provider.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/providers/puskesmas_profile_provider.dart)

---

### 🏥 Anggota B — Master Data (Poliklinik, Dokter, Jadwal, Cuti & Libur)
**Peran Tambahan: 🗄️ Database Owner** (Pastikan semua migration & seeder tim berjalan dan DB lokal sinkron)

Mengerjakan seluruh data operasional dasar puskesmas yang dikelola Administrator.

#### Backend (Laravel)
*   **Controllers:**
    *   [PolyclinicController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/PolyclinicController.php) — CRUD poliklinik + soft delete & restore.
    *   [DoctorController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/DoctorController.php) — CRUD dokter (registrasi user+doctor secara transaksional), profil dokter aktif, toggle status online.
    *   [DoctorScheduleController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/DoctorScheduleController.php) — CRUD jadwal praktik (validasi anti-bentrok jam).
    *   [ClinicHolidayController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/ClinicHolidayController.php) — CRUD hari libur puskesmas.
    *   [DoctorLeaveController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/DoctorLeaveController.php) — CRUD cuti/absen dokter.
*   **Services:**
    *   [DoctorService.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Services/DoctorService.php) — Logic relasi dokter-poliklinik, validasi jadwal bentrok, restore akun dokter beserta user-nya.
*   **Models:**
    *   [Polyclinic.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/Polyclinic.php)
    *   [Doctor.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/Doctor.php)
    *   [DoctorSchedule.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/DoctorSchedule.php)
    *   [ClinicHoliday.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/ClinicHoliday.php)
    *   [DoctorLeave.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/DoctorLeave.php)
*   **Form Requests:**
    *   [StorePolyclinicRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/StorePolyclinicRequest.php)
    *   [UpdatePolyclinicRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/UpdatePolyclinicRequest.php)
    *   [StoreDoctorRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/StoreDoctorRequest.php)
    *   [UpdateDoctorRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/UpdateDoctorRequest.php)
    *   [StoreDoctorScheduleRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/StoreDoctorScheduleRequest.php)
    *   [UpdateDoctorScheduleRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/UpdateDoctorScheduleRequest.php)
    *   [StoreClinicHolidayRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/StoreClinicHolidayRequest.php)
    *   [UpdateClinicHolidayRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/UpdateClinicHolidayRequest.php)
    *   [StoreDoctorLeaveRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/StoreDoctorLeaveRequest.php)
    *   [UpdateDoctorLeaveRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/UpdateDoctorLeaveRequest.php)
*   **Migrations:**
    *   `2026_04_21_123102_create_polyclinics_table.php`
    *   `2026_04_21_123103_create_doctors_table.php`
    *   `2026_04_21_123107_create_doctor_schedules_table.php`
    *   `2026_05_23_010000_create_doctor_leaves_table.php`
    *   `2026_05_23_020000_create_clinic_holidays_table.php`
*   **Seeders:**
    *   [PolyclinicSeeder.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/database/seeders/PolyclinicSeeder.php)
    *   [DoctorSeeder.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/database/seeders/DoctorSeeder.php)
    *   [DoctorScheduleSeeder.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/database/seeders/DoctorScheduleSeeder.php)

#### Frontend (Flutter)
*   **Admin Master Data UI:**
    *   [polyclinic_management_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/polyclinic/ui/polyclinic_management_screen.dart) — CRUD poliklinik lengkap dengan dialog tambah/edit & soft delete.
    *   [doctor_management_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/doctor/ui/doctor_management_screen.dart) — Pendaftaran & pengelolaan dokter beserta akunnya.
    *   [doctor_schedule_management_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/doctor/ui/doctor_schedule_management_screen.dart) — Atur jadwal praktik mingguan tiap dokter.
    *   [admin_clinic_holidays_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/clinic_holiday/ui/admin_clinic_holidays_screen.dart) — Kelola kalender hari libur klinik.
    *   [admin_doctor_leaves_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/doctor/ui/admin_doctor_leaves_screen.dart) — Kelola izin cuti dokter.
*   **Profile Dokter (`lib/features/doctor/profile/ui/`):**
    *   [doctor_profile_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/doctor/profile/ui/doctor_profile_screen.dart)
    *   [doctor_edit_profile_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/doctor/profile/ui/doctor_edit_profile_screen.dart)
*   **Flutter Shared Models (terkait modul B):**
    *   [doctor_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/doctor_model.dart)
    *   [polyclinic_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/polyclinic_model.dart)
    *   [schedule_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/schedule_model.dart)

---

### 🎫 Anggota C — Antrean & Pasien
**Peran Tambahan: ⚙️ Core Logic Lead** (QueueService adalah file paling kompleks di seluruh sistem — perlu dikoordinasikan dengan B dan D)

> [!WARNING]
> **Dependency dua arah:** Modul C membutuhkan data dokter/jadwal dari **B**. Output antrean selesai dari C menjadi trigger untuk proses pembayaran di **D**. Koordinasikan timeline dengan keduanya.

Mengerjakan inti pelayanan: booking, QR check-in, manajemen antrean loket, dan data rekam profil pasien.

#### Backend (Laravel)
*   **Controllers:**
    *   [QueueController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/QueueController.php) — Booking antrean (throttle 5/menit), QR check-in, update status, skip, recall, batalkan, restore.
    *   [PatientController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/PatientController.php) — CRUD data pasien & nomor rekam medis dengan IDOR Protection.
*   **Services:**
    *   [QueueService.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Services/QueueService.php) — Logika kalkulasi nomor urut, estimasi waktu tunggu adaptif (berdasarkan riwayat pemeriksaan riil hari ini), validasi status antrean, validasi kuota & hari libur, dan trigger notifikasi FCM saat pasien dipanggil. Menggunakan **Adaptive Waiting Time** berbasis rata-rata 3 pemeriksaan terakhir hari ini.
*   **Models:**
    *   [Queue.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/Queue.php) — Model inti dengan accessor `position_waiting` (posisi antrean dengan logika prioritas) dan `avg_waiting_time`, serta method `calculateEstimatedServiceTime()` & `recalculateEstimatedTimes()`.
    *   [Patient.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/Patient.php)
*   **Form Requests:**
    *   [StoreQueueRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/StoreQueueRequest.php)
    *   [UpdateQueueRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/UpdateQueueRequest.php)
    *   [StorePatientRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/StorePatientRequest.php)
    *   [UpdatePatientRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/UpdatePatientRequest.php)
*   **Migrations:**
    *   `2026_04_21_123104_create_patients_table.php`
    *   `2026_04_21_123105_create_queues_table.php`

#### Frontend (Flutter)
*   **Core (Sprint 0):**
    *   [app_router.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/core/router/app_router.dart)
*   **Alur Pasien:**
    *   [patient_dashboard.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/dashboard/ui/patient_dashboard.dart)
    *   [booking_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/booking/ui/booking_screen.dart)
    *   [booking_detail_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/booking/ui/booking_detail_screen.dart)
    *   [patient_history_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/history/ui/patient_history_screen.dart)
    *   [notification_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/notification/ui/notification_screen.dart)
    *   [profile_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/profile/ui/profile_screen.dart)
    *   [edit_profile_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/profile/ui/edit_profile_screen.dart)
*   **Admin/Loket Antrean:**
    *   [queue_management_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/queue/ui/queue_management_screen.dart) — Panggil, skip, recall, dan lihat detail antrean aktif.
    *   [admin_booking_detail_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/queue/ui/admin_booking_detail_screen.dart) — Detail tiket antrean dari sudut pandang admin.
    *   [qr_scanner_page.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/queue/ui/qr_scanner_page.dart) — Scan QR code untuk verifikasi check-in pasien.
    *   [patient_management_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/patient/ui/patient_management_screen.dart) — Daftar & cari rekam medis pasien di loket.
    *   [examination_history_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/patient/ui/examination_history_screen.dart) — Riwayat pemeriksaan medis pasien yang dipilih.
*   **Flutter Shared Models (terkait modul C):**
    *   [queue_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/queue_model.dart)
    *   [patient_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/patient_model.dart)

---

### 💊 Anggota D — Pemeriksaan Medis, Pembayaran, Farmasi & Dashboard
**Peran Tambahan: 📊 Visual & Finance Lead** (Bertanggung jawab atas semua output akhir dan laporan)

Mengerjakan rekam medis dokter, sistem kasir/pembayaran, manajemen stok obat, farmasi/apoteker, dan dashboard statistik visual.

#### Backend (Laravel)
*   **Controllers:**
    *   [ExaminationController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/ExaminationController.php) — Simpan rekam medis (keluhan, diagnosis, tindakan, resep obat), update, hapus, restore. Saat store: status antrean otomatis jadi `completed` & tagihan pembayaran otomatis dibuat.
    *   [PaymentController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/PaymentController.php) — Tampilkan tagihan (index/show), upload bukti bayar (disimpan sebagai Base64 di DB), verifikasi pembayaran digital (verify), konfirmasi tunai (cash-pay), tampilkan gambar bukti (getProofImage — endpoint publik).
    *   [PharmacyController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/PharmacyController.php) — Daftar resep yang siap diracik (pembayaran lunas & belum diserahkan), penyerahan obat ke pasien (dispense — kurangi stok & kirim notifikasi FCM).
    *   [MedicineController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/MedicineController.php) — CRUD inventaris obat dengan fitur pencarian & paginasi. Bisa diakses admin & apoteker.
    *   [DashboardController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/DashboardController.php) — Statistik harian: total pasien, total dokter, antrean aktif/selesai/batal hari ini, dan breakdown per poliklinik.
    *   [SettingController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/SettingController.php) — Ambil & perbarui konfigurasi operasional (`registration_fee`, `slot_duration_minutes`). Hanya dapat diakses admin.
*   **Models:**
    *   [Examination.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/Examination.php)
    *   [Payment.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/Payment.php)
    *   [Medicine.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/Medicine.php)
    *   [PrescriptionItem.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/PrescriptionItem.php)
*   **Form Requests:**
    *   [StoreExaminationRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/StoreExaminationRequest.php)
    *   [UpdateExaminationRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/UpdateExaminationRequest.php)
*   **Migrations:**
    *   `2026_04_21_123106_create_examinations_table.php`
    *   `2026_06_01_000001_create_medicines_table.php`
    *   `2026_06_01_000002_create_prescription_items_table.php`
    *   `2026_06_01_000003_create_payments_table.php`
*   **Seeders:**
    *   [MedicineSeeder.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/database/seeders/MedicineSeeder.php)

#### Frontend (Flutter)
*   **Dokter — Pemeriksaan (`lib/features/doctor/`):**
    *   [doctor_dashboard.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/doctor/dashboard/ui/doctor_dashboard.dart) — Daftar tunggu antrean pasien aktif di poli dokter hari ini.
    *   [examination_form_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/doctor/examination/ui/examination_form_screen.dart) — Input rekam medis: keluhan, diagnosis, tindakan, dan resep obat beserta instruksinya.
*   **Rekam Medis Pasien (`lib/features/patient/history/ui/`):**
    *   [medical_history_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/history/ui/medical_history_screen.dart)
    *   [medical_record_detail_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/history/ui/medical_record_detail_screen.dart)
*   **Pembayaran Pasien (`lib/features/patient/payment/ui/`):**
    *   [patient_payment_list_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/payment/ui/patient_payment_list_screen.dart)
    *   [patient_payment_detail_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/payment/ui/patient_payment_detail_screen.dart) — Upload bukti bayar & lihat status pembayaran.
*   **Pembayaran Admin (`lib/features/admin/payment/ui/`):**
    *   [admin_payment_list_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/payment/ui/admin_payment_list_screen.dart)
    *   [admin_payment_detail_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/payment/ui/admin_payment_detail_screen.dart) — Verifikasi bukti bayar digital & konfirmasi tunai.
*   **Farmasi (`lib/features/pharmacy/`):**
    *   [pharmacy_dashboard_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/pharmacy/dashboard/ui/pharmacy_dashboard_screen.dart) — Daftar resep yang siap diracik (sudah lunas).
    *   [prescription_detail_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/pharmacy/prescription/ui/prescription_detail_screen.dart) — Detail resep & tombol serahkan obat.
    *   [medicine_inventory_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/pharmacy/inventory/ui/medicine_inventory_screen.dart) — Manajemen stok obat (CRUD).
*   **Dashboard Admin (`lib/features/admin/dashboard/ui/`):**
    *   [admin_dashboard.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/dashboard/ui/admin_dashboard.dart) — Statistik & grafik kunjungan harian.
*   **Monitor Antrean Publik (`lib/features/admin/queue/ui/`):**
    *   [queue_monitor_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/queue/ui/queue_monitor_screen.dart) — Display publik nomor antrean di ruang tunggu.
*   **Flutter Shared Models (terkait modul D):**
    *   [examination_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/examination_model.dart)
    *   [payment_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/payment_model.dart)
    *   [medicine_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/medicine_model.dart)
    *   [prescription_item_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/prescription_item_model.dart)
    *   [dashboard_stats_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/dashboard_stats_model.dart)
*   **Flutter Shared Providers & Repositories (terkait modul D):**
    *   [payment_provider.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/providers/payment_provider.dart)
    *   [payment_repository.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/repositories/payment_repository.dart)
    *   [puskesmas_profile_repository.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/repositories/puskesmas_profile_repository.dart)

---

## 📢 Protokol Kerja Sama Tim

| # | Aturan |
|---|---|
| 1 | **Git:** Branch per fitur (`feature/A-auth`, `feature/B-master`, `feature/C-queue`, `feature/D-payment`). Tidak boleh push langsung ke `main`. |
| 2 | **Merge:** Hanya **A** (Git Coordinator) yang merge ke `main` setelah code review singkat. |
| 3 | **API Contract:** Selalu rujuk [API.md](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/dokumentasi/API.md) sebelum slicing UI Flutter. |
| 4 | **Seeder Update:** Jika ada seeder baru, informasikan ke tim untuk jalankan `php artisan db:seed --class=NamaSeeder`. |
| 5 | **Review Mingguan:** Sinkronisasi progress tiap minggu, terutama di titik handoff C → D (resep → pembayaran). |

---

## 🏁 Hasil Akhir Beban Kerja per Anggota

| Anggota | Controllers | Services | Models | Form Requests | Migrations | Seeders | Flutter UI | Keterangan |
| :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :--- |
| **A** | 3 | 3 | 3 | 3 | 4 | 5 | 6 | Fondasi sistem — auth, notifikasi, konfigurasi setting & profil puskesmas |
| **B** | 5 | 1 | 5 | 10 | 5 | 3 | 5 | Master data terbanyak, tapi tiap file bersifat CRUD yang relatif simpel |
| **C** | 2 | 1 | 2 | 4 | 2 | 0 | 7 | Sedikit file, tapi `QueueService` adalah service **paling kompleks** di seluruh sistem |
| **D** | 6 | 0 | 4 | 2 | 4 | 1 | 14 | Banyak UI screen (pasien, admin & farmasi), termasuk alur pembayaran end-to-end |

> [!TIP]
> Meski jumlah file C terlihat sedikit, kompleksitas logika bisnis di `QueueService.php` (kalkulasi nomor urut, estimasi waktu tunggu adaptif berbasis riwayat riil, validasi kuota, validasi hari libur & cuti dokter, dan trigger notifikasi FCM) setara dengan pengerjaan 3–4 controller CRUD biasa. Anggota C memiliki **beban kompleksitas tertinggi** meski jumlah file lebih sedikit.

> [!NOTE]
> **SettingController** (modul konfigurasi `registration_fee` & `slot_duration_minutes`) dikerjakan oleh **Anggota D** karena terintegrasi langsung dengan logika kalkulasi tagihan di `ExaminationController` dan estimasi waktu di `QueueService`. Meski berada di bawah tanggung jawab A untuk routes, logic controller ada di tanggung jawab D.
