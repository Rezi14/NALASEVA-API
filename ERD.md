# Entity-Relationship Diagram (ERD) - NalaSeva API (Updated)

Berikut adalah **Entity-Relationship Diagram (ERD)** untuk sistem **NalaSeva** yang telah diperbarui berdasarkan masukan *best practice* untuk integritas data, relasi pembayaran jamak, dan indeks komposit unik pada antrean.

---

## 📊 Diagram ERD (Mermaid)

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email UK
        string password
        enum role "admin, doctor, patient, pharmacist"
        string phone
        text address
        string national_id UK
        enum gender "Laki-laki, Perempuan"
        date birth_date
        string fcm_token
        timestamps created_at_updated_at
        soft_deletes deleted_at
    }

    patients {
        bigint id PK
        bigint user_id FK
        string medical_record_number UK
        timestamps created_at_updated_at
        soft_deletes deleted_at
    }

    polyclinics {
        bigint id PK
        string code UK
        string name
        text description
        timestamps created_at_updated_at
        soft_deletes deleted_at
    }

    doctors {
        bigint id PK
        bigint user_id FK
        bigint polyclinic_id FK
        string specialization
        string license_number UK
        boolean is_online
        timestamps created_at_updated_at
        soft_deletes deleted_at
    }

    doctor_schedules {
        bigint id PK
        bigint doctor_id FK
        enum day_of_week "monday, tuesday, wednesday, thursday, friday, saturday, sunday"
        time start_time
        time end_time
        timestamps created_at_updated_at
        soft_deletes deleted_at
    }

    doctor_leaves {
        bigint id PK
        bigint doctor_id FK
        date leave_date
        string reason
        timestamps created_at_updated_at
        soft_deletes deleted_at
    }

    queues {
        bigint id PK
        bigint patient_id FK
        bigint polyclinic_id FK
        bigint doctor_id FK
        bigint doctor_schedule_id FK
        string queue_number "UK: (queue_number, date, polyclinic_id)"
        date date
        enum status "booked, waiting, examining, completed, cancelled"
        timestamp check_in_time
        timestamp called_time
        boolean is_priority
        string reason
        integer recall_count
        time estimated_service_time
        timestamps created_at_updated_at
        soft_deletes deleted_at
    }

    examinations {
        bigint id PK
        bigint queue_id FK
        bigint doctor_id FK
        text complaint
        text diagnosis
        text treatment
        timestamps created_at_updated_at
        soft_deletes deleted_at
    }

    medicines {
        bigint id PK
        string name UK
        integer stock
        string unit
        decimal price
        timestamps created_at_updated_at
        soft_deletes deleted_at
    }

    prescription_items {
        bigint id PK
        bigint examination_id FK
        bigint medicine_id FK
        integer quantity
        decimal price
        string instruction
        timestamps created_at_updated_at
        soft_deletes deleted_at
    }

    payments {
        bigint id PK
        bigint queue_id FK
        bigint examination_id FK
        string transaction_number UK
        decimal registration_fee
        decimal medicine_fee
        decimal total_amount
        string payment_method
        string payment_proof
        enum status "pending, waiting_verification, paid, failed"
        timestamp paid_at
        timestamp dispensed_at
        timestamps created_at_updated_at
        soft_deletes deleted_at
    }

    clinic_holidays {
        bigint id PK
        date holiday_date
        string description
        timestamps created_at_updated_at
        soft_deletes deleted_at
    }

    puskesmas_profiles {
        bigint id PK
        string name
        text address
        string phone
        string email
        string logo_url
        decimal latitude
        decimal longitude
        timestamps created_at_updated_at
    }

    settings {
        bigint id PK
        string key UK
        text value
        timestamps created_at_updated_at
    }

    password_reset_otps {
        bigint id PK
        string email
        string otp_code
        timestamp expires_at
        timestamps created_at_updated_at
    }

    %% Relationships
    users ||--o| patients : "has one (1:1)"
    users ||--o| doctors : "has one (1:1)"
    polyclinics ||--o{ doctors : "has many (1:N)"
    doctors ||--o{ doctor_schedules : "has many (1:N)"
    doctors ||--o{ doctor_leaves : "has many (1:N)"
    
    patients ||--o{ queues : "has many (1:N)"
    polyclinics ||--o{ queues : "has many (1:N)"
    doctors ||--o{ queues : "has many (1:N)"
    doctor_schedules ||--o{ queues : "has many (1:N)"

    queues ||--o| examinations : "has one (1:1)"
    doctors ||--o{ examinations : "has many (1:N)"
    
    examinations ||--o{ prescription_items : "has many (1:N)"
    medicines ||--o{ prescription_items : "has many (1:N)"

    queues ||--o{ payments : "has many (1:N)"
    examinations ||--o{ payments : "has many (1:N)"
```

---

## 🔑 Penjelasan Relasi & Indeks Baru (*Best Practice Updates*)

1. **`examinations` & `queues` ➔ `payments` (One-to-Many / `1:N`)**
   - **Perubahan:** Dari sebelumnya digambarkan sebagai One-to-One (`1:1`), kini dirancang sebagai **One-to-Many (`1:N`)**.
   - **Alasan:** Memungkinkan skenario di mana satu pemeriksaan/kunjungan memiliki lebih dari satu transaksi pembayaran (misal: pembayaran pendaftaran awal dibayar terpisah dengan biaya penebusan resep obat).

2. **`doctor_schedules.day_of_week` (Menggunakan Enum Bahasa Inggris)**
   - **Perubahan:** Kolom ini di tingkat database bertipe `enum('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')`.
   - **Karakteristik:** Menggunakan standar bahasa Inggris di level database untuk kompabilitas datetime, dan secara otomatis dikonversi ke bahasa Indonesia (`Senin` - `Minggu`) oleh model Eloquent Laravel (`DoctorSchedule.php`) saat disajikan ke aplikasi.

3. **`doctors.license_number` (Unique / `UK`)**
   - **Perubahan:** Kolom nomor izin praktik (**SIP**) dokter didefinisikan sebagai indeks unik.
   - **Alasan:** Menghindari pendaftaran ganda dari data dokter yang sama.

4. **`queues.queue_number` (Composite Unique Index / `UK`)**
   - **Perubahan:** Ditambahkan indeks unik komposit pada kombinasi kolom `(queue_number, date, polyclinic_id)`.
   - **Alasan:** Menjamin tidak ada nomor antrean duplikat pada tanggal yang sama untuk poliklinik yang sama, namun tetap mendukung nomor antrean yang sama untuk digunakan di poliklinik lain atau pada hari yang berbeda.

5. **Dukungan Kunjungan Jamak Pasien (Multi-Visits)**
   - Model `queues` saat ini sudah **mendukung penuh** skenario jika seorang pasien melakukan kunjungan lebih dari sekali ke poliklinik yang sama dalam satu hari di jam berbeda. Hal ini dikarenakan setiap sesi kunjungan direpresentasikan oleh baris baru (*new record*) di tabel `queues` yang memuat ID uniknya sendiri (`queues.id`).
