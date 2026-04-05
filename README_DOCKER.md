# 🚗 Sistem Parkir Cerdas — Docker Setup

## Syarat
- Docker Desktop sudah terinstall dan berjalan
- (Download di: https://www.docker.com/products/docker-desktop)

---

## ▶️ Cara Menjalankan (Cukup 2 Langkah!)

### Langkah 1 — Buka Terminal / Command Prompt
Masuk ke folder project ini (yang ada `docker-compose.yml`-nya):
```
cd path/ke/folder/parkir_project
```

### Langkah 2 — Jalankan Docker
```
docker compose up --build
```

Tunggu sampai muncul tulisan seperti ini:
```
parkir_python_api  | INFO:     Application startup complete.
parkir_ci3_web     | AH00558: apache2 ...
```

---

## 🌐 Akses Aplikasi

| Halaman | URL |
|---------|-----|
| **Dashboard Parkir (CI3)** | http://localhost:8080 |
| **API Docs (FastAPI)** | http://localhost:8000/docs |

---

## ⏹️ Cara Matikan

```
docker compose down
```

---

## 🔁 Kalau Ada Perubahan File

```
docker compose up --build
```
(flag `--build` memastikan Docker rebuild ulang imagenya)

---

## ❓ Troubleshooting

**Dashboard tampil tapi prediksi gagal?**
→ Pastikan container `parkir_python_api` sudah jalan.
→ Cek di Docker Desktop tab "Containers", pastikan keduanya status ✅ Running.

**Port sudah dipakai?**
→ Edit `docker-compose.yml`, ganti port di bagian `ports`:
  - CI3: `"9090:80"` → akses di localhost:9090
  - Python: `"8001:8000"` → akses di localhost:8001
