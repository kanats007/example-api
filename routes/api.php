<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\Authentication;
use App\Http\Middleware\Logger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/apple', function (Request $request) {
    return response()->json(["devices" => ["iPhone", "iPad", "MacBook"]], Response::HTTP_OK, []);
})->middleware(Logger::class);

Route::get('/login', [AuthController::class, 'login'])->middleware(Logger::class);
Route::get('/logout', [AuthController::class, 'logout'])->middleware(Logger::class);

Route::get('/user', [AuthController::class, 'user'])->middleware(Logger::class)->middleware(Authentication::class);

Route::get('/token', [AuthController::class, 'callback'])->middleware(Logger::class);
