<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CardioPulse - Laravel Edition</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4">CardioPulse: Sistem Deteksi Risiko Jantung</h2>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row">
            <div class="col-md-6">
                <div class="card p-4 shadow-sm">
                    <form action="{{ route('jantung.proses') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Usia</label>
                            <input type="number" name="age" class="form-value form-control" value="{{ $request->age ?? 55 }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="sex" class="form-select" required>
                                <option value="1" {{ isset($request) && $request->sex == 1 ? 'selected' : '' }}>Laki-laki</option>
                                <option value="0" {{ isset($request) && $request->sex == 0 ? 'selected' : '' }}>Perempuan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipe Nyeri Dada (CP)</label>
                            <select name="cp" class="form-select" required>
                                <option value="0">0: Typical Angina</option>
                                <option value="1">1: Atypical Angina</option>
                                <option value="2">2: Non-Anginal Pain</option>
                                <option value="3">3: Asymptomatic</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tekanan Darah Istirahat (mmHg)</label>
                            <input type="number" name="trestbps" class="form-control" value="{{ $request->trestbps ?? 130 }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kolesterol Total (mg/dl)</label>
                            <input type="number" name="chol" class="form-control" value="{{ $request->chol ?? 240 }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gula Darah Puasa > 120 mg/dl?</label>
                            <select name="fbs" class="form-select" required>
                                <option value="0">Tidak</option>
                                <option value="1">Ya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">EKG Istirahat</label>
                            <select name="restecg" class="form-select" required>
                                <option value="0">0: Normal</option>
                                <option value="1">1: ST-T Wave Abnormality</option>
                                <option value="2">2: Left Ventricular Hypertrophy</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Detak Jantung Maksimum</label>
                            <input type="number" name="thalach" class="form-control" value="{{ $request->thalach ?? 150 }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nyeri Dada Saat Olahraga (Exang)?</label>
                            <select name="exang" class="form-select" required>
                                <option value="0">Tidak</option>
                                <option value="1">Ya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Oldpeak (ST Depression)</label>
                            <input type="number" step="0.1" name="oldpeak" class="form-control" value="{{ $request->oldpeak ?? 1.0 }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Slope ST Segment</label>
                            <select name="slope" class="form-select" required>
                                <option value="0">0: Upsloping</option>
                                <option value="1">1: Flat</option>
                                <option value="2">2: Downsloping</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Pembuluh Utama (0-3)</label>
                            <input type="number" name="ca" class="form-control" min="0" max="3" value="{{ $request->ca ?? 0 }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hasil Tes Thal</label>
                            <select name="thal" class="form-select" required>
                                <option value="1">1: Normal</option>
                                <option value="2">2: Fixed Defect</option>
                                <option value="3">3: Reversible Defect</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Jalankan Analisis Risiko</button>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card p-4 shadow-sm bg-white h-100">
                    <h4>Hasil Analisis AI (Data Mining):</h4>
                    <hr>
                    @if (isset($hasilPrediksi))
                        <div class="alert alert-info">
                            <strong>Risiko Keseluruhan:</strong> {{ $hasilPrediksi['risiko_keseluruhan'] }}%
                        </div>
                        <ul class="list-group">
                            <li class="list-group-item"><strong>K-Means Cluster:</strong> {{ $hasilPrediksi['kmeans']['kategori_risiko'] }} (Cluster {{ $hasilPrediksi['kmeans']['cluster'] }})</li>
                            <li class="list-group-item"><strong>Prediksi Tekanan Darah Normal:</strong> {{ $hasilPrediksi['prediksi_tekanan_darah_normal'] }} mmHg</li>
                            <li class="list-group-item"><strong>Decision Tree:</strong> {{ $hasilPrediksi['decision_tree']['status'] }} ({{ $hasilPrediksi['decision_tree']['probabilitas'] }}%)</li>
                            <li class="list-group-item"><strong>Random Forest Probabilitas:</strong> {{ $hasilPrediksi['random_forest_probabilitas'] }}%</li>
                            <li class="list-group-item"><strong>Logistic Regression Probabilitas:</strong> {{ $hasilPrediksi['logistic_regression_probabilitas'] }}%</li>
                        </ul>
                    @else
                        <p class="text-muted">Silakan isi data di sebelah kiri dan klik tombol analisis untuk melihat hasil pengolahan data algoritma.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>