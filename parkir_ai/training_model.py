import pandas as pd
import numpy as np
import joblib

from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestRegressor
from sklearn.svm import SVR
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.preprocessing import StandardScaler

# =========================
# LOAD DATA
# =========================
df = pd.read_excel("DATA IN DAN OUT (1).xlsx")

# =========================
# PREPROCESSING
# =========================
df['JAM MASUK'] = pd.to_datetime(df['JAM MASUK'], errors='coerce')
df = df.dropna(subset=['JAM MASUK'])

df['hour'] = df['JAM MASUK'].dt.hour
df['day'] = df['JAM MASUK'].dt.dayofweek  # 0=Senin

hari_map = {
    0: "Senin",
    1: "Selasa",
    2: "Rabu",
    3: "Kamis",
    4: "Jumat",
    5: "Sabtu",
    6: "Minggu"
}

# =========================
# AGREGASI (RATA-RATA HARIAN)
# =========================
# Tambahkan kolom tanggal (date) untuk memisahkan setiap hari unik
df['date'] = df['JAM MASUK'].dt.date

# Hitung jumlah kendaraan per JENIS per jam per hari unik
agg_data = df.groupby(['date', 'day', 'hour', 'JENIS']).size().unstack(fill_value=0).reset_index()

# Pastikan kolom MOBIL dan MOTOR ada
for col in ['MOBIL', 'MOTOR']:
    if col not in agg_data.columns:
        agg_data[col] = 0

# Hitung rata-rata per (day, hour) dari seluruh tanggal yang tersedia
data = agg_data.groupby(['day', 'hour'])[['MOBIL', 'MOTOR']].mean().reset_index()

# Bulatkan rata-rata agar menjadi angka utuh
data['MOBIL'] = data['MOBIL'].round()
data['MOTOR'] = data['MOTOR'].round()
data['total'] = data['MOBIL'] + data['MOTOR']

# =========================
# FEATURE
# =========================
X = data[['hour','day']]
y_mobil = data['MOBIL']
y_motor = data['MOTOR']

# =========================
# SPLIT DATA
# =========================
X_train, X_test, y_mobil_train, y_mobil_test, y_motor_train, y_motor_test = train_test_split(
    X, y_mobil, y_motor, test_size=0.2, random_state=42
)

# =========================
# RANDOM FOREST
# =========================
rf_mobil = RandomForestRegressor(n_estimators=150, max_depth=10, random_state=42)
rf_motor = RandomForestRegressor(n_estimators=150, max_depth=10, random_state=42)

rf_mobil.fit(X_train, y_mobil_train)
rf_motor.fit(X_train, y_motor_train)

rf_mobil_pred = rf_mobil.predict(X_test)
rf_motor_pred = rf_motor.predict(X_test)

# =========================
# SVR (MOBIL & MOTOR)
# =========================
scaler = StandardScaler()

X_train_scaled = scaler.fit_transform(X_train)
X_test_scaled = scaler.transform(X_test)

svr_mobil = SVR(kernel='rbf', C=100, gamma=0.1)
svr_motor = SVR(kernel='rbf', C=100, gamma=0.1)

svr_mobil.fit(X_train_scaled, y_mobil_train)
svr_motor.fit(X_train_scaled, y_motor_train)

svr_mobil_pred = svr_mobil.predict(X_test_scaled)
svr_motor_pred = svr_motor.predict(X_test_scaled)

# =========================
# EVALUASI
# =========================
def evaluate(name, y_true, y_pred):
    print(f"\n=== {name} ===")
    print("MAE :", round(mean_absolute_error(y_true, y_pred), 2))
    print("RMSE:", round(np.sqrt(mean_squared_error(y_true, y_pred)), 2))
    print("R2  :", round(r2_score(y_true, y_pred), 3))

# RF
evaluate("RF Mobil", y_mobil_test, rf_mobil_pred)
evaluate("RF Motor", y_motor_test, rf_motor_pred)

# SVR
evaluate("SVR Mobil", y_mobil_test, svr_mobil_pred)
evaluate("SVR Motor", y_motor_test, svr_motor_pred)

# =========================
# TEST PREDIKSI
# =========================
print("\n=== TEST PREDIKSI ===")

input_day = 4   # Jumat
input_hour = 14

contoh = pd.DataFrame([[input_hour, input_day]], columns=['hour','day'])

# RF
mobil_rf = round(rf_mobil.predict(contoh)[0])
motor_rf = round(rf_motor.predict(contoh)[0])

# SVR
mobil_svr = round(svr_mobil.predict(scaler.transform(contoh))[0])
motor_svr = round(svr_motor.predict(scaler.transform(contoh))[0])

print(f"Prediksi {hari_map[input_day]} pukul {input_hour:02d}:00–{input_hour:02d}:59:\n")

print("=== Random Forest ===")
print("Mobil :", mobil_rf)
print("Motor :", motor_rf)
print("Total :", mobil_rf + motor_rf)

print("\n=== SVR ===")
print("Mobil :", mobil_svr)
print("Motor :", motor_svr)
print("Total :", mobil_svr + motor_svr)

# =========================
# SIMPAN MODEL
# =========================
joblib.dump(rf_mobil, "model_rf_mobil.pkl")
joblib.dump(rf_motor, "model_rf_motor.pkl")
joblib.dump(svr_mobil, "model_svr_mobil.pkl")
joblib.dump(svr_motor, "model_svr_motor.pkl")
joblib.dump(scaler, "scaler.pkl")

print("\nModel berhasil disimpan!")