import streamlit as st
import pandas as pd
import numpy as np
import warnings

from sklearn.cluster import KMeans
from sklearn.linear_model import LinearRegression, LogisticRegression
from sklearn.tree import DecisionTreeClassifier
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split

warnings.filterwarnings("ignore")

# --- PAGE CONFIG ---
st.set_page_config(
    page_title="Cardiopulse - Prediksi Risiko Jantung",
    page_icon="🫀",
    layout="wide"
)

# --- LOAD DATA & TRAIN MODELS ---
@st.cache_resource
def init_models():
    try:
        df = pd.read_csv("heart.csv")
    except FileNotFoundError:
        df = pd.read_csv("../heart.csv")
        
    if 'target' not in df.columns:
        if 'num' in df.columns:
            df.rename(columns={'num': 'target'}, inplace=True)
        elif 'disease' in df.columns:
            df.rename(columns={'disease': 'target'}, inplace=True)

    col_names = ['age','sex','cp','trestbps','chol','fbs','restecg','thalach','exang','oldpeak','slope','ca','thal']
    X = df[col_names]
    y = df['target']
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

    kmeans = KMeans(n_clusters=3, random_state=42, n_init=10).fit(df[['age','sex']])
    lr = LinearRegression().fit(df[['age']], df['trestbps'])
    dt = DecisionTreeClassifier(max_depth=4, random_state=42).fit(X_train, y_train)
    rf = RandomForestClassifier(n_estimators=100, random_state=42).fit(X_train, y_train)
    logreg = LogisticRegression(max_iter=2000).fit(X_train, y_train)

    df['cluster'] = kmeans.predict(df[['age','sex']])
    risk_summary = df.groupby('cluster')['target'].mean().sort_values()
    cluster_labels = {
        risk_summary.index[0]: 'RISIKO RENDAH',
        risk_summary.index[1]: 'RISIKO SEDANG',
        risk_summary.index[2]: 'RISIKO TINGGI'
    }

    return {
        'kmeans': kmeans, 'lr': lr, 'dt': dt, 'rf': rf, 'logreg': logreg, 
        'col_names': col_names, 'cluster_labels': cluster_labels
    }

models = init_models()

# --- HEADER ---
st.title("🫀 Cardiopulse - Patient Health Analytics")
st.write("Sistem Diagnosa Prediksi Risiko Penyakit Jantung berbasis Machine Learning & Data Mining.")
st.markdown("---")

# --- SIDEBAR INPUT ---
st.sidebar.header("📋 Input Data Medis Pasien")

age = st.sidebar.slider("Usia (age)", 1, 100, 55)
sex_label = st.sidebar.radio("Jenis Kelamin (sex)", ["Laki-laki", "Perempuan"])
sex = 1 if sex_label == "Laki-laki" else 0

cp_options = {0: "0: Typical Angina", 1: "1: Atypical Angina", 2: "2: Non-anginal Pain", 3: "3: Asymptomatic"}
cp = st.sidebar.selectbox("Tipe Nyeri Dada (cp)", options=list(cp_options.keys()), format_func=lambda x: cp_options[x])

trestbps = st.sidebar.number_input("Tekanan Darah Istirahat - mmHg (trestbps)", value=130, step=1)
chol = st.sidebar.number_input("Kolesterol Serum - mg/dl (chol)", value=240, step=1)

fbs_label = st.sidebar.selectbox("Gula Darah Puasa > 120 mg/dl (fbs)", ["Tidak", "Ya"])
fbs = 1 if fbs_label == "Ya" else 0

restecg_options = {0: "0: Normal", 1: "1: ST-T Wave Abnormality", 2: "2: Left Ventricular Hypertrophy"}
restecg = st.sidebar.selectbox("Hasil EKG Istirahat (restecg)", options=list(restecg_options.keys()), format_func=lambda x: restecg_options[x])

thalach = st.sidebar.number_input("Detak Jantung Maksimum (thalach)", value=150, step=1)

exang_label = st.sidebar.selectbox("Angina Akibat Olahraga (exang)", ["Tidak", "Ya"])
exang = 1 if exang_label == "Ya" else 0

oldpeak = st.sidebar.number_input("Depresi ST Akibat Olahraga (oldpeak)", value=1.0, step=0.1)
slope = st.sidebar.selectbox("Kemiringan Segmen ST (slope)", [0, 1, 2])
ca = st.sidebar.selectbox("Jumlah Pembuluh Darah Utama (ca)", [0, 1, 2, 3, 4])
thal = st.sidebar.selectbox("Thalassemia (thal)", [0, 1, 2, 3])

# --- PROSES PREDIKSI ---
col_names = models['col_names']
user_df = pd.DataFrame([[age, sex, cp, trestbps, chol, fbs, restecg, thalach, exang, oldpeak, slope, ca, thal]], columns=col_names)

user_cluster = int(models['kmeans'].predict([[age, sex]])[0])
risk_category = models['cluster_labels'].get(user_cluster, 'UNKNOWN')

bp_pred = float(models['lr'].predict([[age]])[0])

dt_pred = int(models['dt'].predict(user_df)[0])
dt_prob = float(models['dt'].predict_proba(user_df)[0][1] * 100)
dt_status = 'SAKIT JANTUNG' if dt_pred == 1 else 'SEHAT'

rf_prob = float(models['rf'].predict_proba(user_df)[0][1] * 100)
log_prob = float(models['logreg'].predict_proba(user_df)[0][1] * 100)

overall = (rf_prob + log_prob + dt_prob) / 3.0

# --- HASIL UTAMA ---
st.subheader("📊 Ringkasan Hasil Analisis")
col1, col2, col3 = st.columns(3)

with col1:
    st.metric(label="Estimasi Risiko Keseluruhan", value=f"{overall:.1f}%")
    st.progress(int(overall))

with col2:
    st.metric(label="Kategori Klaster Pasien (K-Means)", value=risk_category)

with col3:
    st.metric(label="Prediksi Tekanan Darah Normal (Usia)", value=f"{bp_pred:.1f} mmHg")

st.markdown("---")

# --- DETAIL MODEL ---
st.subheader("🤖 Analisis Detail per Algoritma ML")
col_a, col_b, col_c = st.columns(3)

with col_a:
    st.info("**Decision Tree**")
    st.write(f"Status: **{dt_status}**")
    st.write(f"Probabilitas SAKIT: `{dt_prob:.2f}%`")

with col_b:
    st.warning("**Random Forest**")
    st.write("Ensemble Classifier")
    st.write(f"Probabilitas SAKIT: `{rf_prob:.2f}%`")

with col_c:
    st.success("**Logistic Regression**")
    st.write("Statistical Classification")
    st.write(f"Probabilitas SAKIT: `{log_prob:.2f}%`")