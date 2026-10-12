# GeoAttend — Project Specification

GeoAttend adalah aplikasi presensi portofolio berbasis GPS geofencing dengan konfirmasi biometrik lokal pada perangkat. Target awal adalah Android, dengan backend Laravel dan dashboard admin berbasis Blade.

## Keputusan teknologi
- Mobile: Flutter + Dart
- State management: Riverpod
- GPS: `geolocator`
- Biometric prompt: `local_auth`
- API client: `dio`
- Secure token storage: `flutter_secure_storage`
- Backend: Laravel REST API + Laravel Sanctum
- Database: PostgreSQL
- Admin UI: Laravel Blade + Tailwind CSS
- Testing: Pest atau PHPUnit untuk Laravel; Flutter unit/widget tests
- Development: lokal, tanpa Docker

## Dokumen
- `PRD.md`: tujuan, pengguna, scope, dan acceptance criteria
- `ARCHITECTURE.md`: komponen dan aturan arsitektur
- `DATABASE.md`: rancangan tabel
- `API_SPEC.md`: kontrak endpoint MVP
- `ANTIGRAVITY_PLAN.md`: tahapan implementasi untuk coding agent

## Batasan penting
`local_auth` hanya mengonfirmasi autentikasi biometrik secara lokal melalui sistem operasi. Aplikasi tidak menerima sidik jari mentah dan hasil `local_auth` saja bukan bukti kriptografis yang dapat diverifikasi backend. Jangan mengklaim backend dapat memastikan sidik jari siapa yang digunakan. Untuk MVP, biometrik menjadi konfirmasi lokal sebelum mengirim permintaan presensi; autentikasi akun dan semua aturan presensi tetap divalidasi server-side.

GPS juga bukan bukti lokasi yang sempurna. Backend memvalidasi koordinat yang dikirim, akurasi, radius, waktu server, status akun, jadwal, dan duplikasi. Deteksi mock location hanya sinyal tambahan, bukan jaminan anti-spoofing.

## Prinsip pengerjaan
Implementasikan bertahap. Sebelum mulai fase baru, jalankan test dan lint/analyze fase sebelumnya, periksa perubahan, serta perbarui dokumentasi. Jangan mengimplementasikan fitur yang berada di luar MVP tanpa persetujuan.
