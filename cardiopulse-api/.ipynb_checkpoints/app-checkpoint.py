from pathlib import Path
import warnings

import pandas as pd
from flask import Flask, request, jsonify
from flask_cors import CORS

from sklearn.cluster import KMeans
from sklearn.linear_model import LinearRegression, LogisticRegression
from sklearn.tree import DecisionTreeClassifier
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.pipeline import make_pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import (accuracy_score, precision_score, recall_score,
                             f1_score, roc_auc_score)

warnings.filterwarnings("ignore")

app = Flask(__name__)
CORS(app)

COL_NAMES = ['age', 'sex', 'cp', 'trestbps', 'chol', 'fbs', 'restecg',
             'thalach', 'exang', 'oldpeak', 'slope', 'ca', 'thal']
RISK_LABELS = ['RISIKO RENDAH', 'RISIKO SEDANG', 'RISIKO TINGGI']
RANDOM_STATE = 42

# Batas nilai wajar tiap fitur (min, max) untuk validasi input
RANGES = {'age': (1, 100), 'sex': (0, 1), 'cp': (0, 3), 'trestbps': (50, 250),
          'chol': (100, 600), 'fbs': (0, 1), 'restecg': (0, 2), 'thalach': (60, 220),
          'exang': (0, 1), 'oldpeak': (0, 7), 'slope': (0, 2), 'ca': (0, 4), 'thal': (0, 3)}


def load_data(path=None):
    here = Path(__file__).parent
    candidates = [Path(path)] if path else [here / "heart.csv", here.parent / "heart.csv", Path("heart.csv")]
    csv = next((p for p in candidates if p.exists()), None)
    if csv is None:
        raise FileNotFoundError("heart.csv tidak ditemukan. Taruh di folder yang sama dengan app.py")

    df = pd.read_csv(csv, na_values=["?"])
    for old in ("num", "disease"):
        if 'target' not in df.columns and old in df.columns:
            df = df.rename(columns={old: 'target'})

    df = df.dropna(subset=COL_NAMES + ['target']).copy()
    # Dataset UCI asli punya label 0-4. Dibuat biner: 0 = sehat, 1 = sakit.
    df['target'] = (df['target'] > 0).astype(int)
    return df


def train_models(df):
    X, y = df[COL_NAMES], df['target']
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=RANDOM_STATE, stratify=y)

    classifiers = {
        'Decision Tree': DecisionTreeClassifier(max_depth=4, min_samples_leaf=5, random_state=RANDOM_STATE),
        'Random Forest': RandomForestClassifier(n_estimators=200, min_samples_leaf=3, random_state=RANDOM_STATE),
        # Logistic Regression butuh scaling, jadi dibungkus pipeline
        'Logistic Regression': make_pipeline(StandardScaler(), LogisticRegression(max_iter=2000)),
    }

    # Evaluasi pada data uji + cross-validation pada data latih
    rows = []
    for name, model in classifiers.items():
        cv_auc = cross_val_score(model, X_train, y_train, cv=5, scoring='roc_auc').mean()
        model.fit(X_train, y_train)
        pred = model.predict(X_test)
        proba = model.predict_proba(X_test)[:, 1]
        rows.append({
            'Model': name,
            'Accuracy': accuracy_score(y_test, pred),
            'Precision': precision_score(y_test, pred),
            'Recall': recall_score(y_test, pred),
            'F1': f1_score(y_test, pred),
            'ROC-AUC (test)': roc_auc_score(y_test, proba),
            'ROC-AUC (CV 5-fold)': cv_auc,
        })
    metrics = pd.DataFrame(rows).set_index('Model')

    # Bobot ensemble sesuai ROC-AUC cross-validation (model lebih baik = bobot lebih besar)
    w = metrics['ROC-AUC (CV 5-fold)']
    weights = (w / w.sum()).to_dict()

    # K-Means dilatih hanya pada data latih (usia & jenis kelamin), dengan scaling
    kmeans = make_pipeline(StandardScaler(), KMeans(n_clusters=3, random_state=RANDOM_STATE, n_init=10))
    kmeans.fit(X_train[['age', 'sex']])
    clusters = kmeans.predict(X_train[['age', 'sex']])
    rate = y_train.groupby(clusters).mean().sort_values()  # proporsi sakit per klaster
    cluster_labels = {int(c): RISK_LABELS[i] for i, c in enumerate(rate.index)}
    cluster_rates = {cluster_labels[int(c)]: float(r) for c, r in rate.items()}

    # Rata-rata tekanan darah menurut usia (deskriptif, bukan standar "normal" medis)
    lr = LinearRegression().fit(df[['age']], df['trestbps'])

    return {
        'classifiers': classifiers, 'weights': weights, 'metrics': metrics,
        'kmeans': kmeans, 'cluster_labels': cluster_labels, 'cluster_rates': cluster_rates,
        'lr': lr, 'n_samples': len(df), 'n_test': len(y_test),
    }


