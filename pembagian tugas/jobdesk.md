# Pembagian Tugas Kelompok (Jobdesk) - Projek NalaSeva

Pembagian ini menggunakan **Model Vertikal (Vertical Slicing)** — setiap anggota mengerjakan Backend (Laravel) dan Frontend (Flutter) untuk modulnya masing-masing.

---

## ⚖️ Analisis Keseimbangan Beban Kerja

Sebelum pembagian, semua file diinventarisasi dan dibobot berdasarkan **jumlah + ukuran/kompleksitas** file:

| Anggota | Controllers | Services | Models | Form Requests | Migrations | Seeders | Flutter UI |
| :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **A** | 3 | 3 | 3 | 3 | 4 | 5 | 11 |
| **B** | 5 | 1 | 5 | 10 | 5 | 3 | 10 |
| **C** | 2 | 1 | 2 | 4 | 2 | 0 | 9 |
| **D** | 5 | 0 | 4 | 2 | 4 | 1 | 13 |

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
| **A** | Auth & Notifikasi | 3 | 3 | 3 | 4 | 4 | 5 | 6 | 3 |
| **B** | Master Data (Poli & Dokter) | 4 | 1 | 5 | 8 | 4 | 3 | 6 | 4 |
| **C** | Antrean & Pasien | 2 | 1 | 2 | 3 | 2 | 0 | 7 | 3 |
| **D** | Pemeriksaan, Bayar & Farmasi | 5 | 0 | 4 | 4 | 4 | 1 | 10 | 4 |

---

## 📄 Rincian File Kode per Anggota

---

### 🛡️ Anggota A — Auth, User Management & Push Notification
**Peran Tambahan: 🔑 Git Coordinator** (Me-*merge* branch semua anggota ke `main` & menjaga `routes/api.php` bebas konflik)

Mengerjakan fondasi keamanan: login, registrasi, reset password via OTP, manajemen profil user, data puskesmas, konfigurasi setting, dan push notification.

#### Backend (Laravel)
*   **Controllers:**
    *   [AuthController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/AuthController.php) — Login, register pasien mandiri, logout, profil aktif, update profil, verifikasi OTP, update FCM token.
    *   [UserController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/UserController.php) — Manajemen akun user (blokir, aktifkan, ubah role).
    *   [PuskesmasProfileController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/PuskesmasProfileController.php) — Ambil & perbarui data klinik (alamat, logo, koordinat GPS).
*   **Services:**
    *   [AuthService.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Services/AuthService.php) — Logic token Sanctum, OTP, registrasi.
    *   [FirebaseNotificationService.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Services/FirebaseNotificationService.php) — Kirim push notification server → perangkat via FCM.
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
    *   `create_users_table.php`
    *   `create_personal_access_tokens_table.php`
    *   `create_password_reset_otps_table.php`
    *   `create_settings_table.php`
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
    *   [firebase_options.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/firebase_options.dart)
    *   [main.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/main.dart)
*   **Auth UI (`lib/features/auth/ui/`):**
    *   [login_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/auth/ui/login_screen.dart)
    *   [register_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/auth/ui/register_screen.dart)
    *   [forgot_password_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/auth/ui/forgot_password_screen.dart)
*   **User Management & Settings (Admin UI):**
    *   [user_management_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/ui/user_management_screen.dart)
    *   [admin_settings_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/ui/admin_settings_screen.dart) — Konfigurasi kuota antrean, jam operasional, profil puskesmas.
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
    *   [DoctorController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/DoctorController.php) — CRUD dokter (registrasi user+doctor secara transaksional).
    *   [DoctorScheduleController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/DoctorScheduleController.php) — CRUD jadwal praktik (validasi anti-bentrok jam).
    *   [ClinicHolidayController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/ClinicHolidayController.php) — CRUD hari libur puskesmas.
    *   [DoctorLeaveController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/DoctorLeaveController.php) — CRUD cuti/absen dokter.
*   **Services:**
    *   [DoctorService.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Services/DoctorService.php) — Logic relasi dokter-poliklinik, validasi jadwal bentrok, restore akun.
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
    *   `create_polyclinics_table.php`
    *   `create_doctors_table.php`
    *   `create_doctor_schedules_table.php`
    *   `create_doctor_leaves_table.php`
    *   `create_clinic_holidays_table.php`
