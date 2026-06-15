<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JantungController;

Route::get('/', [JantungController::class, 'index'])->name('jantung.index');

Route::post('/proses', [JantungController::class, 'proses'])->name('jantung.proses');

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/halo', function () {
//     return 'ini adalah home';
// });
