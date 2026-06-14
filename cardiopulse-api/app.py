from flask import Flask, request, jsonify
from flask_cors import CORS
import pandas as pd
import numpy as np
import warnings

from sklearn.cluster import KMeans
from sklearn.linear_model import LinearRegression, LogisticRegression
from sklearn.tree import DecisionTreeClassifier
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split

warnings.filterwarnings("ignore")

app = Flask(__name__)
CORS(app) # Mengizinkan Laravel untuk mengakses API ini

# --- PROSES TRAINING MODEL (Dijalankan sekali saat server nyala) ---
def load_data(path="heart.csv"):
    df = pd.read_csv(path)
    if 'target' not in df.columns:
        if 'num' in df.columns:
            df.rename(columns={'num': 'target'}, inplace=True)
        elif 'disease' in df.columns:
            df.rename(columns={'disease': 'target'}, inplace=True)
    return df

def train_models(df):
    col_names = ['age','sex','cp','trestbps','chol','fbs','restecg','thalach','exang','oldpeak','slope','ca','thal']
    X = df[col_names]
    y = df['target']
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

    kmeans = KMeans(n_clusters=3, random_state=42, n_init=10).fit(df[['age','sex']])
    lr = LinearRegression().fit(df[['age']], df['trestbps'])
    dt = DecisionTreeClassifier(max_depth=4, random_state=42).fit(X_train, y_train)
    rf = RandomForestClassifier(n_estimators=100, random_state=42).fit(X_train, y_train)
    logreg = LogisticRegression(max_iter=2000).fit(X_train, y_train)

    return {
        'kmeans': kmeans, 'lr': lr, 'dt': dt, 'rf': rf, 'logreg': logreg, 'col_names': col_names
    }

# Memuat data dan melatih model secara global
print("Sedang melatih model Data Mining...")
df_global = load_data()
models = train_models(df_global)

# Untuk label KMeans berdasarkan risiko target
df_global['cluster'] = models['kmeans'].predict(df_global[['age','sex']])
risk_summary = df_global.groupby('cluster')['target'].mean().sort_values()
cluster_labels = {
    risk_summary.index[0]: 'RISIKO RENDAH',
    risk_summary.index[1]: 'RISIKO SEDANG',
    risk_summary.index[2]: 'RISIKO TINGGI'
}
print("Model selesai dilatih! Server siap menerima permintaan.")

# --- ENDPOINT API ---
@app.route('/predict', methods=['POST'])
def predict_risk():
    try:
        # Menangkap data JSON yang dikirim oleh Laravel
        data = request.json
        
        # Mengekstrak nilai inputan
        age = float(data.get('age', 55))
        sex = int(data.get('sex', 1))
        cp = int(data.get('cp', 0))
        trestbps = float(data.get('trestbps', 130))
        chol = float(data.get('chol', 240))
        fbs = int(data.get('fbs', 0))
        restecg = int(data.get('restecg', 0))
        thalach = float(data.get('thalach', 150))
        exang = int(data.get('exang', 0))
        oldpeak = float(data.get('oldpeak', 1.0))
        slope = int(data.get('slope', 0))
        ca = int(data.get('ca', 0))
        thal = int(data.get('thal', 1))

        col_names = models['col_names']
        user_df = pd.DataFrame([[age, sex, cp, trestbps, chol, fbs, restecg, thalach, exang, oldpeak, slope, ca, thal]], columns=col_names)

        # 1. Prediksi K-Means
        user_cluster = int(models['kmeans'].predict([[age, sex]])[0])
        risk_category = cluster_labels.get(user_cluster, 'UNKNOWN')

        # 2. Prediksi Regresi Linear (Tekanan Darah)
        bp_pred = float(models['lr'].predict([[age]])[0])

        # 3. Prediksi Decision Tree
        dt_pred = int(models['dt'].predict(user_df)[0])
        dt_prob = float(models['dt'].predict_proba(user_df)[0][1] * 100)
        dt_status = 'SAKIT JANTUNG' if dt_pred == 1 else 'SEHAT'

        # 4. Prediksi Random Forest
        rf_prob = float(models['rf'].predict_proba(user_df)[0][1] * 100)

        # 5. Prediksi Logistic Regression
        log_prob = float(models['logreg'].predict_proba(user_df)[0][1] * 100)

        # 6. Risiko Keseluruhan
        overall = (rf_prob + log_prob + dt_prob) / 3.0

        # Mengembalikan hasil sebagai JSON
        return jsonify({
            "status": "success",
            "data": {
                "kmeans": {
                    "cluster": user_cluster,
                    "kategori_risiko": risk_category
                },
                "prediksi_tekanan_darah_normal": round(bp_pred, 1),
                "decision_tree": {
                    "status": dt_status,
                    "probabilitas": round(dt_prob, 2)
                },
                "random_forest_probabilitas": round(rf_prob, 2),
                "logistic_regression_probabilitas": round(log_prob, 2),
                "risiko_keseluruhan": round(overall, 2)
            }
        })

    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 400

if __name__ == '__main__':
    # Menjalankan server di port 5000
    app.run(debug=True, port=5000)