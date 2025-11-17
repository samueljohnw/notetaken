<?php

use App\Http\Controllers\Api\NoteApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api')->group(function () {
    Route::post('/notes', [NoteApiController::class, 'store']);
    Route::get('/notes', [NoteApiController::class, 'index']);
});
