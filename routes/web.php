<?php

use App\Http\Controllers\Api\PropertySuggestionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PropertyApiController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/properties',[PropertyController::class, 'index'])->name('properties');
    Route::get('/properties/{property}', [PropertyController::class, 'show'])->name('properties.show');
    Route::post('/properties/parse-address', [PropertyController::class, 'parseAddress'])->name('properties.parse-address');
    Route::get('/properties/search-by-address', [PropertyController::class, 'searchByAddress'])->name('properties.search-by-address');

});

// API for new developments
Route::get('/api/new-developments', [PropertyApiController::class, 'getNewDevelopments'])->name('api.new-developments');
// Make sure this route exists in your routes/web.php file
Route::get('/api/property-suggestions', [PropertySuggestionController::class, 'suggestion'])->name('api.property-suggestions');

// API for homepage developments (just 8 items)
Route::get('/api/home-developments', [PropertyApiController::class, 'getHomePageDevelopments'])->name('api.home-developments');
// Add this to your routes/web.php file
Route::get('/api/condominiums', [App\Http\Controllers\Api\PropertyApiController::class, 'getCondominiums'])->name('api.condominiums');
Route::get('/api/search', [PropertyApiController::class, 'search']);

Route::get('/property-suggestions', [PropertySuggestionController::class, 'index']);

require __DIR__.'/auth.php';