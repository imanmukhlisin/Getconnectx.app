# 📘 Panduan Penggunaan Admin Panel: Sistem Kuesioner (Onboarding)

Selamat datang di panduan Admin Panel ConnectX! Panduan ini dibuat khusus agar Anda (sebagai Admin atau Pemilik Sistem) dapat dengan mudah mengubah, menambah, atau mengatur pertanyaan-pertanyaan yang muncul saat pengguna (User) pertama kali mendaftar. 

Semua sistem pertanyaan berantai ini menggunakan sistem yang sangat canggih dan dinamis. Mari kita pelajari cara kerjanya dengan bahasa yang sederhana.

---

## 🏗️ 1. Konsep Dasar Konfigurasi (Bagaimana Sistem Ini Bekerja?)

Bayangkan sistem pendaftaran kita seperti sebuah **"Buku Cerita Interaktif" (Pilih Petualanganmu Sendiri)**. Untuk membuat buku cerita ini, ada 5 komponen utama yang harus Anda pahami, sesuai dengan urutannya:

1. 🛤️ **Flows (Alur Cerita)**
2. 📄 **Steps (Halaman Cerita)**
3. ❓ **Questions (Pertanyaan)**
4. 🔘 **Options (Pilihan Jawaban)**
5. 🔀 **Transitions (Logika Pindah Halaman)**

Berikut adalah penjelasan detail untuk masing-masing menu yang akan Anda temui di Admin Panel.

---

## 🛤️ 2. Penjelasan Tiap Menu di Admin Panel

### A. Flows (Menu: Onboarding Flows)
**Apa ini?** Ini adalah "Garis Besar Cerita" atau Pintu Masuk. 
Contoh: Alur khusus untuk para Founder, Alur khusus untuk Startup, dll.
- **Isian Penting:**
  - `ID`: Kode unik (contoh: `flow_founder`). Jangan menggunakan spasi, gunakan garis bawah (_).
  - `Name`: Nama alur yang mudah Anda kenali (contoh: "Alur Founder Cari Tim").
  - `Is Entry`: Centang ini JIKA flow ini adalah pintu masuk **pertama kali** user mendaftar. (Hanya boleh ada 1 flow yang dicentang `Is Entry`, biasanya "Data Diri Umum").

### B. Steps (Menu: Onboarding Steps)
**Apa ini?** Ini adalah "Halaman Kuesioner" di dalam sebuah layar aplikasi. Satu Alur (Flow) biasanya terdiri dari beberapa Halaman (Step).
- **Isian Penting:**
  - `ID`: Kode halaman (contoh: `step_personal_name`).
  - `Flow`: Halaman ini milik alur yang mana?
  - `Order Index`: Urutan halaman. Halaman pertama isi 1, kedua isi 2, dst.
  - `Section`: Judul kecil di atas halaman (Contoh: "Data Diri").
  - `Title`: Judul besar/Pertanyaan utamanya (Contoh: "Siapa nama Anda?").

### C. Questions (Menu: Onboarding Questions)
**Apa ini?** Ini adalah kotak isian atau pertanyaan detail yang muncul **di dalam halaman (Step)**. Satu halaman bisa berisi lebih dari 1 pertanyaan.
- **Isian Penting:**
  - `ID`: Kode pertanyaan (contoh: `q_first_name`).
  - `Step`: Pertanyaan ini ditaruh di halaman mana?
  - `Type`: Jenis isiannya. 
    - *Teks biasaya*: `text`
    - *Pilih satu dari banyak (Dropdown)*: `dropdown` atau `searchable_dropdown`
    - *Pilih banyak (Bisa lebih dari 1)*: `multi_select_chip`
    - *Pilih kotak (Card)*: `single_select_card`
  - `Label`: Teks pertanyaannya.
  - `Required`: Apakah user **wajib** mengisi ini? (Centang jika iya).

