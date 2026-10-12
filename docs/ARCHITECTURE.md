# GeoAttend — Architecture

## 1. High-level components

- Flutter mobile: UI, state, permission prompts, GPS acquisition, local biometric prompt, API calls, secure token storage.
- Laravel: authentication, authorization, business rules, geofence calculation, attendance transaction, reporting, audit logging.
- PostgreSQL: users, work locations, schedules, attendance records, audit logs.
- Laravel Blade + Tailwind: admin dashboard; gunakan session-based web auth untuk browser.
- Laravel Sanctum personal access tokens: mobile API authentication.

## 2. Request flow
1. Employee login dengan email/password.
2. Backend menerbitkan token Sanctum; Flutter menyimpannya di `flutter_secure_storage`.
3. Employee memilih check-in atau check-out.
4. Flutter meminta permission lokasi dan membaca koordinat serta accuracy.
5. Flutter meminta konfirmasi `local_auth`.
6. Flutter mengirim koordinat, accuracy, dan metadata yang diperlukan melalui HTTPS.
7. Laravel mengautentikasi token, memeriksa status user, mengambil lokasi dan aturan dari database, memvalidasi accuracy/radius, lalu menyimpan record dalam transaction.
8. Laravel mengembalikan hasil beserta waktu server dan jarak terhitung.
9. Flutter menampilkan hasil hanya setelah respons server diterima.

## 3. Security boundaries
- Backend adalah sumber kebenaran untuk role, radius, jadwal, dan waktu presensi.
- Jangan percaya role, radius, nama lokasi, hasil jarak, maupun timestamp yang dikirim client.
- `local_auth` merupakan local confirmation, bukan attestation biometrik ke server.
- Jangan mencatat password, token, atau koordinat lengkap ke application logs secara tidak perlu.
- Gunakan HTTPS di deployment.
- Batasi request, validasi semua input, gunakan authorization policy, dan tangani duplicate requests di database.
- Gunakan transaction dan unique constraint untuk mencegah presensi ganda.
- Jangan melakukan background location tracking untuk MVP.
- Mock-location detection hanya indikator risiko, bukan jaminan.

## 4. Suggested repository layout
```text
geoattend/
  README.md
  .gitignore
  docs/
    PRD.md
    ARCHITECTURE.md
    DATABASE.md
    API_SPEC.md
    ANTIGRAVITY_PLAN.md
  backend/
    app/
    database/
    routes/
    tests/
  mobile/
    lib/
      app/
      core/
      features/
    test/
```

## 5. Flutter feature structure
```text
lib/
  app/
  core/
    api/
    auth/
    config/
    errors/
    location/
    storage/
    theme/
  features/
    login/
    attendance/
    history/
    profile/
```
Gunakan Riverpod untuk state management. Pisahkan widget/UI dari repository dan data source. Gunakan DTO/model untuk request/response API.

## 6. Laravel module boundaries
- Auth: login, logout, current user.
- Users: admin CRUD employee accounts.
- WorkLocations: CRUD lokasi dan radius.
- WorkSchedules: jadwal dasar terkait lokasi.
- Attendance: check-in/out, history, validation.
- Reports: summary dan CSV export.
- AuditLogs: catatan tindakan administratif dan keputusan penting.

Gunakan Form Requests untuk validasi input, Policies/middleware untuk authorization, service/action class untuk aturan presensi, dan API Resources untuk bentuk response yang konsisten.

## 7. Local development
- Install PHP dan Composer sesuai persyaratan versi Laravel yang dipilih.
- Install PostgreSQL lokal dan buat database `geoattend`.
- Atur backend `.env`, jalankan migration dan seeder.
- Jalankan backend dengan `php artisan serve`.
- Jalankan Flutter di Android emulator atau perangkat fisik.
- Untuk Android Emulator, host localhost biasanya diakses melalui `10.0.2.2`; untuk perangkat fisik, gunakan IP LAN komputer.
- Jangan commit `.env`, signing keys, token, atau kredensial.
