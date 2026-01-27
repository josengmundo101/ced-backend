<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectDocumentController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public

Route::post('/login', [AuthController::class, 'login']);


// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::post('/users/change-password', [UserController::class, 'changePassword']);

    

    // Projects
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']); 
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);

    // List documents of a project
    Route::get('/projects/{project}/documents',[ProjectDocumentController::class, 'index']);
    // Upload document to a project
    Route::post('/projects/{project}/documents',[ProjectDocumentController::class, 'store']);
    // Download a document
    Route::get('/documents/{document}/download',[ProjectDocumentController::class, 'download']);
    // Delete a document
    Route::delete('/documents/{document}',[ProjectDocumentController::class, 'destroy']);

    
});