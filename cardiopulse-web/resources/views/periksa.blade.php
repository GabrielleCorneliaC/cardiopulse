<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CardioPulse - Heart Disease Risk Detection</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* Animasi Background Biru Keren */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(-45deg, #0f2027, #203a43, #2c5364, #1e3c72, #2a5298);
            background-size: 400% 400%;
            animation: gradientBG 12s ease infinite;
            color: #ffffff; /* Teks default jadi putih untuk landing page */
            overflow-x: hidden;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .landing-section {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            /* Background transparan biar animasi bodynya tembus */
            background: transparent; 
        }

        .main-title {
            font-size: 5rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -1.5px;
            margin-bottom: 5px;
            text-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }

        .sub-title {
            font-size: 1.3rem;
            color: #b3cde0;
            font-weight: 400;
            margin-bottom: 40px;
            letter-spacing: 2px;
        }

        .btn-start {
            padding: 14px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            background-color: #00d2ff;
            background-image: linear-gradient(to right, #00d2ff 0%, #3a7bd5 100%);
            color: #ffffff;
            border: none;
            box-shadow: 0 8px 25px rgba(0, 210, 255, 0.4);
            transition: all 0.3s ease;
        }

        .btn-start:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 210, 255, 0.6);
            color: #fff;
        }

        #form-section {
            display: <?php echo session('hasilPrediksi') || $errors->any() ? 'block' : 'none'; ?>;
            padding: 80px 0;
            min-height: 100vh;
        }

        /* Card Form dibikin putih bersih biar gampang dibaca */
        .form-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            background: #ffffff;
            color: #2b2b2b; /* Teks di dalam form kembali gelap */
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 12px 15px;
            font-size: 0.95rem;
            background-color: #f8f9fa;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #3a7bd5;
            box-shadow: 0 0 0 0.25rem rgba(58, 123, 213, 0.25);
            background-color: #ffffff;
        }

        .help-text {
            font-size: 0.8rem;
            color: #888;
            margin-top: 5px;
        }

        .form-label {
            color: #1e3c72; /* Label biru elegan */
        }

        /* Perbaikan Print PDF */
        @media print {
            /* 1. Atur default kertas ke A4 */
            @page {
                size: A4 portrait;
                margin: 1cm;
            }

            html, body {
                height: auto !important;
                background: #fff !important;
                color: #000 !important;
                /* Paksa warna background card keluar */
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* 2. SOLUSI 3 HALAMAN: Hilangkan form dan landing page sampai ke akarnya */
            #landing-section, #form-section {
                display: none !important;
            }

            /* Hilangkan efek gelap di belakang modal */
            .modal-backdrop {
                display: none !important;
            }

            /* 3. SOLUSI RAMPING: Bebaskan modal dari kurungan lebar Bootstrap */
            .modal {
                position: static !important;
                display: block !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
            }

            .modal-dialog {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                transform: none !important; /* Hapus efek animasi yang kadang bikin nyangkut */
            }

            .modal-content {
                border: none !important;
                box-shadow: none !important;
            }

            .modal-body {
                padding: 0 !important;
            }

            /* Sembunyikan tombol-tombol saat diprint */
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="landing-section" id="landing-section">
        <h1 class="main-title">CardioPulse</h1>
        <p class="sub-title">Heart Disease Risk Detection</p>
        <button class="btn btn-start" onclick="showForm()">Start Detection</button>
    </div>

    <div id="form-section">
        <div class="container">
            <h2 class="text-center mb-5 fw-bold" style="color: #ffffff; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">Medical Data Entry</h2>

            @if ($errors->any())
                <div class="alert alert-danger rounded-3 mb-4 shadow">
                    Terdapat kesalahan input: {{ $errors->first() }}
                </div>
            @endif

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card form-card p-4 p-md-5">
                        <form action="{{ route('jantung.proses') }}" method="POST">
                            @csrf
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-semibold">Usia</label>
                                    <input type="number" name="age" class="form-control" value="{{ old('age', 55) }}" required placeholder="Contoh: 55">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-semibold">Jenis Kelamin</label>
                                    <select name="sex" class="form-select" required>
                                        <option value="" disabled selected>Pilih...</option>
                                        <option value="1" {{ old('sex') == '1' ? 'selected' : '' }}>Laki-laki</option>
                                        <option value="0" {{ old('sex') == '0' ? 'selected' : '' }}>Perempuan</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Tipe Nyeri Dada (Chest Pain)</label>
                                <select name="cp" class="form-select" required>
                                    <option value="" disabled selected>Pilih kondisi nyeri dada...</option>
                                    <option value="0" {{ old('cp') == '0' ? 'selected' : '' }}>Typical Angina (Nyeri tertekan di tengah dada)</option>
                                    <option value="1" {{ old('cp') == '1' ? 'selected' : '' }}>Atypical Angina (Nyeri tidak khas, otot/pencernaan)</option>
                                    <option value="2" {{ old('cp') == '2' ? 'selected' : '' }}>Non-Anginal Pain (Nyeri tajam, bukan penyumbatan)</option>
                                    <option value="3" {{ old('cp') == '3' ? 'selected' : '' }}>Asymptomatic (Tanpa gejala nyeri sama sekali)</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-semibold">Tekanan Darah (mmHg)</label>
                                    <input type="number" name="trestbps" class="form-control" value="{{ old('trestbps', 130) }}" required placeholder="Contoh: 120">
                                    <div class="help-text">Tekanan darah saat istirahat.</div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-semibold">Kolesterol (mg/dl)</label>
                                    <input type="number" name="chol" class="form-control" value="{{ old('chol', 240) }}" required placeholder="Contoh: 200">
                                    <div class="help-text">Total kolesterol dalam darah.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-semibold">Gula Darah Puasa</label>
                                    <select name="fbs" class="form-select" required>
                                        <option value="" disabled selected>Pilih...</option>
                                        <option value="0" {{ old('fbs') == '0' ? 'selected' : '' }}>Normal (< 120 mg/dl)</option>
                                        <option value="1" {{ old('fbs') == '1' ? 'selected' : '' }}>Tinggi (> 120 mg/dl)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-semibold">Detak Jantung Maksimal</label>
                                    <input type="number" name="thalach" class="form-control" value="{{ old('thalach', 150) }}" required placeholder="Contoh: 150">
                                    <div class="help-text">Detak jantung tertinggi saat aktivitas.</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Hasil EKG Istirahat (Resting ECG)</label>
                                <select name="restecg" class="form-select" required>
                                    <option value="" disabled selected>Pilih hasil tes...</option>
                                    <option value="0" {{ old('restecg') == '0' ? 'selected' : '' }}>Normal</option>
                                    <option value="1" {{ old('restecg') == '1' ? 'selected' : '' }}>Abnormal (Kelainan gelombang ST-T)</option>
                                    <option value="2" {{ old('restecg') == '2' ? 'selected' : '' }}>Hipertrofi (Penebalan bilik kiri)</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Nyeri Dada Saat Olahraga?</label>
                                <select name="exang" class="form-select" required>
                                    <option value="" disabled selected>Pilih...</option>
                                    <option value="0" {{ old('exang') == '0' ? 'selected' : '' }}>Tidak terasa nyeri saat olahraga</option>
                                    <option value="1" {{ old('exang') == '1' ? 'selected' : '' }}>Ya, dada nyeri saat olahraga</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <label class="form-label fw-semibold">Oldpeak</label>
                                    <input type="number" step="0.1" name="oldpeak" class="form-control" value="{{ old('oldpeak', 1.0) }}" required>
                                    <div class="help-text">Depresi ST pada EKG.</div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label class="form-label fw-semibold">Slope</label>
                                    <select name="slope" class="form-select" required>
                                        <option value="" disabled selected>Pilih...</option>
                                        <option value="0" {{ old('slope') == '0' ? 'selected' : '' }}>Upsloping (Normal, aliran darah sangat baik saat olahraga)</option>
                                        <option value="1" {{ old('slope') == '1' ? 'selected' : '' }}>Flat (Waspada, jantung mulai kekurangan oksigen saat lelah)</option>
                                        <option value="2" {{ old('slope') == '2' ? 'selected' : '' }}>Downsloping (Bahaya, indikasi kuat penyempitan pembuluh darah)</option>
                                    </select>
                                    <div class="help-text">Arah gelombang EKG saat beraktivitas berat.</div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label class="form-label fw-semibold">Jumlah Pembuluh</label>
                                    <input type="number" name="ca" class="form-control" min="0" max="3" value="{{ old('ca', 0) }}" required>
                                    <div class="help-text">Terdeteksi fluoroskopi.</div>
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="form-label fw-semibold">Hasil Tes Thalassemia</label>
                                <select name="thal" class="form-select" required>
                                    <option value="" disabled selected>Pilih...</option>
                                    <option value="1" {{ old('thal') == '1' ? 'selected' : '' }}>Normal (Aliran baik)</option>
                                    <option value="2" {{ old('thal') == '2' ? 'selected' : '' }}>Fixed Defect (Aliran terhambat permanen)</option>
                                    <option value="3" {{ old('thal') == '3' ? 'selected' : '' }}>Reversible Defect (Terhambat saat olahraga)</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-start w-100 py-3 rounded-3" style="font-size: 1.1rem; border-radius: 10px !important;">
                                Submit Analysis
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('hasilPrediksi'))
        @php 
            $hasil = session('hasilPrediksi'); 
        @endphp
        <div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" id="print-area">
                    <div class="modal-header border-0 pb-0 no-print">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-5 pb-5 pt-3 text-dark">
                        
                        <div class="text-center mb-5">
                            <h2 class="fw-bold" style="color: #1e3c72;">CardioPulse Report</h2>
                            <p class="text-muted mb-0">Heart Risk Assessment</p>
                        </div>

                        <div class="card bg-light border-0 mb-4 p-4 rounded-3">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Patient Data Profile:</h6>
                            <div class="row">
                                <div class="col-6">
                                    <ul class="list-unstyled mb-0" style="font-size: 0.9rem;">
                                        <li><strong>Usia:</strong> {{ old('age') }} Tahun</li>
                                        <li><strong>Kelamin:</strong> {{ old('sex') == 1 ? 'Laki-laki' : 'Perempuan' }}</li>
                                        <li><strong>Tekanan Darah:</strong> {{ old('trestbps') }} mmHg</li>
                                        <li><strong>Kolesterol:</strong> {{ old('chol') }} mg/dl</li>
                                        <li><strong>Detak Max:</strong> {{ old('thalach') }} bpm</li>
                                    </ul>
                                </div>
                                <div class="col-6">
                                    <ul class="list-unstyled mb-0" style="font-size: 0.9rem;">
                                        <li><strong>Tipe Nyeri Dada:</strong> Tipe {{ old('cp') }}</li>
                                        <li><strong>Nyeri Olahraga:</strong> {{ old('exang') == 1 ? 'Ya' : 'Tidak' }}</li>
                                        <li><strong>Gula Darah Tinggi:</strong> {{ old('fbs') == 1 ? 'Ya' : 'Tidak' }}</li>
                                        <li><strong>Jumlah Pembuluh:</strong> {{ old('ca') }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3">Assessment Result:</h6>
                        
                        <div class="text-center py-4 mb-4 rounded-3" style="background-color: {{ $hasil['risiko_keseluruhan'] > 50 ? '#ffebee' : '#e8f5e9' }}; color: {{ $hasil['risiko_keseluruhan'] > 50 ? '#c62828' : '#2e7d32' }}; border: 1px solid {{ $hasil['risiko_keseluruhan'] > 50 ? '#ffcdd2' : '#c8e6c9' }};">
                            <h5 class="mb-1 fw-semibold">Tingkat Risiko Keseluruhan</h5>
                            <h1 class="display-3 fw-bold mb-0">{{ $hasil['risiko_keseluruhan'] }}%</h1>
                            <span class="badge bg-white text-dark mt-2 px-3 py-2 border shadow-sm">
                                Status: {{ $hasil['decision_tree']['status'] }}
                            </span>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 border rounded-3 bg-white h-100 shadow-sm">
                                    <span class="text-muted d-block" style="font-size: 0.8rem;">Estimasi Tekanan Darah Normal Anda</span>
                                    <strong class="fs-5" style="color: #1e3c72;">{{ $hasil['prediksi_tekanan_darah_normal'] }} mmHg</strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 border rounded-3 bg-white h-100 shadow-sm">
                                    <span class="text-muted d-block" style="font-size: 0.8rem;">Kategori Kesehatan (Usia & Kelamin)</span>
                                    <strong class="fs-5" style="color: #1e3c72;">{{ $hasil['kmeans']['kategori_risiko'] }}</strong>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3 border rounded-3 bg-white mt-2 shadow-sm">
                                    <span class="text-muted d-block mb-2" style="font-size: 0.8rem;">Rincian Deteksi Algoritma:</span>
                                    <ul class="mb-0 text-muted" style="font-size: 0.85rem;">
                                        <li><strong>Diagnosa Cepat (Decision Tree):</strong> Menunjukkan status {{ $hasil['decision_tree']['status'] }} dengan kepastian {{ $hasil['decision_tree']['probabilitas'] }}%.</li>
                                        <li><strong>Analisis Mendalam (Random Forest):</strong> Memprediksi probabilitas risiko sebesar {{ $hasil['random_forest_probabilitas'] }}% berdasarkan ratusan pola pohon keputusan.</li>
                                        <li><strong>Analisis Faktor Gejala (Logistic Regression):</strong> Menilai bobot setiap gejala medis dan menghasilkan peluang risiko sebesar {{ $hasil['logistic_regression_probabilitas'] }}%.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-5 no-print">
                            <button type="button" class="btn btn-light border px-4 me-2 fw-semibold" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary px-4 fw-semibold" style="background: #1e3c72; border: none;" onclick="window.print()">Print Report</button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showForm() {
            const formSection = document.getElementById('form-section');
            formSection.style.display = 'block';
            window.scrollTo({ top: formSection.offsetTop, behavior: 'smooth' });
        }

        document.addEventListener("DOMContentLoaded", function() {
            @if(session('hasilPrediksi'))
                var myModal = new bootstrap.Modal(document.getElementById('resultModal'));
                myModal.show();
                const formSection = document.getElementById('form-section');
                formSection.style.display = 'block';
                window.scrollTo(0, formSection.offsetTop);
            @endif
        });
    </script>
</body>
</html>