<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JantungController;

// Jalur untuk menampilkan halaman utama form
Route::get('/', [JantungController::class, 'index'])->name('jantung.index');

// Jalur untuk memproses form saat tombol "Analisis" ditekan
Route::post('/proses', [JantungController::class, 'proses'])->name('jantung.proses');

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/halo', function () {
//     return 'ini adalah home';
// });
