# LAPORAN PERUBAHAN IMPLEMENTASI FUZZY SUGENO DAN DECISION RULE

Dokumen ini mencatat secara resmi seluruh perubahan penyempurnaan terbatas, aman, dan terdokumentasi pada aplikasi web Laravel **WEB-MONITORING-TA**.

---

## 1. IDENTITAS PERUBAHAN

- **Nama Project:** WEB-MONITORING-TA (Laravel 11)
- **Tanggal & Waktu Execution:** 28 Juli 2026
- **Branch Aktif:** `main` (Lokal)
- **Commit Awal (Baseline):** `eaf0be2` (*forgot & reset password*)
- **Commit Checkpoint:** `64585c5` (*chore: checkpoint before fuzzy risk revision*)
- **Status Push Remote:** **BELUM DIPUSH** (Hanya tersimpan di repository lokal `main`).

---

## 2. LATAR BELAKANG & REVISI LOGIS

### Kondisi Sebelum Perubahan:
1. Keluaran Fuzzy Sugeno yang mengolah parameter mikroklimatologi (Suhu Udara, Kelembapan Udara, Kelembapan Tanah) sebelumnya diklasifikasikan langsung menggunakan label `AMAN`, `WASPADA`, dan `HAMA`.
2. Decision Rule awal menentukan status `HAMA` apabila hasil Fuzzy bernilai `HAMA` ($\ge 0.70$), **meskipun objek tikus tidak terdeteksi pada citra kamera (YOLO OFF)**.

### Masalah Logis:
Parameter suhu dan kelembapan udara/tanah murni menggambarkan kondisi fisik lingkungan (mikro-klimatologi) lahan jagung. Kondisi lingkungan yang sangat mendukung perkembangbiakan/aktivitas hama **tidak membuktikan secara langsung bahwa hama tikus secara fisik berada di lokasi pada waktu tersebut**.

### Alasan Teknis Perubahan:
1. **Pemisahan Peran Modul:**
   - **Modul Fuzzy Sugeno:** Menghasilkan **Tingkat Risiko Lingkungan** (`RENDAH`, `SEDANG`, `TINGGI`).
   - **Modul YOLO Detector:** Mendeteksi **Keberadaan Fisik Hama Tikus secara Visual** (`ON` / `OFF`).
2. **Restriksi Status HAMA:** Status keputusan akhir `HAMA TERDETEKSI` **hanya boleh dihasilkan apabila YOLO bernilai `ON`**. Jika YOLO `OFF` / tidak terdeteksi, status keputusan akhir mengikuti tingkat risiko lingkungan Fuzzy (`RENDAH`, `SEDANG`, atau `TINGGI`).

---

## 3. ARSITEKTUR SEBELUM PERUBAHAN

```
[ESP32 Sensor] ──▶ [Laravel: Fuzzy Sugeno] ──▶ [Prediksi Sensor: AMAN/WASPADA/HAMA] ──┐
                                                                                         ├──▶ [Decision Rule] ──▶ Status Akhir (Bisa HAMA walau YOLO OFF)
[ESP32-CAM] ──▶ [Python: YOLO Detector] ──▶ [Deteksi YOLO: ON/OFF] ─────────────────────┘
```

---

## 4. ARSITEKTUR SESUDAH PERUBAHAN

```
[ESP32 Sensor] ──▶ [Laravel: Fuzzy Sugeno] ──▶ [Risiko Lingkungan: RENDAH/SEDANG/TINGGI] ──┐
                                                                                            ├──▶ [Decision Rule Matrix] ──▶ Status Akhir (HAMA HANYA jika YOLO ON)
[ESP32-CAM] ──▶ [Python: YOLO Detector] ──▶ [Deteksi Visual: ON/OFF] ──────────────────────┘
```

---

## 5. PERBANDINGAN SEBELUM DAN SESUDAH