print("Sedang melatih model Data Mining...")
bundle = train_models(load_data())
print("Model selesai dilatih! Server siap menerima permintaan.")


@app.route('/predict', methods=['POST'])
def predict_risk():
    data = request.get_json(silent=True) or {}

    # Semua field wajib diisi (tidak ada nilai default diam-diam)
    missing = [f for f in COL_NAMES if f not in data]
    if missing:
        return jsonify({"status": "error", "message": f"Field wajib belum diisi: {missing}"}), 400

    try:
        patient = {f: float(data[f]) for f in COL_NAMES}
    except (TypeError, ValueError):
        return jsonify({"status": "error", "message": "Semua field harus berupa angka"}), 400

    out_of_range = [f for f, (lo, hi) in RANGES.items() if not lo <= patient[f] <= hi]
    if out_of_range:
        return jsonify({"status": "error", "message": f"Nilai di luar rentang wajar: {out_of_range}"}), 400

    user_df = pd.DataFrame([patient])[COL_NAMES]

    # 1. K-Means (segmentasi berdasarkan usia & jenis kelamin)
    user_cluster = int(bundle['kmeans'].predict(user_df[['age', 'sex']])[0])
    risk_category = bundle['cluster_labels'].get(user_cluster, 'UNKNOWN')

    # 2. Regresi Linear (rata-rata tekanan darah menurut usia)
    bp_pred = float(bundle['lr'].predict(user_df[['age']])[0])

    # 3-5. Probabilitas tiap classifier (dalam persen)
    probs = {name: float(m.predict_proba(user_df)[0][1] * 100)
             for name, m in bundle['classifiers'].items()}
    dt_status = 'SAKIT JANTUNG' if probs['Decision Tree'] >= 50 else 'SEHAT'

    # 6. Risiko keseluruhan: rata-rata berbobot
    overall = sum(probs[n] * bundle['weights'][n] for n in probs)

    return jsonify({
        "status": "success",
        "data": {
            "kmeans": {"cluster": user_cluster, "kategori_risiko": risk_category},
            "rata_rata_tekanan_darah_usia": round(bp_pred, 1),
            "decision_tree": {"status": dt_status, "probabilitas": round(probs['Decision Tree'], 2)},
            "random_forest_probabilitas": round(probs['Random Forest'], 2),
            "logistic_regression_probabilitas": round(probs['Logistic Regression'], 2),
            "risiko_keseluruhan": round(overall, 2),
            "bobot_model": {k: round(v, 4) for k, v in bundle['weights'].items()},
        }
    })


@app.route('/metrics', methods=['GET'])
def get_metrics():
    return jsonify({
        "status": "success",
        "data": {
            "metrik": bundle['metrics'].round(4).to_dict(orient='index'),
            "proporsi_sakit_per_segmen": bundle['cluster_rates'],
            "jumlah_data": bundle['n_samples'],
            "jumlah_data_uji": bundle['n_test'],
        }
    })


if __name__ == '__main__':
    app.run(debug=False, use_reloader=False, port=5000)