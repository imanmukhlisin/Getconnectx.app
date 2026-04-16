# Panduan Ngetes API Onboarding (Live Action 6 Flow)

Dokumen ini adalah panduan lengkap untuk mengetes **Dynamic Onboarding Engine** sesuai dengan `OnboardingSeeder` dan `Dokumen-contractapi-onbording.md`.

## 🔑 Persiapan
- **Base URL:** `{{base_url}}/api/v1`
- **Auth:** `Bearer Token` (Dapatkan dari Login atau OTP Verify).

---

## 🏎️ Step 1: Start Onboarding Session
Mulai sesi baru untuk mendapatkan `session_id`.
- **Method:** `POST`
- **URL:** `/onboarding/sessions`
- **Response:** `{ "session_id": "ses_abc123", ... }`

---

## 📍 Step 2: Jalur Umum (Flow Common)
Isi data diri dasar sebelum masuk ke percabangan peran.

### 2.1 Nama Lengkap (`step_personal_name`)
```json
{
  "step_id": "step_personal_name",
  "answers": {
    "q_first_name": "Antigravity",
    "q_last_name": "AI"
  }
}
```

### 2.2 Tanggal Lahir (`step_personal_dob`)
```json
{
  "step_id": "step_personal_dob",
  "answers": {
    "q_dob": "1995-05-20"
  }
}
```

### 2.3 Lokasi (`step_personal_location`)
```json
{
  "step_id": "step_personal_location",
  "answers": {
    "q_location": "jakarta",
    "q_open_remote": "yes",
    "q_remote_pref": "hybrid"
  }
}
```
> **Note:** `q_remote_pref` hanya wajib dikirim jika `q_open_remote` bernilai `yes`.

### 2.4 Gender & Role Selection
Pilih peran Anda untuk menentukan arah flow selanjutnya.
```json
{
  "step_id": "step_personal_gender",
  "answers": {
    "q_gender": "male"
  }
}
```
*Kemudian dilanjut ke:*
```json
{
  "step_id": "step_role_selection",
  "answers": {
    "q_use_connectx": "founder" 
  }
}
```
**Value `q_use_connectx` menentukan branching:**
- `startup` -> Langsung ke **Flow F (Profil Startup)**.
- `founder`, `cofounder`, `team` -> Masuk ke **Flow Builder Common**.

---

## 🏗️ Step 3: Jalur Builder (Flow Builder Common)
Hanya untuk peran Founder, Co-Founder, atau Team Member.

### 3.1 Pengalaman & Industri (`step_bld_exp` & `step_bld_industry`)
```json
{
  "step_id": "step_bld_industry",
  "answers": {
    "q_industry": ["AI/ML", "Fintech"],
    "q_availability": "full_time",
    "q_relocate": "yes"
  }
}
```

### 3.2 Role Detail (`step_bld_role`)
```json
{
  "step_id": "step_bld_role",
  "answers": {
    "q_role_desc": "cto",
    "q_role_years": 5,
    "q_linkedin": "https://linkedin.com/in/user"
  }
}
```

---

## 🚦 Step 4: Branching Spesifik (Flow A - F)
Berdasarkan jawaban di langkah sebelumnya, Anda akan diarahkan ke salah satu flow berikut:

### Contoh Flow F: Startup Profile (`step_flow_f`)
Dipicu jika `q_use_connectx` = `startup`.
```json
{
  "step_id": "step_flow_f",
  "answers": {
    "q_ff_name": "ConnectX Tech",
    "q_ff_stage": "mvp",
    "q_ff_look": "cofounder",
    "q_ff_ind": ["SaaS", "AI/ML"],
    "q_ff_role": ["ceo", "cmo"],
    "q_ff_offer": "Salary + 5% Equity"
  }
}
```

### Contoh Flow A: Founder mencari Co-Founder (`step_flow_a`)
Dipicu jika `founder` memilih `q_founder_intent` = `cofounder`.
```json
{
  "step_id": "step_flow_a",
  "answers": {
    "q_flow_a_type": ["tech", "business"]
  }
}
```

---

## 🏁 Step 5: Finish & Redirect
Jika langkah terakhir sudah terpenuhi, API akan menjawab:
```json
{
  "next_step": null,
  "completed": true,
  "profile_id": "uuid-anda",
  "redirect_to": "/home"
}
```

---

## 🛠️ Fitur Tambahan (Control Session)
- **Mundur Langkah:** `POST /onboarding/sessions/{{session_id}}/back`
- **Resume (Ambil posisi terakhir):** `GET /onboarding/sessions/{{session_id}}/current`
- **Status Lengkap:** `GET /onboarding/sessions/{{session_id}}`

---

## 📂 Penanganan Media (Upload GCS)
Jika ada pertanyaan yang membutuhkan file (misal: Pitch Deck), gunakan flow pre-signed URL:

1. **Get Upload URL**: `POST /media/upload-url` dengan body `{"file_name": "test.pdf", "mime_type": "application/pdf"}`.
2. **PUT to GCS**: Tembak file fisik ke `upload_url` menggunakan method `PUT` dan header `Content-Type`.
3. **Submit Answer**: Kirim nilai `file_url` ke endpoint `/answer`.