| Komponen | Sebelum Perubahan | Sesudah Perubahan | Alasan & Dampak |
| :--- | :--- | :--- | :--- |
| **Keluaran Fuzzy** | `AMAN`, `WASPADA`, `HAMA` | `RENDAH`, `SEDANG`, `TINGGI` | Mencerminkan tingkat risiko kondisi lingkungan iklim mikro. |
| **Status HAMA** | Dapat dipicu oleh Fuzzy Sugeno $\ge 0.70$ saja | **Murni HANYA dipicu oleh YOLO `ON`** | Menghindari klaim adanya tikus tanpa bukti fisik visual. |
| **Pemicu Notifikasi Hama** | Fuzzy $\ge 0.70$ atau YOLO `ON` | Hanya saat YOLO `ON` | Notifikasi "Hama Tikus Terdeteksi" hanya saat ada bukti foto. |
| **Notifikasi Risiko** | "Peringatan Hama Terdeteksi" (Fuzzy $\ge 0.70$) | "Risiko Lingkungan Tinggi / Sedang" | Informasi notifikasi lebih akurat sesuai parameter lingkungan. |
| **Label UI Kamera/Riwayat** | "OFF - TIDAK ADA TIKUS" | "OFF - TIKUS TIDAK TERDETEKSI PADA CITRA" | Menghindari klaim mutlak lahan bebas tikus. |
| **Kompatibilitas Historis** | Membaca string lama langsung | Dipetakan dinamis dengan pemeta nilai fuzzy / fallback data lama | Data historis lama tetap dapat ditampilkan dengan aman. |

---

## 6. ALGORITMA FUZZY SUGENO YANG TIDAK BERUBAH

Seluruh struktur dasar matematika Fuzzy Sugeno tetap **100% UTUH & TIDAK DIUBAH**:
- **3 Variabel Input:** Suhu Udara ($x_1$), Kelembapan Udara ($x_2$), Kelembapan Tanah ($x_3$).
- **Fungsi Keanggotaan:** Bahu Kiri (Linear Turun), Bahu Kanan (Linear Naik), dan Komplemen Linear.
- **27 Rule Base:** Permutasi lengkap $3 \times 3 \times 3 = 27$ aturan IF-THEN.
- **Operator Firing Strength:** Operator MIN (`min($suhu, $udara, $tanah)`).
- **Konsekuen Singleton ($z_i$):** Nilai konstanta $0.10$ s.d. $1.00$.
- **Defuzzifikasi:** Sugeno Weighted Average $\frac{\sum (\alpha_i \cdot z_i)}{\sum \alpha_i}$.
- **Threshold Numerik:** $0.45$ (Batas Waspada/Sedang) dan $0.70$ (Batas Hama/Tinggi).

---

## 7. PERUBAHAN KLASIFIKASI FUZZY

| Rentang Nilai Numerik ($Z^*$) | Label Lama | Label Baru | Makna Baru |
| :---: | :---: | :---: | :--- |
| $Z^* < 0.45$ | `AMAN` | **`RENDAH`** | Kondisi iklim mikro berisiko rendah terhadap potensi hama. |
| $0.45 \le Z^* < 0.70$ | `WASPADA` | **`SEDANG`** | Kondisi iklim mikro berisiko sedang, perlu pemantauan berkala. |
| $Z^* \ge 0.70$ | `HAMA` | **`TINGGI`** | Kondisi iklim mikro berisiko tinggi / kondusif bagi perkembangan hama. |

---

## 8. DECISION RULE FINAL BERDASARKAN KODE AKTUAL

Daftar lengkap kombinasi input-output pada method `getSystemDecision(?string $hasilDeteksiYolo, string $prediksiSensor)`:

