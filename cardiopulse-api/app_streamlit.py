import os
import pandas as pd
import requests
import streamlit as st

# Normalisasi URL agar tidak ada double slash
API_URL = os.getenv("CARDIOPULSE_API", "http://127.0.0.1:5000").rstrip("/")

# --- PAGE CONFIG ---
st.set_page_config(
    page_title="Cardiopulse - Prediksi Risiko Jantung",
    page_icon="🫀",
    layout="wide"
)

# --- PANGGIL BACKEND (app.py) ---
def call_api(method, path, payload=None):
    url = f"{API_URL}{path}"
    try:
        r = requests.request(method, url, json=payload, timeout=15)
        
        # Cek jika HTTP Status bukan 200/Success
        if r.status_code != 200:
            return {"status": "error", "message": f"HTTP {r.status_code} saat mengakses {path}"}
            
        body = r.json()
        return body
    except requests.exceptions.ConnectionError:
        return {"status": "error", "message": "Backend belum menyala. Jalankan dulu di terminal: `python app.py`"}
    except requests.exceptions.JSONDecodeError:
        return {"status": "error", "message": "Respon backend bukan format JSON yang valid."}
    except Exception as e:
        return {"status": "error", "message": str(e)}


@st.cache_data(ttl=300)
def get_metrics():
    return call_api("GET", "/metrics")


# --- HEADER ---
st.title("🫀 Cardiopulse - Patient Health Analytics")
st.write("Sistem Prediksi Risiko Penyakit Jantung berbasis Machine Learning & Data Mining.")
st.caption("⚠️ Proyek edukasi, bukan alat diagnosis medis. Hasil tidak menggantikan pemeriksaan dokter.")
st.markdown("---")

# --- SIDEBAR INPUT ---
st.sidebar.header("📋 Input Data Medis Pasien")

age = st.sidebar.slider("Usia (age)", 1, 100, 55)
sex_label = st.sidebar.radio("Jenis Kelamin (sex)", ["Laki-laki", "Perempuan"])
sex = 1 if sex_label == "Laki-laki" else 0

cp_options = {0: "0: Typical Angina", 1: "1: Atypical Angina", 2: "2: Non-anginal Pain", 3: "3: Asymptomatic"}
cp = st.sidebar.selectbox("Tipe Nyeri Dada (cp)", options=list(cp_options.keys()), format_func=lambda x: cp_options[x])

trestbps = st.sidebar.number_input("Tekanan Darah Istirahat - mmHg (trestbps)", min_value=50, max_value=250, value=130, step=1)
chol = st.sidebar.number_input("Kolesterol Serum - mg/dl (chol)", min_value=100, max_value=600, value=240, step=1)

fbs_label = st.sidebar.selectbox("Gula Darah Puasa > 120 mg/dl (fbs)", ["Tidak", "Ya"])
fbs = 1 if fbs_label == "Ya" else 0

restecg_options = {0: "0: Normal", 1: "1: ST-T Wave Abnormality", 2: "2: Left Ventricular Hypertrophy"}
restecg = st.sidebar.selectbox("Hasil EKG Istirahat (restecg)", options=list(restecg_options.keys()), format_func=lambda x: restecg_options[x])

thalach = st.sidebar.number_input("Detak Jantung Maksimum (thalach)", min_value=60, max_value=220, value=150, step=1)

exang_label = st.sidebar.selectbox("Angina Akibat Olahraga (exang)", ["Tidak", "Ya"])
exang = 1 if exang_label == "Ya" else 0

oldpeak = st.sidebar.number_input("Depresi ST Akibat Olahraga (oldpeak)", min_value=0.0, max_value=7.0, value=1.0, step=0.1)
slope = st.sidebar.selectbox("Kemiringan Segmen ST (slope)", [0, 1, 2])
ca = st.sidebar.selectbox("Jumlah Pembuluh Darah Utama (ca)", [0, 1, 2, 3, 4])
thal = st.sidebar.selectbox("Thalassemia (thal)", [0, 1, 2, 3])

# --- PROSES PREDIKSI (Kirim ke backend) ---
payload = {
    "age": age, "sex": sex, "cp": cp, "trestbps": trestbps, "chol": chol,
    "fbs": fbs, "restecg": restecg, "thalach": thalach, "exang": exang,
    "oldpeak": oldpeak, "slope": slope, "ca": ca, "thal": thal
}

