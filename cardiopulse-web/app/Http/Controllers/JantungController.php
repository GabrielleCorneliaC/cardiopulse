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
        // Validasi inputan
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

        // Tembak ke API Flask Python
        $response = Http::post('http://127.0.0.1:5000/predict', $validatedData);

        if ($response->successful()) {
            $hasilPrediksi = $response->json()['data'];
            
            // PERUBAHAN DI SINI: Kita redirect kembali ke halaman utama (/) 
            // membawa data input sebelumnya (withInput) dan hasil prediksinya (with)
            return redirect()->route('jantung.index')
                             ->withInput()
                             ->with('hasilPrediksi', $hasilPrediksi);
        } else {
            return back()->withErrors(['api_error' => 'Gagal terhubung ke otak AI Python. Pastikan server Python menyala.']);
        }
    }
}