| No | Nilai YOLO | Risiko Fuzzy | Confidence Digunakan? | Status Internal | Label pada UI | Notifikasi Ditampilkan | File & Function | Line | Hasil Test |
| :-: | :---: | :---: | :-: | :---: | :---: | :--- | :--- | :-: | :-: |
| 1 | `ON` | RENDAH | Tidak | `HAMA` | HAMA TERDETEKSI | 🚨 Hama Tikus Terdeteksi | `SensorController::getSystemDecision` | 583 | PASS |
| 2 | `ON` | SEDANG | Tidak | `HAMA` | HAMA TERDETEKSI | 🚨 Hama Tikus Terdeteksi | `SensorController::getSystemDecision` | 583 | PASS |
| 3 | `ON` | TINGGI | Tidak | `HAMA` | HAMA TERDETEKSI | 🚨 Hama Tikus Terdeteksi | `SensorController::getSystemDecision` | 583 | PASS |
| 4 | `OFF` | RENDAH | Tidak | `RENDAH` | RISIKO LINGKUNGAN RENDAH | (Tidak ada notifikasi darurat) | `SensorController::getSystemDecision` | 590 | PASS |
| 5 | `OFF` | SEDANG | Tidak | `SEDANG` | RISIKO LINGKUNGAN SEDANG | ⚠️ Risiko Lingkungan Sedang | `SensorController::getSystemDecision` | 590 | PASS |
| 6 | `OFF` | TINGGI | Tidak | `TINGGI` | RISIKO LINGKUNGAN TINGGI | ⚠️ Risiko Lingkungan Tinggi | `SensorController::getSystemDecision` | 590 | PASS |
| 7 | `null` | RENDAH | Tidak | `RENDAH` | RISIKO LINGKUNGAN RENDAH | (Tidak ada notifikasi darurat) | `SensorController::getSystemDecision` | 590 | PASS |
| 8 | `null` | SEDANG | Tidak | `SEDANG` | RISIKO LINGKUNGAN SEDANG | ⚠️ Risiko Lingkungan Sedang | `SensorController::getSystemDecision` | 590 | PASS |
| 9 | `null` | TINGGI | Tidak | `TINGGI` | RISIKO LINGKUNGAN TINGGI | ⚠️ Risiko Lingkungan Tinggi | `SensorController::getSystemDecision` | 590 | PASS |
| 10 | `""` (Kosong) | TINGGI | Tidak | `TINGGI` | RISIKO LINGKUNGAN TINGGI | ⚠️ Risiko Lingkungan Tinggi | `SensorController::getSystemDecision` | 590 | PASS |
| 11 | `INVALID` | TINGGI | Tidak | `TINGGI` | RISIKO LINGKUNGAN TINGGI | ⚠️ Risiko Lingkungan Tinggi | `SensorController::getSystemDecision` | 590 | PASS |
| 12 | OFFLINE | (Cache null) | Tidak | `OFFLINE` | OFFLINE | (Tidak ada notifikasi) | `SensorController::getStatusGlobal` | 51 | PASS |

---

## 9. DAFTAR FILE YANG DIUBAH

