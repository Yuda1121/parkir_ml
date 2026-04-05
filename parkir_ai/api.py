from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
import joblib
import pandas as pd
import os

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

# Load semua model
rf_mobil  = joblib.load(os.path.join(BASE_DIR, "model_rf_mobil.pkl"))
rf_motor  = joblib.load(os.path.join(BASE_DIR, "model_rf_motor.pkl"))
svr_mobil = joblib.load(os.path.join(BASE_DIR, "model_svr_mobil.pkl"))
svr_motor = joblib.load(os.path.join(BASE_DIR, "model_svr_motor.pkl"))

# Metrik evaluasi dari training (hardcoded sesuai hasil training kamu)
METRICS = {
    "rf": {
        "mobil": {"mae": 1.33, "rmse": 1.75, "r2": 0.567},
        "motor": {"mae": 3.52, "rmse": 4.96, "r2": 0.322},
    },
    "svr": {
        "mobil": {"mae": 1.72, "rmse": 2.49, "r2": 0.126},
        "motor": {"mae": 4.77, "rmse": 5.40, "r2": 0.198},
    }
}

hari_map = {
    "senin": 0, "selasa": 1, "rabu": 2, "kamis": 3,
    "jumat": 4, "sabtu": 5, "minggu": 6
}

@app.post("/dashboard")
def dashboard(data: dict):
    try:
        hari = data["hari"].lower()
        jam  = int(data["jam"])

        if hari not in hari_map:
            return {"status": "error", "message": "Hari tidak valid"}
        if jam < 0 or jam > 23:
            return {"status": "error", "message": "Jam harus 0-23"}

        day = hari_map[hari]

        labels = []
        rf_chart_mobil,  rf_chart_motor  = [], []
        svr_chart_mobil, svr_chart_motor = [], []
        rf_totals, svr_totals = [], []

        for j in range(24):
            X = pd.DataFrame([[j, day]], columns=['hour', 'day'])

            rm = max(0, round(rf_mobil.predict(X)[0]))
            rmo = max(0, round(rf_motor.predict(X)[0]))
            sm = max(0, round(svr_mobil.predict(X)[0]))
            smo = max(0, round(svr_motor.predict(X)[0]))

            labels.append(f"{j:02d}:00")
            rf_chart_mobil.append(rm)
            rf_chart_motor.append(rmo)
            svr_chart_mobil.append(sm)
            svr_chart_motor.append(smo)
            rf_totals.append(rm + rmo)
            svr_totals.append(sm + smo)

        # Prediksi jam terpilih
        rf_sel_mobil  = rf_chart_mobil[jam]
        rf_sel_motor  = rf_chart_motor[jam]
        svr_sel_mobil = svr_chart_mobil[jam]
        svr_sel_motor = svr_chart_motor[jam]

        # Statistik RF
        rf_total_all   = sum(rf_totals)
        rf_peak_jam    = rf_totals.index(max(rf_totals))

        # Statistik SVR
        svr_total_all  = sum(svr_totals)
        svr_peak_jam   = svr_totals.index(max(svr_totals))

        # Rekomendasi pakai RF (lebih akurat R2 lebih tinggi)
        if rf_peak_jam == jam:
            rekomendasi = f"Jam {jam:02d}:00 adalah jam PUNCAK di hari {hari.capitalize()}. Siapkan petugas tambahan."
        elif abs(rf_peak_jam - jam) <= 1:
            rekomendasi = f"Menjelang/setelah jam puncak ({rf_peak_jam:02d}:00). Pantau kepadatan."
        elif hari in ["sabtu", "minggu"]:
            rekomendasi = f"Hari {hari.capitalize()} cenderung lebih ramai. Puncak di jam {rf_peak_jam:02d}:00."
        else:
            rekomendasi = f"Kondisi parkir hari {hari.capitalize()} terkendali. Puncak diperkirakan pukul {rf_peak_jam:02d}:00 WIB."

        return {
            "status":   "success",
            "hari":     hari.capitalize(),
            "jam_label": f"{jam:02d}:00 WIB",
            "chart_labels": labels,

            # RF data
            "rf": {
                "mobil":        rf_sel_mobil,
                "motor":        rf_sel_motor,
                "total":        rf_sel_mobil + rf_sel_motor,
                "chart_mobil":  rf_chart_mobil,
                "chart_motor":  rf_chart_motor,
                "total_hari":   rf_total_all,
                "total_mobil":  sum(rf_chart_mobil),
                "total_motor":  sum(rf_chart_motor),
                "jam_puncak":   f"{rf_peak_jam:02d}:00 WIB",
                "rata_rata":    round(rf_total_all / 24),
                "metrics":      METRICS["rf"],
            },

            # SVR data
            "svr": {
                "mobil":        svr_sel_mobil,
                "motor":        svr_sel_motor,
                "total":        svr_sel_mobil + svr_sel_motor,
                "chart_mobil":  svr_chart_mobil,
                "chart_motor":  svr_chart_motor,
                "total_hari":   svr_total_all,
                "total_mobil":  sum(svr_chart_mobil),
                "total_motor":  sum(svr_chart_motor),
                "jam_puncak":   f"{svr_peak_jam:02d}:00 WIB",
                "rata_rata":    round(svr_total_all / 24),
                "metrics":      METRICS["svr"],
            },

            "rekomendasi": rekomendasi,
        }

    except Exception as e:
        return {"status": "error", "message": str(e)}


@app.post("/predict")
def predict(data: dict):
    try:
        hari = data["hari"].lower()
        jam  = int(data["jam"])
        if hari not in hari_map:
            return {"status": "error", "message": "Hari tidak valid"}
        X = pd.DataFrame([[jam, hari_map[hari]]], columns=['hour', 'day'])
        return {
            "status": "success",
            "rf":  {"mobil": round(rf_mobil.predict(X)[0]),  "motor": round(rf_motor.predict(X)[0])},
            "svr": {"mobil": round(svr_mobil.predict(X)[0]), "motor": round(svr_motor.predict(X)[0])},
        }
    except Exception as e:
        return {"status": "error", "message": str(e)}


@app.get("/metrics")
def get_metrics():
    return {"status": "success", "metrics": METRICS}
