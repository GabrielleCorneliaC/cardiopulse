<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // Wajib import ini untuk panggil API

class JantungController extends Controller
{
    // 1. Menampilkan halaman form input pasien
    public function index()
    {
        return view('periksa');
    }

    // 2. Memproses data form dan menembak ke API Flask Python
    public function proses(Request $request)
    {
        // Validasi inputan dulu biar datanya aman dan sesuai tipe data Python
        $validatedData = $request->validate([
            'age' => 'required|numeric',
            'sex' => 'required|integer',
            'cp' => 'required|integer',
            'trestbps' => 'required|numeric',
            'chol' => 'required|numeric',
            'fbs' => 'required|integer',
            'restecg' => 'required|integer',
            'thalach' => 'required|numeric',
            'exang' => 'required|integer',
            'oldpeak' => 'required|numeric',
            'slope' => 'required|integer',
            'ca' => 'required|integer',
            'thal' => 'required|integer',
        ]);

        // Kirim data hasil validasi ke API Flask Python (Metode POST)
        // Pastikan server Flask kamu di port 5000 sudah menyala ya!
        $response = Http::post('http://127.0.0.1:5000/predict', $validatedData);

        if ($response->successful()) {
            $hasilPrediksi = $response->json()['data'];
            
            // Lempar hasil prediksi dari Python kembali ke halaman view
            return view('periksa', compact('hasilPrediksi', 'request'));
        } else {
            return back()->withErrors(['api_error' => 'Gagal terhubung ke otak AI Python. Pastikan server Python sudah menyala.']);
        }
    }
}