| No | File | Class / Function / Bagian | Rentang Baris | Perubahan | Alasan |
| :-: | :--- | :--- | :-: | :--- | :--- |
| 1 | `app/Http/Controllers/SensorController.php` | `getStatusGlobal()` | 36–52 | Mengubah fallback default dari `'AMAN'` ke `'RENDAH'` | Penyelarasan konstanta label baru |
| 2 | `app/Http/Controllers/SensorController.php` | `createNotification()` | 106–131 | Memperbarui judul dan isi pesan notifikasi untuk `HAMA`, `TINGGI`, dan `SEDANG` | Penyesuaian kriteria notifikasi Tahap 7 |
| 3 | `app/Http/Controllers/SensorController.php` | `store()` & `manual()` | 427, 477, 487 | Menggunakan `getSystemDecision()` dan notifikasi untuk `HAMA`, `TINGGI`, `SEDANG` | Konsistensi jalur penerimaan data API & manual |
| 4 | `app/Http/Controllers/SensorController.php` | `getStatus()` | 573–580 | Mengubah nilai return dari `['HAMA', ...]` / `['WASPADA', ...]` / `['AMAN', ...]` menjadi `['TINGGI', ...]` / `['SEDANG', ...]` / `['RENDAH', ...]` | Klasifikasi risiko lingkungan Fuzzy Tahap 3 |
| 5 | `app/Http/Controllers/SensorController.php` | `getSystemDecision()` | 583–597 | Restriksi status `HAMA` murni hanya jika YOLO `ON` | Implementasi Decision Rule Tahap 4 |
| 6 | `app/Http/Controllers/SensorController.php` | `riwayat()` & `adminRiwayat()` | 721, 1029 | Memperluas filter kueri status deteksi (`HAMA`, `TINGGI`, `SEDANG`, `RENDAH`) | Kompatibilitas kueri data lama dan baru |
| 7 | `app/Console/Commands/MQTTSubscribe.php` | `getStatus()` & `handle()` | 132, 239–247 | Menyelaraskan return `getStatus()` dan trigger notifikasi MQTT | Konsistensi jalur MQTT background worker |
| 8 | `resources/views/prediksi.blade.php` | Blade view | 1–160 | Memperbarui judul halaman, badge status, deskripsi analisis, dan legenda threshold | Penyelarasan antarmuka Tahap 6 |
| 9 | `resources/views/dashboard.blade.php` | Blade view & JS Polling | 80–100, 445–555 | Memperbarui teks status banner, badge keputusan sistem, dan script polling JS | Penyelarasan UI Dashboard |
| 10 | `resources/views/riwayat.blade.php` | Blade view & Table | 28–110 | Memperbarui opsi filter, ringkasan jumlah, badge kompatibilitas data lama, dan label YOLO | Penyelarasan UI Riwayat User |
| 11 | `resources/views/kamera.blade.php` | Blade view & JS Polling | 265–310, 485–540 | Memperbarui label "Tingkat Risiko Lingkungan", teks status besar, dan text YOLO `ON/OFF` | Penyelarasan UI Kamera Monitoring |
| 12 | `resources/views/admin/dashboard.blade.php` | Blade view | 381–415 | Memperbarui deskripsi dan label form konfigurasi threshold admin | Penyelarasan UI Admin Panel |
| 13 | `resources/views/admin/riwayat.blade.php` | Blade view & Table | 85–174 | Memperbarui opsi filter, ringkasan jumlah, badge kompatibilitas data lama, dan label YOLO | Penyelarasan UI Riwayat Admin |

---

## 10. DAFTAR FILE YANG TIDAK DIUBAH (Strict Compliance)

Ditegaskan bahwa **TIDAK ADA PERUBAHAN SAMA SEKALI** pada:
- Script Python YOLO (`yolo_mouse_detector`) & File Model YOLO (`.pt`/`.onnx`).
- Program Mikrokontroler Arduino / ESP32 & Modul ESP32-CAM.
- Flow Node-RED & Broker / Topic MQTT (`priyatna/deteksi/data`).
- API Endpoint (`POST /api/sensor`) & Header Token (`X-API-TOKEN`).
- Format Payload JSON / Request Input Field (`suhu_udara`, `kelembapan_udara`, `kelembapan_tanah`, `sensor_name`, `status`, `value`).
- Algoritma 27 Rule Fuzzy, Fungsi Keanggotaan, Operator MIN, Weighted Average, dan Threshold Dinamis DB (`0.45` & `0.70`).
- Skema Migration & Struktur Tabel Database (`sensor_readings`, `notifications`, `threshold_settings`).
- File Konfigurasi `.env` & File Backup Manual `WEB-MONITORING-TA_BACKUP_SEBELUM_REVISI_FUZZY`.

---

## 11. PERUBAHAN ANTARMUKA (UI SUMMARY)

