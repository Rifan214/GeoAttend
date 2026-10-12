# GeoAttend — Product Requirements Document

## 1. Ringkasan
GeoAttend adalah aplikasi demo presensi untuk portofolio yang menunjukkan integrasi aplikasi mobile, geolocation, autentikasi biometrik perangkat, REST API, database, dan dashboard administrasi. Produk tidak ditujukan untuk organisasi tertentu.

## 2. Tujuan
- Pengguna dapat check-in dan check-out ketika berada di dalam radius lokasi kerja yang ditetapkan.
- Pengguna melakukan konfirmasi biometrik lokal sebelum mengirim presensi.
- Backend menjadi sumber kebenaran untuk validasi dan penyimpanan presensi.
- Admin dapat mengelola pengguna, lokasi, jadwal, dan laporan.
- Repository mudah dijalankan secara lokal tanpa Docker.

## 3. Target platform
- Mobile: Android terlebih dahulu.
- Admin: browser desktop dan mobile.
- iOS, face verification, dan offline attendance bukan bagian MVP.

## 4. Role
### Admin
- Login ke dashboard.
- Mengelola akun karyawan.
- Mengelola lokasi kerja dan radius geofence.
- Mengatur jadwal kerja dasar.
- Melihat dan memfilter data presensi.
- Mengekspor laporan CSV.

### Employee
- Login ke aplikasi mobile.
- Melihat lokasi kerja dan status lokasi perangkat.
- Melakukan konfirmasi biometrik lokal.
- Check-in dan check-out.
- Melihat riwayat presensi sendiri.

## 5. Fitur MVP
1. Autentikasi email dan password.
2. Role admin dan employee.
3. Pengelolaan karyawan oleh admin.
4. Pengelolaan lokasi: nama, latitude, longitude, radius meter, status aktif.
5. Jadwal kerja dasar: jam mulai, jam selesai, toleransi keterlambatan, dan lokasi terkait.
6. Pengambilan lokasi dengan izin eksplisit dari pengguna.
7. Validasi lokasi dan radius di backend.
8. Validasi akurasi GPS dengan ambang yang dapat dikonfigurasi; default MVP 50 meter.
9. Konfirmasi biometrik lokal melalui `local_auth`.
10. Check-in dan check-out pada satu record per karyawan per tanggal kerja.
11. Riwayat presensi karyawan.
12. Dashboard rekap dan filter tanggal/karyawan/status.
13. Ekspor CSV.
14. Audit log untuk tindakan admin penting dan keputusan presensi.
15. Automated tests untuk aturan inti.

## 6. Aturan bisnis
- Waktu resmi presensi berasal dari server, bukan jam perangkat.
- Backend mengambil lokasi kerja dan radius dari database; nilai radius dari client tidak dipercaya.
- Backend menghitung jarak menggunakan Haversine atau fungsi geospasial yang setara.
- Presensi ditolak jika akun nonaktif, lokasi nonaktif/tidak ditetapkan, koordinat tidak valid, akurasi melebihi batas, jarak melampaui radius, atau ada presensi duplikat.
- Check-in dan check-out wajib berada di dalam geofence.
- Satu record per user per tanggal lokal yang dikonfigurasi untuk demo.
- Check-out hanya dapat dilakukan setelah check-in dan hanya sekali.
- Jangan menerima waktu check-in/check-out yang dikirim client sebagai waktu otoritatif.
- Simpan latitude, longitude, dan accuracy saat check-in serta check-out untuk transparansi audit; jangan mengumpulkan tracking lokasi di latar belakang.
- Jika biometrik tidak tersedia atau tidak disiapkan, jangan diam-diam melewati langkah biometrik. Tampilkan pesan yang jelas. Untuk demo/testing, sediakan mock hanya di test environment, bukan bypass produksi.
- Jangan menyimpan gambar sidik jari atau template biometrik.

## 7. Di luar scope MVP
- Face recognition dan liveness detection.
- Penyimpanan template biometrik.
- Offline attendance dan sinkronisasi tertunda.
- Multi-tenant enterprise, payroll, cuti, lembur, dan shift lintas tengah malam.
- Push notifications.
- Integrasi perangkat fingerprint eksternal.
- Klaim anti-spoofing GPS yang absolut.

## 8. Acceptance criteria
- Karyawan yang berada di dalam radius dan memenuhi batas akurasi dapat melakukan check-in setelah konfirmasi biometrik lokal.
- Karyawan di luar radius ditolak oleh backend, walaupun client dimodifikasi.
- Client tidak dapat memilih radius atau koordinat kantor untuk melewati validasi.
- Presensi duplikat ditolak secara konsisten, termasuk permintaan bersamaan.
- Check-out tanpa check-in ditolak.
- Admin dapat membuat/mengubah/menonaktifkan lokasi dan melihat laporan.
- Employee hanya dapat melihat riwayatnya sendiri.
- Endpoint admin terlindungi role authorization.
- Token disimpan menggunakan secure storage di perangkat; tidak ditulis ke log.
- Test mencakup di dalam/di luar radius, akurasi buruk, akun nonaktif, duplikasi, check-out tanpa check-in, dan akses lintas pengguna.
- Proyek dapat dijalankan dari instruksi README tanpa Docker.

## 9. Prinsip UI/UX
- Tampilan modern, rapi, responsif, dan accessible.
- Status lokasi harus menjelaskan izin, akurasi, jarak, dan alasan penolakan secara jelas.
- Tombol check-in/check-out memiliki loading state dan mencegah double tap.
- Error jaringan dan izin ditangani dengan pesan yang bisa ditindaklanjuti.
- Jangan menampilkan status “berhasil” sebelum server mengonfirmasi.
