<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::group(['middleware' => 'auth:sanctum'], function() {
      Route::get('logout', [AuthController::class, 'logout']);
      Route::get('user', [AuthController::class, 'user']);
    });
});

Route::group(['prefix' => 'task'], function () {
    Route::group(['middleware' => 'auth:sanctum'], function() {
      Route::get('index', [TaskController::class, 'index']);
      Route::get('show', [TaskController::class, 'show']);
      Route::post('store', [TaskController::class, 'store']);
      Route::put('update/{id}', [TaskController::class, 'update']);
    });
});