response_predict = call_api("POST", "/predict", payload)

if response_predict.get("status") != "success":
    st.error(f"Gagal memproses prediksi: {response_predict.get('message', 'Backend error')}")
    st.stop()

res = response_predict.get("data", {})

# Pengecekan Key secara Aman (.get)
overall = res.get("risiko_keseluruhan", 0.0)

kmeans_data = res.get("kmeans", {})
risk_category = kmeans_data.get("kategori_risiko", "TIDAK DIKETAHUI")

# Mendukung dua nama variabel tekanan darah (lama/baru)
bp_avg = res.get("rata_rata_tekanan_darah_usia", res.get("prediksi_tekanan_darah_normal", 120.0))

dt_data = res.get("decision_tree", {})
dt_status = dt_data.get("status", "N/A")
dt_prob = dt_data.get("probabilitas", 0.0)

rf_prob = res.get("random_forest_probabilitas", 0.0)
log_prob = res.get("logistic_regression_probabilitas", 0.0)

# Default bobot jika backend tidak mengirim bobot_model
w_default = {"Decision Tree": 0.33, "Random Forest": 0.33, "Logistic Regression": 0.34}
w = res.get("bobot_model", w_default)

# --- TAB ---
tab_pred, tab_eval = st.tabs(["📊 Prediksi", "🧪 Evaluasi Model"])

with tab_pred:
    st.subheader("Ringkasan Hasil Analisis")
    col1, col2, col3 = st.columns(3)

    with col1:
        st.metric(label="Estimasi Risiko Keseluruhan", value=f"{overall:.1f}%")
        st.progress(min(max(int(overall), 0), 100))
        st.caption("Rata-rata berbobot dari 3 model")

    with col2:
        st.metric(label="Segmen Pasien (K-Means)", value=risk_category)
        st.caption("Segmentasi demografis (usia & jenis kelamin), bukan diagnosis klinis.")

    with col3:
        st.metric(label="Rata-rata Tekanan Darah Usia Ini", value=f"{bp_avg:.1f} mmHg")
        st.caption(f"Input kamu: {trestbps} mmHg")

    st.markdown("---")

    st.subheader("Analisis Detail per Algoritma ML")
    col_a, col_b, col_c = st.columns(3)

    with col_a:
        st.info("**Decision Tree**")
        st.write(f"Status: **{dt_status}**")
        st.write(f"Probabilitas SAKIT: `{dt_prob:.2f}%`")
        st.caption(f"Bobot ensemble: {w.get('Decision Tree', 0.33):.0%}")

    with col_b:
        st.warning("**Random Forest**")
        st.write("Ensemble Classifier")
        st.write(f"Probabilitas SAKIT: `{rf_prob:.2f}%`")
        st.caption(f"Bobot ensemble: {w.get('Random Forest', 0.33):.0%}")

    with col_c:
        st.success("**Logistic Regression**")
        st.write("Statistical Classification")
        st.write(f"Probabilitas SAKIT: `{log_prob:.2f}%`")
        st.caption(f"Bobot ensemble: {w.get('Logistic Regression', 0.34):.0%}")

with tab_eval:
    m_resp = get_metrics()
    
    if m_resp.get("status") == "success":
        m = m_resp.get("data", {})
        st.subheader("Performa Model pada Data Uji")
        st.write(f"Total data: **{m.get('jumlah_data', '-')}** | Data uji: **{m.get('jumlah_data_uji', '-')}**")
        
        if "metrik" in m:
            st.dataframe(pd.DataFrame(m["metrik"]).T.round(3))
            
        st.caption("Untuk kasus medis, **Recall** penting: seberapa banyak pasien sakit yang berhasil terdeteksi. "
                   "ROC-AUC (CV) lebih stabil dibanding satu kali split karena datasetnya kecil.")
                   
        if "proporsi_sakit_per_segmen" in m:
            st.subheader("Proporsi Pasien Sakit per Segmen K-Means (data latih)")
            st.bar_chart(m["proporsi_sakit_per_segmen"])
    else:
        st.warning("⚠️ Endpoint `/metrics` belum tersedia di backend `app.py`. "
                   "Pastikan `app.py` sudah memiliki rute `@app.route('/metrics')` untuk melihat halaman evaluasi ini.")