*   **Seeders:**
    *   [PolyclinicSeeder.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/database/seeders/PolyclinicSeeder.php)
    *   [DoctorSeeder.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/database/seeders/DoctorSeeder.php)
    *   [DoctorScheduleSeeder.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/database/seeders/DoctorScheduleSeeder.php)

#### Frontend (Flutter)
*   **Admin Master Data UI (`lib/features/admin/ui/`):**
    *   [polyclinic_management_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/ui/polyclinic_management_screen.dart)
    *   [doctor_management_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/ui/doctor_management_screen.dart)
    *   [doctor_schedule_management_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/ui/doctor_schedule_management_screen.dart)
    *   [admin_clinic_holidays_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/ui/admin_clinic_holidays_screen.dart)
    *   [admin_doctor_leaves_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/ui/admin_doctor_leaves_screen.dart)
*   **Profile Dokter (`lib/features/doctor/ui/`):**
    *   [doctor_profile_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/doctor/ui/doctor_profile_screen.dart)
    *   [doctor_edit_profile_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/doctor/ui/doctor_edit_profile_screen.dart)
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
    *   [QueueController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/QueueController.php) — Booking antrean, QR check-in, panggil/skip/recall, validasi kuota & hari libur.
    *   [PatientController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/PatientController.php) — CRUD data pasien & nomor rekam medis.
*   **Services:**
    *   [QueueService.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Services/QueueService.php) — Logika kalkulasi nomor urut, estimasi waktu, validasi status antrean, dan trigger notifikasi FCM saat pasien dipanggil.
*   **Models:**
    *   [Queue.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/Queue.php)
    *   [Patient.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/Patient.php)
*   **Form Requests:**
    *   [StoreQueueRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/StoreQueueRequest.php)
    *   [UpdateQueueRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/UpdateQueueRequest.php)
    *   [StorePatientRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/StorePatientRequest.php)
    *   [UpdatePatientRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/UpdatePatientRequest.php)
*   **Migrations:**
    *   `create_patients_table.php`
    *   `create_queues_table.php`

#### Frontend (Flutter)
*   **Core (Sprint 0):**
    *   [app_router.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/core/router/app_router.dart)
*   **Alur Pasien (`lib/features/patient/ui/`):**
    *   [patient_dashboard.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/ui/patient_dashboard.dart)
    *   [booking_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/ui/booking_screen.dart)
    *   [booking_detail_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/ui/booking_detail_screen.dart)
    *   [patient_history_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/ui/patient_history_screen.dart)
    *   [notification_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/ui/notification_screen.dart)
*   **Admin/Loket Antrean (`lib/features/admin/ui/`):**
    *   [queue_management_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/ui/queue_management_screen.dart) — Panggil, lewati, scan QR check-in.
    *   [admin_booking_detail_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/ui/admin_booking_detail_screen.dart)
    *   [patient_management_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/ui/patient_management_screen.dart) — Daftar & cari rekam medis pasien di loket.
*   **Flutter Shared Models (terkait modul C):**
    *   [queue_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/queue_model.dart)
    *   [patient_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/patient_model.dart)

---

### 💊 Anggota D — Pemeriksaan Medis, Pembayaran, Farmasi & Dashboard
**Peran Tambahan: 📊 Visual & Finance Lead** (Bertanggung jawab atas semua output akhir dan laporan)

Mengerjakan rekam medis dokter, sistem kasir, manajemen stok obat, farmasi/apoteker, dan dashboard statistik visual.

#### Backend (Laravel)
*   **Controllers:**
    *   [ExaminationController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/ExaminationController.php) — Simpan rekam medis, diagnosis, tindakan, dan proses resep obat (potong stok otomatis).
    *   [PaymentController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/PaymentController.php) — Generate tagihan, upload bukti bayar, verifikasi nontunai & tunai.
    *   [PharmacyController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/PharmacyController.php) — Antrean resep siap racik (sudah lunas).
    *   [MedicineController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/MedicineController.php) — CRUD inventaris obat.
    *   [DashboardController.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Controllers/Api/DashboardController.php) — Agregat statistik harian (total pasien, antrean, grafik per poli).