| Halaman | Label Lama | Label Baru | File |
| :--- | :--- | :--- | :--- |
| **Prediksi** | Prediksi Serangan Hama | **Analisis Risiko Lingkungan dan Deteksi Hama** | `resources/views/prediksi.blade.php` |
| **Prediksi** | Prediksi Sensor (Fuzzy) | **Tingkat Risiko Lingkungan (Fuzzy Sugeno)** | `resources/views/prediksi.blade.php` |
| **Kamera** | Prediksi Sensor (Fuzzy) | **Tingkat Risiko Lingkungan (Fuzzy Sugeno)** | `resources/views/kamera.blade.php` |
| **Riwayat / Kamera** | OFF - TIDAK ADA TIKUS | **OFF - TIKUS TIDAK TERDETEKSI PADA CITRA** | `resources/views/riwayat.blade.php` & `kamera.blade.php` |
| **Riwayat / Kamera** | ON - TIKUS TERDETEKSI | **ON - TIKUS TERDETEKSI PADA CITRA** | `resources/views/riwayat.blade.php` & `kamera.blade.php` |
| **Dashboard / Kamera** | Keputusan Akhir: HAMA | **Keputusan Akhir: HAMA TERDETEKSI** (jika YOLO ON) | `resources/views/dashboard.blade.php` & `kamera.blade.php` |
| **Dashboard / Kamera** | Keputusan Akhir: WASPADA | **Keputusan Akhir: RISIKO LINGKUNGAN SEDANG** | `resources/views/dashboard.blade.php` & `kamera.blade.php` |
| **Dashboard / Kamera** | Keputusan Akhir: AMAN | **Keputusan Akhir: RISIKO LINGKUNGAN RENDAH** | `resources/views/dashboard.blade.php` & `kamera.blade.php` |

---

## 12. PERUBAHAN NOTIFIKASI

| Kondisi Sistem | Judul Notifikasi Baru | Isi Pesan Notifikasi Baru | File |
| :--- | :--- | :--- | :--- |
| **YOLO `ON`** | `Hama Tikus Terdeteksi` | Tikus terdeteksi pada citra kamera. Lakukan pemeriksaan dan penanganan pada area lahan terkait. | `SensorController.php` (L. 110) |
| **YOLO `OFF` & Fuzzy `TINGGI`** | `Risiko Lingkungan Tinggi` | Kondisi suhu dan kelembapan menunjukkan tingkat risiko lingkungan tinggi. Disarankan melakukan pemeriksaan langsung pada lahan. | `SensorController.php` (L. 113) |
| **YOLO `OFF` & Fuzzy `SEDANG`** | `Risiko Lingkungan Sedang` | Kondisi lingkungan berada pada tingkat risiko sedang. Lakukan pemantauan secara berkala. | `SensorController.php` (L. 116) |
| **YOLO `OFF` & Fuzzy `RENDAH`** | *(Tidak membuat notifikasi)* | *(Tidak ada kiriman notifikasi darurat)* | `SensorController.php` (L. 119) |

---

## 13. KOMPATIBILITAS DATA LAMA

1. **Apakah record lama di database diubah?** **TIDAK**. Tidak ada update massal database, seeding ulang, maupun pembersihan data lama.
2. **Bagaimana data lama disajikan di UI?**
   Di dalam view `riwayat.blade.php` & `admin/riwayat.blade.php`, kode memetakan tampilan secara dinamis:
   - Jika `deteksi_yolo === 'ON'`: Ditamapilkan sebagai `HAMA TERDETEKSI`.
   - Jika `deteksi_yolo !== 'ON'` dan Memiliki `nilai_fuzzy`: Dikelompokkan ke `RISIKO LINGKUNGAN RENDAH` ($<0.45$), `SEDANG` ($0.45 - 0.6999$), atau `TINGGI` ($\ge 0.70$).
   - Jika data legacy tidak memiliki `nilai_fuzzy`: Ditampilkan dengan keterangan `RISIKO TINGGI (DATA LAMA)` / `SEDANG (DATA LAMA)` / `RENDAH (DATA LAMA)` dan **tidak menyatakan bahwa tikus terdeteksi secara visual**.

