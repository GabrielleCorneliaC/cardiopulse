<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; 

class JantungController extends Controller
{
    public function index()
    {
        return view('periksa');
    }

    public function proses(Request $request)
    {
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

        $response = Http::post('http://127.0.0.1:5000/predict', $validatedData);

        if ($response->successful()) {
            $hasilPrediksi = $response->json()['data'];
            

            return redirect()->route('jantung.index')
                             ->withInput()
                             ->with('hasilPrediksi', $hasilPrediksi);
        } else {
            return back()->withErrors(['api_error' => 'Gagal terhubung ke otak AI Python. Pastikan server Python menyala.']);
        }
    }
}