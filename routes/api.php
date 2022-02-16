<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * Each request with JSON return response has element "success" into it.
 * When success has value "false", that indicates API has responded with undesired behavior.
 * Another response here is "message" which states type of error or exception
 *
 * When success has value "true", that indicates we have received what we expect from this API.
 * For each call, request parameters and response paraameters are mentioned
 */

Route::post('register',[\App\Http\Controllers\API\AuthController::class, 'register'])->name('api.register');
Route::post('login', [\App\Http\Controllers\API\AuthController::class, 'login'])->name('api.login');

Route::group(['middleware' => ['jwt.verify']], function() {
    Route::post('logout', [\App\Http\Controllers\API\AuthController::class, 'logout'])->name('api.logout');

    Route::post('loan/apply', [\App\Http\Controllers\API\ApplicationController::class, 'apply'])->name('api.loan.apply');
    Route::post('loan/calculate/{loanID?}', [\App\Http\Controllers\API\ApplicationController::class, 'calculateInstallment'])->name('api.loan.calculateEMI');

    Route::get('loan/{id}', [\App\Http\Controllers\API\ApplicationController::class, 'applicationInfo'])->name('api.loan.info');
    Route::post('loan/{id}/approve', [\App\Http\Controllers\API\ApplicationController::class, 'approveApplication'])->name('api.loan.approve');
    Route::post('loan/{id}/reject', [\App\Http\Controllers\API\ApplicationController::class, 'rejectApplication'])->name('api.loan.approve');

    Route::post('loan/list', [\App\Http\Controllers\API\ApplicationController::class, 'userLoans'])->name('api.loan.list');

    Route::post('loan/{id}/repayment', [\App\Http\Controllers\API\ApplicationController::class, 'receivePayment'])->name('api.loan.repayment');
});
