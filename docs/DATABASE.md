# GeoAttend — Database Design

Skema awal yang sengaja sederhana untuk MVP. Gunakan foreign keys, timestamps, index, dan constraint yang sesuai.

## 1. users
- `id` primary key
- `name` string
- `email` unique
- `password` hashed
- `role` enum/string: `admin`, `employee`
- `work_location_id` nullable FK ke `work_locations.id`
- `is_active` boolean default true
- `email_verified_at` nullable bila verifikasi email diterapkan
- timestamps

Catatan: pastikan ada admin demo melalui seeder; jangan hardcode password production.

## 2. work_locations
- `id`
- `name`
- `address` nullable
- `latitude` decimal
- `longitude` decimal
- `radius_meters` unsigned integer; nilai valid lebih besar dari nol dan dibatasi secara wajar
- `max_accuracy_meters` unsigned integer default 50
- `is_active` boolean default true
- timestamps

## 3. work_schedules
- `id`
- `work_location_id` FK
- `name`
- `start_time` time
- `end_time` time
- `late_tolerance_minutes` unsigned integer default 0
- `is_active` boolean default true
- timestamps

MVP tidak mendukung shift lintas tengah malam. Jelaskan batasan ini di README.

## 4. attendance_records
- `id`
- `user_id` FK
- `work_location_id` FK
- `attendance_date` date
- `check_in_at` nullable timestamp with timezone / UTC convention
- `check_in_latitude` nullable decimal
- `check_in_longitude` nullable decimal
- `check_in_accuracy_meters` nullable decimal
- `check_in_distance_meters` nullable decimal
- `check_out_at` nullable timestamp
- `check_out_latitude` nullable decimal
- `check_out_longitude` nullable decimal
- `check_out_accuracy_meters` nullable decimal
- `check_out_distance_meters` nullable decimal
- `status` string: e.g. `present`, `late`, `incomplete`
- timestamps
- unique constraint on (`user_id`, `attendance_date`)

Jika nanti ada beberapa presensi per hari atau shift, revisi constraint berdasarkan model shift sebelum mengimplementasikannya.

## 5. audit_logs
- `id`
- `actor_user_id` nullable FK ke users
- `action` string
- `subject_type` nullable string
- `subject_id` nullable bigint
- `metadata` nullable JSON
- `ip_address` nullable string
- timestamps

Hindari menyimpan token/password atau data sensitif yang tidak diperlukan di metadata.

## 6. Laravel Sanctum
Gunakan tabel `personal_access_tokens` dari migration Sanctum untuk token mobile.

## 7. Relationship
- User belongs to WorkLocation (nullable).
- WorkLocation has many Users.
- WorkLocation has many WorkSchedules.
- User has many AttendanceRecords.
- WorkLocation has many AttendanceRecords.
- User has many AuditLogs sebagai actor.

## 8. Indexes and integrity
- Unique `users.email`.
- Unique (`attendance_records.user_id`, `attendance_records.attendance_date`) untuk model satu record per hari.
- Index `attendance_records.attendance_date`, `attendance_records.work_location_id`, dan kombinasi yang mendukung filter laporan.
- Foreign keys dengan deletion behavior yang tidak menghapus sejarah presensi secara tidak sengaja.
- Gunakan database transaction ketika membuat atau mengubah presensi.
