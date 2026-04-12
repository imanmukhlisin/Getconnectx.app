# Panduan Ngetes API di Postman (Live Action 6 Flow & Validasi)

Berikut adalah panduan tembak API rahasia lu lewat Postman, menggunakan data **asli 6-Flow** yang berada di `OnboardingSeeder`.  

**Persiapan Awal:**  
Pastikan lo udah dapet `Bearer Token` dari proses *Login/Verify WhatsApp* (atau copy manual token dari DB).  

### Step 1: Tarik Nafas, Buka Sesi Baru
Ini tombol *Start* balapannya.  
*   **Method:** `POST`
*   **URL:** `{{base_url}}/api/v1/onboarding/sessions`
*   **Response:** Lo bakal dapet string `session_id` (Misal: `ses_abc123`). Bawa tiket ini kemanapun lo jalan.

---

### Step 2: Pengujian Nembus Tembok Pengaman (Test PR #2 Server Validation)


*   **Method:** `POST`
*   **URL:** `{{base_url}}/api/v1/onboarding/sessions/{{session_id}}/answer`
*   **Body (raw JSON):**
```json
{
  "step_id": "step_personal_name",
  "answers": {
    "q_first_name": "Dimas",
  }
}
```
*(Asumsi: Misal string "Dimas" kurang dari `min_length` atau jika di-set sengaja kosong)*
*(Asumsi: Misal string "Dimas" lebih dari `max_length` lebih dari 50 karakter)*
*(Note: jika cuma "Dimas" doang yang dikirim, maka akan ditolak karena `q_last_name` tidak diisi)*

**BOOM! DITOLAK (Status `422 Unprocessable Entity`):**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "q_first_name": [
            "Tulisan 'Nama Depan' terlalu pendek (Minimal 3 karakter).",
            "Tulisan 'Nama Depan' terlalu panjang (Maksimum 50 karakter)."
            "Tulisan 'Hanya q_first_name dan q_last_name yang diisi'"
        ]
    }
}
```

---

### Step 3: Jalan Benar menuju Persimpangan Tipe Akun (Flow Common)
Minta maaf, benerin jawabannya lalu lanjut isi semua step: Nama, Lokasi, Ketersediaan. Begitu nyampe di `step_role_selection` (Milih Peran):

*   **Method:** `POST`
*   **URL:** `{{base_url}}/api/v1/onboarding/sessions/{{session_id}}/answer`
*   **Body (raw JSON):**
```json
{
  "step_id": "step_role_selection",
  "answers": {
    "q_use_connectx": "startup"
  }
}
```
> **Magic Engine:** Otak Engine lo bakal ngeliat oh orang ini isinya `"startup"`, transisi nomor 1 nangkep ini. *Wushh...* Tiba-tiba balasan "next_step" lo bakal mendadak memunculkan pertanyaan tentang *Ceritakan tentang perjalanan startup Anda* (`step_flow_f`). 

---

### Step 4: Nyentuh Garis Finish (Di Flow F: Profil Startup)
Meskipun lo tadinya masuk pake *Flow Common*, sekarang lo lagi berdiri di tanah *Flow F*. Kita isi *Chip Array*-nya sekaligus diuji validasinya (Misal lo nekat pilih 6 Industri padahal aturannya form lo maksimal 5).

*   **Method:** `POST`
*   **URL:** `{{base_url}}/api/v1/onboarding/sessions/{{session_id}}/answer`
*   **Body (raw JSON):**
```json
{
  "step_id": "step_flow_f",
  "answers": {
    "q_ff_name": "Warung Masa Depan Indo",
    "q_ff_stage": "mvp",
    "q_ff_look": "team",
    "q_ff_ind": ["fintech", "edtech", "healthtech", "agritech", "legaltech", "proptech"],
    "q_ff_role": ["cto"],
    "q_ff_offer": "Bagi hasil 20%"
  }
}
```
**DITOLAK KARENA MARUK INDUSTRI (Status `422`):**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "q_ff_ind": [
            "Anda mencentang terlalu banyak! Maksimum 5 buah pada pilihan 'Sektor Industri Startup'."
        ]
    }
}
```

Kurangin satu industri, lalu tembak ulang, dan... **YAS!!**
```json
{
  "next_step": null,
  "completed": true,
  "profile_id": 16,
  "redirect_to": "/home"
}
```
Sistem tamat! Jawaban tadi dimasak dari JSON menjadi Kolom fisikal SQL `role_category` (Startup), dan `startup_stage` (mvp) untuk `user` terkait berkat peracikan di `mapResponsesToProfile()`.

---

### Step Tambahan (Cheat Menu Frontend):
*   **Mundur 1 Langkah:** `POST {{base_url}}/api/v1/onboarding/sessions/{{session_id}}/back`
*   **Muat Ulang Form Posisi Terakhir (Resume):** `GET {{base_url}}/api/v1/onboarding/sessions/{{session_id}}/current`
*   **Narik File Transkrip Keseluruhan Session Selesai:** `GET {{base_url}}/api/v1/onboarding/sessions/{{session_id}}`
