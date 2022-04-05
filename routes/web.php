<?php

use App\Http\Controllers\Encryption;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


// Route::get('/', [Encryption::class,'index']);
// Route::get('/decode', [Encryption::class,'decodeFile']);


Auth::routes();

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::post('testment', 'EncryptionController@testme')->name('testme');
Route::post('decodeme', 'EncryptionController@decodeSignature')->name('decodeme');
// Route::post('/testinme', [EncryptionController@index,'testme'])->name("testme");


Route::group(['prefix' => '/'], function () {
    Voyager::routes();
    Route::resource('signature', 'SignatureController');
    Route::resource('verifications', 'VerificationController');
});