*   **Models:**
    *   [Examination.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/Examination.php)
    *   [Payment.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/Payment.php)
    *   [Medicine.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/Medicine.php)
    *   [PrescriptionItem.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Models/PrescriptionItem.php)
*   **Form Requests:**
    *   [StoreExaminationRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/StoreExaminationRequest.php)
    *   [UpdateExaminationRequest.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/app/Http/Requests/UpdateExaminationRequest.php)
*   **Migrations:**
    *   `create_examinations_table.php`
    *   `create_medicines_table.php`
    *   `create_prescription_items_table.php`
    *   `create_payments_table.php`
*   **Seeders:**
    *   [MedicineSeeder.php](file:///d:/Materi%20Semester%204/PAA%20TM/Tugas/nalaseva%20api/database/seeders/MedicineSeeder.php)

#### Frontend (Flutter)
*   **Dokter — Pemeriksaan (`lib/features/doctor/ui/`):**
    *   [doctor_dashboard.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/doctor/ui/doctor_dashboard.dart) — Daftar tunggu antrean pasien aktif di poli dokter.
    *   [examination_form_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/doctor/ui/examination_form_screen.dart) — Input rekam medis: keluhan, diagnosis, tindakan, dan resep obat.
*   **Rekam Medis Pasien (`lib/features/patient/ui/`):**
    *   [medical_history_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/ui/medical_history_screen.dart)
    *   [medical_record_detail_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/patient/ui/medical_record_detail_screen.dart)
*   **Pembayaran (`lib/features/payment/ui/`):**
    *   [payment_list_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/payment/ui/payment_list_screen.dart)
    *   [payment_detail_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/payment/ui/payment_detail_screen.dart)
*   **Farmasi (`lib/features/pharmacy/ui/`):**
    *   [pharmacy_dashboard_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/pharmacy/ui/pharmacy_dashboard_screen.dart)
    *   [prescription_detail_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/pharmacy/ui/prescription_detail_screen.dart)
    *   [medicine_inventory_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/pharmacy/ui/medicine_inventory_screen.dart)
*   **Dashboard & Monitor (`lib/features/admin/ui/`):**
    *   [admin_dashboard.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/ui/admin_dashboard.dart) — Statistik & grafik kunjungan.
    *   [examination_history_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/ui/examination_history_screen.dart)
    *   [queue_monitor_screen.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/features/admin/ui/queue_monitor_screen.dart) — Display publik ruang tunggu.
*   **Flutter Shared Models (terkait modul D):**
    *   [examination_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/examination_model.dart)
    *   [payment_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/payment_model.dart)
    *   [medicine_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/medicine_model.dart)
    *   [prescription_item_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/prescription_item_model.dart)
    *   [dashboard_stats_model.dart](file:///d:/Materi%20Semester%204/PBM/nalaseva%203/lib/shared/models/dashboard_stats_model.dart)

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
| **A** | 3 | 3 | 3 | 3 | 4 | 5 | 11 | Fondasi sistem — auth, notifikasi, konfigurasi |
| **B** | 5 | 1 | 5 | 10 | 5 | 3 | 10 | Master data terbanyak, tapi tiap file bersifat CRUD yang relatif simpel |
| **C** | 2 | 1 | 2 | 4 | 2 | 0 | 9 | Sedikit file, tapi `QueueService` adalah service **paling kompleks** di seluruh sistem |
| **D** | 5 | 0 | 4 | 2 | 4 | 1 | 13 | Banyak UI screen, tapi logic per controller lebih *straightforward* |

> [!TIP]
> Meski jumlah file C terlihat sedikit, kompleksitas logika bisnis di `QueueService.php` (kalkulasi nomor urut, estimasi waktu tunggu, validasi kuota, dan trigger notifikasi FCM) setara dengan pengerjaan 3–4 controller CRUD biasa. Anggota C memiliki **beban kompleksitas tertinggi** meski jumlah file lebih sedikit.