---

## 14. HASIL PENGUJIAN KLASIFIKASI FUZZY SUGENO

Unit test dieksekusi secara otomatis melalui runner `tests/run_tests.php`:

| Nilai Fuzzy ($Z^*$) | Expected Status | Actual Status | Pass / Fail |
| :---: | :---: | :---: | :---: |
| `0.00` | `RENDAH` | `RENDAH` | **PASS** |
| `$tw - 0.0001` (misal `0.4499` / `0.4999`) | `RENDAH` | `RENDAH` | **PASS** |
| `$tw` (misal `0.45` / `0.50`) | `SEDANG` | `SEDANG` | **PASS** |
| `$th - 0.0001` (misal `0.6999`) | `SEDANG` | `SEDANG` | **PASS** |
| `$th` (misal `0.70`) | `TINGGI` | `TINGGI` | **PASS** |
| `1.00` | `TINGGI` | `TINGGI` | **PASS** |

---

## 15. HASIL PENGUJIAN DECISION RULE

| Test Input YOLO | Test Input Fuzzy | Expected Decision | Actual Decision | Pass / Fail |
| :---: | :---: | :---: | :---: | :---: |
| `'ON'` | `'RENDAH'` | `'HAMA'` | `'HAMA'` | **PASS** |
| `'ON'` | `'SEDANG'` | `'HAMA'` | `'HAMA'` | **PASS** |
| `'ON'` | `'TINGGI'` | `'HAMA'` | `'HAMA'` | **PASS** |
| `'OFF'` | `'RENDAH'` | `'RENDAH'` | `'RENDAH'` | **PASS** |
| `'OFF'` | `'SEDANG'` | `'SEDANG'` | `'SEDANG'` | **PASS** |
| `'OFF'` | `'TINGGI'` | `'TINGGI'` | `'TINGGI'` | **PASS** |
| `null` | `'RENDAH'` | `'RENDAH'` | `'RENDAH'` | **PASS** |
| `null` | `'SEDANG'` | `'SEDANG'` | `'SEDANG'` | **PASS** |
| `null` | `'TINGGI'` | `'TINGGI'` | `'TINGGI'` | **PASS** |
| `""` (Kosong) | `'TINGGI'` | `'TINGGI'` | `'TINGGI'` | **PASS** |
| `'INVALID'` | `'TINGGI'` | `'TINGGI'` | `'TINGGI'` | **PASS** |

---

## 16. HASIL PENGUJIAN SISTEM KESELURUHAN

- **Unit Test Runner (`php tests/run_tests.php`):** **2 Passed, 0 Failed (100% Success)**.
- **PHP Syntax Check (`php -l`):** No syntax errors pada `SensorController.php` dan `MQTTSubscribe.php`.
- **Route & Controller Check:** Seluruh endpoint lama `/dashboard`, `/prediksi`, `/kamera`, `/riwayat`, `/manual`, `/api/sensor`, `/live-data` tetap berfungsi normal tanpa merusak *signature*.

---

## 17. GIT STATUS & DIFF

```
On branch main
Your branch is ahead of 'origin/main' by 1 commit.

Changes to be committed:
  docs/LAPORAN_PERUBAHAN_FUZZY_DAN_DECISION_RULE.md (NEW DOCUMENTATION)
  app/Http/Controllers/SensorController.php
  app/Console/Commands/MQTTSubscribe.php
  resources/views/prediksi.blade.php
  resources/views/dashboard.blade.php
  resources/views/riwayat.blade.php
  resources/views/kamera.blade.php
  resources/views/admin/dashboard.blade.php
  resources/views/admin/riwayat.blade.php
  tests/Unit/FuzzyAndDecisionRuleTest.php
  tests/run_tests.php
```

