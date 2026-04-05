# Sistem Parkir Cerdas (CI3 + Python ML)

## Arsitektur

```
[Browser]
    ↕ HTTP
[CI3 Frontend - dashboard.php]   ← tampil UI, form input
    ↕ cURL (POST /dashboard)
[FastAPI Python - api.py]        ← Random Forest prediction
    ↕ joblib
[model_rf_mobil.pkl, model_rf_motor.pkl]
```

## Cara Menjalankan

### 1. Jalankan Python API (FastAPI)

```bash
cd parkir_ai/

# Install dependencies (sekali saja)
pip install fastapi uvicorn joblib scikit-learn pandas

# Jalankan server
uvicorn api:app --reload --port 8000
```

> API akan berjalan di: http://127.0.0.1:8000
> Dokumentasi otomatis: http://127.0.0.1:8000/docs

### 2. Jalankan CI3 (PHP)

```bash
cd parkir_ci3/

# Jika pakai PHP built-in server:
php -S localhost:8080 index.php

# Atau taruh di htdocs/www XAMPP/WAMP
```

> Akses di: http://localhost:8080

---

## Endpoint Python API

| Method | URL | Keterangan |
|--------|-----|-----------|
| POST | `/dashboard` | **Utama** — data lengkap untuk frontend (grafik + stats + prediksi jam) |
| POST | `/predict` | Prediksi 1 titik (hari + jam) |
| GET | `/predict_full?hari=senin` | Prediksi 24 jam untuk grafik |
| GET | `/stats?hari=senin` | Statistik harian |

### Contoh request `/dashboard`:
```json
POST http://127.0.0.1:8000/dashboard
{
  "hari": "senin",
  "jam": 8
}
```

### Contoh response:
```json
{
  "status": "success",
  "hari": "Senin",
  "jam_label": "08:00 WIB",
  "mobil": 12,
  "motor": 47,
  "total": 59,
  "chart_labels": ["00:00", "01:00", ...],
  "chart_mobil": [3, 2, ...],
  "chart_motor": [8, 5, ...],
  "total_hari_ini": 850,
  "total_mobil_hari": 210,
  "total_motor_hari": 640,
  "jam_puncak": "08:00 WIB",
  "rata_rata": 35,
  "rekomendasi": "Jam 08:00 adalah jam PUNCAK..."
}
```

---

## File yang Diubah

| File | Perubahan |
|------|-----------|
| `parkir_ai/api.py` | + endpoint `/dashboard` (gabungan semua data) |
| `parkir_ci3/application/controllers/Parkir.php` | Buang mock data → call Python API via cURL |
| `parkir_ci3/application/views/dashboard.php` | Sesuaikan field PHP & JS dengan response API Python |
| `parkir_ci3/application/config/routes.php` | Default controller → `parkir` |
