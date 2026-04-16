# 🎯 API Contract & Postman Guide: Matchmaking System

Dokumen ini berisi panduan dan kontrak API lengkap untuk menguji fitur Matchmaking di dalam aplikasi menggunakan Postman.

## 🔑 Base Setup

- **Base URL:** `http://localhost/api/v1` (Bila Docker) atau `http://localhost:8000/api/v1` (Bila PHP Artisan Local)
- **Headers Wajib:**
    ```text
    Accept: application/json
    Content-Type: application/json
    Authorization: Bearer {token_sanctum}
    ```
    > **Catatan:** Endpoint ini dilindungi oleh otorisasi. Pastikan User Anda sudah lolos verifikasi tahap akhir (memiliki token aktif).

---

## 1. 💌 Swipe Right (Like User)

Endpoint ini digunakan ketika Anda (User aktif) menyukai (swipe right) profil orang lain.

- **Method:** `POST`
- **Endpoint:** `/matches/like`

### Request Body (JSON)

```json
{
    "to_user_id": "uuid-dari-user-target"
}
```

### Response Sukses - Belum Mutual (HTTP 200 OK)

Jika target User masih belum memberikan "Like" balik:

```json
{
    "status": "success",
    "message": "Like sent.",
    "data": {
        "like_id": "like_xxxxx",
        "to_user_id": "uuid-dari-user-target",
        "is_mutual": false
    }
}
```

### Response Sukses - It's a Mutual Match! (HTTP 200 OK)

Jika terjadi kecocokan (Target user sebelumnya ternyata sudah me-like akun Anda):

```json
{
    "status": "success",
    "message": "It's a Match!",
    "data": {
        "is_mutual": true,
        "match_id": "mtc_xxxxx",
        "conversation_id": "cnv_xxxxx"
    }
}
```

---

## 2. 📋 Get Match List & Likes Summary

Endpoint ini dipanggil untuk mengisi halaman beranda Match. Ia akan mengeluarkan "Riwayat Teman Match Anda" sekaligus menghitung "Berapa banyak orang yang ngelike kamu diam-diam".

- **Method:** `GET`
- **Endpoint:** `/matches`

### Response (HTTP 200 OK)

```json
{
    "status": "success",
    "data": {
        "matches": {
            "current_page": 1,
            "data": [
                {
                    "match_id": "mtc_83b320d0f41...",
                    "conversation_id": "cnv_910b2f...",
                    "matched_at": "2026-04-15T10:00:00.000000Z",
                    "partner_id": "uuid-partner-anda",
                    "fitSummary": null
                }
            ],
            "last_page": 1,
            "per_page": 15,
            "total": 1
        },
        "likesYouCount": 5
    }
}
```

> **Info:** Field `fitSummary` secara otomatis akan mengeluarkan object JSON Skor Kecocokan jika Background Job (Queue) sudah beres menghitung perhitungan AI-nya.

---

## 3. 🔍 Get Match Analysis Detail

Bila tampilan _User Interface_ butuh penjabaran detail alasan "Mengapa Match Ini Dinyatakan Cocok 90%", panggil endpoint ini. Note: Ini adalah hasil gawean dari background job.

- **Method:** `GET`
- **Endpoint:** `/matches/{match_id}/analysis`
- **Contoh request:** `/matches/mtc_83b320d0f41/analysis`

### Response Menggantung / Sedang Dihitung (HTTP 404 Not Found)

Bila queue lambat dan masih proses:

```json
{
    "message": "Analysis is still generating."
}
```

### Response Sukses (HTTP 200 OK)

```json
{
    "status": "success",
    "data": {
        "compatibilityScore": 85,
        "skillComplementarity": "Strong",
        "insight": "Both users show high interest in AI and matching roles.",
        "generated_at": "2026-04-15T10:05:00.000000Z"
    }
}
```

_(Kustomisasi Key-Value yang ada di dalam `data` ini akan sepenuhnya bergantung bentuk JSON yang dikelurkan algoritma Analysis Queue)_

---

## 🚀 Skenario Wajib Pengetesan di Postman (Simulasi Tinder)

Buatlah urutan tes di Postman Anda seperti ini:

1. **Siapkan 2 Token Login** untuk Akun (A) dan Akun (B).
2. Dari Akun A, tembak `/matches/like`, kirim Body JSON `to_user_id` milik Akun B.
   _(Ekspektasi: Muncul "Like sent" dan is_mutual: false)_
3. Ganti Token Auth menjadi milik **Akun B**. Tembak endpoint `/matches/like`, lalu arahkan `to_user_id` milik Akun A.
   _(Ekspektasi: Muncul "It's a Match!" dan kalian mendapat `match_id`)_
4. Dengan token siapapun (A atau B), jalan ke endpoint `/matches` (GET).
   _(Ekspektasi: Data match tadi muncul di dalam array data)_
5. Tes fitur proteksi sekuritas: Copy `match_id` yang terbentuk, lalu buka tab Postman baru untuk endpoint `GET /matches/nomor_match/analysis`. Pakaikan token **Akun C** (Akun asing).
   _(Ekspektasi: Harus muncul error HTTP 403 / "Unauthorized access to this match" untuk menguji data tidak bocor)_