---

## 18. PETUNJUK ROLLBACK (PETUNJUK PEMULIHAN)

> **PERINGATAN:** JANGAN MENJALANKAN ROLLBACK ATAU `git reset --hard` KECUALI ATAS PERINTAH DARI PENGGUNA.

Apabila di kemudian hari perubahan ini ingin dibatalkan secara aman tanpa merusak riwayat Git:
1. Batalkan commit dengan perintah `git revert`:
   ```bash
   git revert <hash-commit-terakhir>
   ```
2. Atau gunakan salinan cadangan manual yang berada di luar repositori:
   `WEB-MONITORING-TA_BACKUP_SEBELUM_REVISI_FUZZY`

---

## 19. KESIMPULAN AKHIR

| Pertanyaan Evaluasi | Jawaban |
| :--- | :---: |
| a. Apakah Python YOLO berubah? | **TIDAK** |
| b. Apakah perangkat IoT (Arduino/ESP32) berubah? | **TIDAK** |
| c. Apakah Node-RED berubah? | **TIDAK** |
| d. Apakah broker / topic MQTT berubah? | **TIDAK** |
| e. Apakah payload API / MQTT berubah? | **TIDAK** |
| f. Apakah formula dasar Fuzzy Sugeno berubah? | **TIDAK** |
| g. Apakah 27 Rule Fuzzy berubah? | **TIDAK** |
| h. Apakah struktur database / migration berubah? | **TIDAK** |
| i. Apakah Fuzzy sekarang menghasilkan RENDAH/SEDANG/TINGGI? | **YA** |
| j. Apakah HAMA hanya berasal dari YOLO ON? | **YA** |
| k. Apakah UI sudah selaras secara istilah? | **YA** |
| l. Apakah notifikasi sudah selaras? | **YA** |
| m. Apakah semua unit test berhasil? | **YA (100% PASS)** |
| n. Status Kelayakan Integrasi & Kode | **SIAP** |
| o. Status Persiapan Commit / Push | **SIAP (Menunggu persetujuan eksplisit pengguna)** |

---

## 20. PEMISAHAN DATA LIVE YOLO DAN DATA HISTORIS SENSOR

### 1. Masalah Sebelum Perbaikan
Python YOLO mengirimkan request HTTP `POST /api/sensor` berisi status `OFF` / `ON`, `confidence`, dan file gambar secara berulang mengikuti *loop* deteksi (sekitar 1 detik per request). Sebelumnya, setiap request HTTP tersebut mengeksekusi `SensorReading::create()` tanpa memfilter kejadian, sehingga dalam waktu singkat database `sensor_readings` dibanjiri ribuan record duplikat dan ribuan file foto `OFF`.

### 2. Penyebab Ribuan Record
`SensorController::store()` mengeksekusi pembuatan baris database `SensorReading::create()` secara acak tanpa mengecek apakah request yang masuk merupakan frame rutin `OFF`, transisi kejadian `OFF → ON`, atau duplikat `ON`.

### 3. Alur Data Live
Data live diperbarui pada setiap request HTTP dari Python YOLO tanpa membuat record database baru:
- Status & confidence YOLO disimpan pada cache Laravel (`yolo_live_data` & `latest_yolo_status`).
- File gambar live disimpan atau ditimpa pada path stabil `storage/app/public/kamera/latest_live.jpg`.
- Panel Kamera utama membaca data dari cache live tersebut.

### 4. Alur Data Historis 15 Menit
Penyimpanan rutin data sensor lingkungan (suhu, kelembapan udara, kelembapan tanah) dilakukan maksimal setiap 15 menit melalui jalur `MQTTSubscribe.php` atau pencatatan rutin. Saat record 15 menit dibuat, sistem membaca status YOLO & confidence terbaru dari cache live, serta menyalin gambar live `latest_live.jpg` menjadi file snapshot permanen `storage/app/public/kamera/periodic_{timestamp}_{uniqid}.jpg`.

