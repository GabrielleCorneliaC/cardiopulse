# 🫀 Cardiopulse - Health Analytics & Heart Risk Prediction

Aplikasi web dashboard berbasis Machine Learning untuk melakukan analisa serta prediksi risiko penyakit jantung berdasarkan data medis pasien.

---

### 📊 Fitur Utama & Algoritma
- **Streamlit Dashboard**: Antarmuka interaktif untuk memasukkan indikator kesehatan pasien dan melihat hasil prediksi secara langsung.
- **K-Means Clustering**: Pengelompokan pasien berdasarkan kategori risiko (*Risiko Rendah, Sedang, Tinggi*).
- **Regresi Linear**: Estimasi tekanan darah normal berdasarkan usia pasien.
- **Decision Tree, Random Forest, & Logistic Regression**: Mendiagnosis status kesehatan jantung dan menghitung persentase probabilitas risiko.

---

### 📁 Struktur File Utama
- `app_streamlit.py` : Kode utama untuk menjalankan web dashboard berbasis Streamlit.
- `heart.csv` : Dataset indikator kesehatan jantung yang digunakan untuk melatih model ML.
- `requirements.txt` : Daftar library Python yang dibutuhkan.
- `app.py` : *(Opsional)* Backend Flask API jika ingin menarik data raw JSON tanpa antarmuka UI.

---

### 🚀 Cara Menjalankan Aplikasi

1. **Clone Repository**:
   ```bash
   git clone https://github.com/GabrielleCorneliaC/cardiopulse.git
   ```

2. **Masuk ke folder project**:
   ```bash
   cd cardiopulse/cardiopulse-api
   ```

3. **Install dependensi library**:
   ```bash
   pip install -r requirements.txt
   ```

4. **Jalankan Aplikasi Web**:
   ```bash
   python -m streamlit run app_streamlit.py
   ```

5. **Akses Dashboard**:
   Buka web browser dan akses alamat yang tertera di terminal (secara default `http://localhost:8501`).