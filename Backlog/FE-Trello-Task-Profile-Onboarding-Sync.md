# FE TASK: Integration Profile API Modernization & Streamlined Onboarding (CON-73 & CON-74)

Copy-paste data berikut ke Trello Card Anda.

## Title
[FE] Integration: Sapu Jagat Profile API, Smart Slug Formatter & Streamlined Onboarding

## Description
Backend telah mengimplementasikan perombakan besar pada struktur data profil dan alur onboarding untuk memberikan fleksibilitas penuh pada Frontend. 

**Key Backend Changes:**
1. **Raw Data Exposure:** `GET /api/v1/me/profile` kini mengembalikan `userRaw` dan `startupRaw` (semua kolom DB dalam format camelCase).
2. **Smart Slug Formatter:** Field slug (seperti `role_category`, `industry`, `stage`) otomatis dikirim sebagai string cantik (contoh: "Software Engineer").
3. **Update Reversal:** `PATCH` profil mendukung pengiriman string cantik tersebut; Backend otomatis mengubahnya kembali menjadi slug (`software_engineer`) sebelum simpan.
4. **LinkedIn Data Injection:** Data hasil scraping LinkedIn kini tersedia di `userRaw.linkedinData`.
5. **Streamlined Onboarding:** Alur onboarding builder telah diringkas (beberapa step digabung) dan mendukung fitur *Fast-Forward* (lewati biodata jika sudah onboarded).

## Checklist
### 1. Integrasi Master Data (Dropdowns)
- [ ] Ganti semua opsi hardcoded dengan data dinamis dari `GET /api/v1/profile-options` (untuk lokasi & hobi).
- [ ] Gunakan `GET /api/v1/discovery/filter-options` untuk mendapatkan katalog Industri, Skills, dan Roles yang valid.

### 2. Modernisasi Profile Screen
- [ ] Update state management untuk mengonsumsi `userRaw` dan `startupRaw`.
- [ ] Tampilkan data pengalaman/pendidikan dari `userRaw.linkedinData` jika tersedia.
- [ ] Implementasikan `PATCH /api/v1/me/profile` menggunakan field-field baru (avatarUrl, birthday, gender, dll).
- [ ] Implementasikan `PATCH /api/v1/me/startup` menggunakan field-field baru (logoUrl, stage, industry, openRoles, dll).

### 3. Streamlined Onboarding Flow
- [ ] Sesuaikan navigasi step onboarding sesuai kontrak `CON-73-StreamlineOnboardContract.md`.
- [ ] Implementasikan *Combined Screens* (satu step mengirim banyak jawaban sekaligus, misal di `step_identity_details`).
- [ ] Pastikan validasi regex LinkedIn di frontend mendukung query parameters (Loose Regex).

### 4. Verification
- [ ] Pastikan saat user melakukan "Switch Mode", aplikasi langsung lompat ke Step 5 (Role Selection) tanpa mengisi ulang nama/TTL.
- [ ] Verifikasi data yang diupdate di profil tersimpan dengan format slug yang benar di database (cek via API lagi).

## Reference Documentation
- **Profile API & Catalogs:** [CON-74-ProfileAPI-RawData-Editability-And-Catalogs.md](file:///home/Rama_indonesia/codevits_projects/connectx-backend/Backlog/CON-74-ProfileAPI-RawData-Editability-And-Catalogs.md)
- **Onboarding Contract:** [CON-73-StreamlineOnboardContract.md](file:///home/Rama_indonesia/codevits_projects/connectx-backend/Backlog/CON-73-StreamlineOnboardContract.md)
- **LinkedIn Raw Sample:** [RawDataLinkedin.md](file:///home/Rama_indonesia/codevits_projects/connectx-backend/Backlog/RawDataLinkedin.md)
