<?php

use Illuminate\Support\Facades\Route;

// API-only backend: root goes to the Swagger UI served by l5-swagger
// (GET /api/documentation, spec at GET /docs).
Route::redirect('/', '/api/documentation');