### 5. Alur Event HAMA Real-Time (Transisi OFF → ON)
Ketika terjadi transisi status **OFF → ON** (deteksi hama tikus baru):
- Sistem langsung membuat 1 record `SensorReading` di database (`deteksi = 'HAMA'`).
- Gambar bukti disalin menjadi file permanen `storage/app/public/kamera/hama_on_{timestamp}_{uniqid}.jpg`.
- Notifikasi darurat `"Hama Tikus Terdeteksi"` langsung dikirimkan ke pengguna.
- Proses dilakukan secara *real-time* tanpa menunggu interval 15 menit.

### 6. Pencegahan Duplikasi ON & Atomic Lock
Untuk mencegah *race condition* jika dua request `ON` masuk hampir bersamaan:
- Digunakan `Cache::lock('yolo_on_transition_lock', 5)` untuk memastikan hanya 1 proses transisi yang berjalan.
- Selama status tetap `ON → ON`, request berikutnya hanya memperbarui data live & gambar live, tanpa membuat record database atau notifikasi baru.

### 7. Pengelolaan Gambar Live & Permanen
- **Gambar Live:** Path stabil `kamera/latest_live.jpg` selalu memuat citra visual terbaru.
- **Gambar Historis:** File permanen dengan nama unik (`hama_on_...` atau `periodic_...`) disimpan di database dan tidak pernah ditimpa.

### 8. Perbaikan Riwayat Foto Kamera (5 Terakhir)
- Server-side render pertama kali pada `SensorController::kamera()` langsung mengirimkan variabel `$riwayatHtml` berisi 5 foto terbaru yang memiliki gambar (baik status `ON` maupun `OFF`).
- Endpoint AJAX `/api/kamera/latest` mengembalikan HTML riwayat foto secara konsisten.
- Diperbaiki penanganan error JavaScript agar indikator loading `"Memuat riwayat foto..."` langsung dihentikan dan diganti dengan teks yang wajar jika koneksi terputus.

### 9. Daftar File yang Diubah
- `app/Http/Controllers/SensorController.php` (Penanganan transisi live/historis & riwayat foto)
- `app/Console/Commands/MQTTSubscribe.php` (Penyimpanan snapshot gambar pada record berkala 15 menit)
- `resources/views/kamera.blade.php` (Server-side render `$riwayatHtml` & penanganan error polling JS)
- `docs/LAPORAN_PERUBAHAN_FUZZY_DAN_DECISION_RULE.md` (Dokumentasi lengkap)

### 10. Hasil Pengujian (`tests/test_yolo_behavior.php`)
- **10 Request OFF Berulang:** 0 record DB baru, 0 notifikasi baru (100% PASS).
- **Transisi OFF → ON:** 1 record HAMA dibuat, gambar bukti permanen disimpan, 1 notifikasi dikirim per user (100% PASS).
- **10 Request ON Berulang:** 0 record DB duplikat, 0 notifikasi duplikat (100% PASS).
- **Transisi ON → OFF:** Status live kembali ke OFF, 0 record DB duplikat (100% PASS).
- **Endpoint Kamera Latest:** Mengembalikan HTML 5 foto terbaru dengan URL valid (100% PASS).

### 11. Keterbatasan
Python YOLO masih dapat mengirim request HTTP secara berulang pada setiap frame deteksi, namun dampaknya terhadap pembengkakan database dan penyimpanan disk telah **100% dikendalikan di sisi Laravel**.

### 12. Konfirmasi Skema Database & Payload
- Format payload JSON Python: **TIDAK DIUBAH** (`sensor_name`, `status`, `value`, `timestamp`, `image`).
- Migration / Schema Database: **TIDAK DIUBAH** (Menggunakan tabel & kolom `sensor_readings` yang ada).