### D. Options (Menu: Onboarding Options)
**Apa ini?** JIKA pertanyaan Anda berjenis "Plihan Ganda" (Dropdown/Select), maka di sinilah Anda memasukkan opsi jawabannya. Jika pertanyaannya tipe teks bebas, bagian ini **abaikan saja**.
- **Isian Penting:**
  - `ID`: Kode opsi (contoh: `opt_loc_1`).
  - `Question`: Pilihan ini untuk pertanyaan yang mana?
  - `Label`: Teks yang dilihat oleh user (Contoh: "Jakarta").
  - `Value`: Data yang disimpan oleh komputer di belakang layar (Contoh: `jakarta`). *Matiin spasi, pakai huruf kecil semua*.
  - `Order Index`: Urutan pilihan ini muncul.

### E. Transitions (Menu: Onboarding Transitions)
**Apa ini?** Ini adalah **"Petunjuk Arah"**. Saat user mengklik tombol "Next" di sebuah halaman (Step), ke halaman mana dia harus pergi selanjutnya?
- **Isian Penting:**
  - `From Step`: Berasal dari halaman mana?
  - `To Step`: Pergi ke halaman mana selanjutnya?
  - `To Flow`: Berada di alur mana halaman tujuan tersebut?
  - `Condition (Kondisi/Syarat)`: **Inilah kehebatan sistem kita!** Anda bisa mengatur: *"Jika di pertanyaan sebelumnya dia jawab A, arahkan ke Halaman 3. Tapi jika dia jawab B, arahkan ke Halaman 4"*. 
    *(Catatan teknis: Biarkan kosong jika tidak ada syarat/langsung pindah)*.

---

## 🚀 3. Panduan Praktis: Skenario Sehari-hari

### Skenario 1: Bagaimana Cara Menambah 1 Pertanyaan Teks Baru?
*(Misal: Ingin menanyakan "Apa hobi Anda?" di halaman Data Diri)*

1. Buka menu **Onboarding Questions**.
2. Klik **Add/Create**.
3. Isi kolom:
   - `ID`: `q_hobi`
   - `Step`: Pilih "step_personal_name" (atau halaman data diri manapun).
   - `Order Index`: Taruh angka paling besar misal `5` (agar muncul paling bawah).
   - `Type`: Pilih `text`.
   - `Label`: Isi dengan "ID: Apa hobi Anda?, EN: What is your hobby?" 
4. Tekan **Save**. Selesai!

### Skenario 2: Cara Menambah Pilihan Kota Baru
*(Misal: Ingin menambah kota "Makassar")*

1. Buka menu **Onboarding Options**.
2. Klik **Add/Create**.
3. Isi kolom:
   - `ID`: `opt_loc_makassar`
   - `Question`: Pilih pertanyaan Kota/Location (contoh: `q_location`).
   - `Label`: "ID: Makassar, Indonesia, EN: Makassar, Indonesia".
   - `Value`: `makassar`
   - `Group`: Pilih "Asia Tenggara".
4. Tekan **Save**. Makassar langsung muncul di pilihan!

### Skenario 3: Menganulir User Jika Menjawab Tertentu (Mengatur Transisi)
*(Misal: Setelah halaman A, mau diarahkan ke Halaman B hanya jika dia menjawab 'Ya')*

1. Buka menu **Onboarding Transitions**.
2. Klik **Add/Create**.
3. `From Step`: Halaman A.
4. `To Step`: Halaman B.
5. `Condition`: Masukkan format syaratnya (biasanya staf teknis akan membantu mengisi format *JSON*-nya. Intinya sistem akan mengecek jawaban dari pertanyaan yang ditetapkan).
6. Tekan **Save**.

---

## 💡 Tips & Peringatan Penting
> [!WARNING]
> **Pentingnya ID yang Unik**: Semua isian yang bernama `ID` (Flow ID, Step ID, Question ID) **TIDAK BOLEH** ada yang sama/kembar di seluruh sistem.

> [!TIP]
> **Uji Coba Dulu**: Jika Anda mengubah Alur (Transition) atau menghapus Step, pastikan Anda meregistrasi akun contoh baru di aplikasi untuk mencoba apakah alurnya sudah benar dan tidak ada *halaman yang terjebak/error*.

> [!NOTE]
> Sistem ini dikelola oleh Database Supabase secara *Real-Time*. Begitu Anda klik Simpan dari Panel Admin, detil perubahan akan langsung aktif dan diunduh oleh aplikasi ke perangkat pendaftar baru.
