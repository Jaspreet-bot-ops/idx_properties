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
Route::get('/api/property-suggestions', [PropertySuggestionController::class, 'autocomplete'])->name('api.property-suggestions');

// API for homepage developments (just 8 items)
Route::get('/api/home-developments', [PropertyApiController::class, 'getHomePageDevelopments'])->name('api.home-developments');
// Add this to your routes/web.php file
Route::get('/api/condominiums', [PropertyApiController::class, 'getCondominiums'])->name('api.condominiums');
Route::get('/api/search', [PropertyApiController::class, 'search']);

Route::get('/api/autocomplete', [PropertySuggestionController::class, 'autocomplete']);
// Route::get('/api/property-suggestions', [PropertySuggestionController::class, 'index']);
Route::get('/property-suggestions', [PropertySuggestionController::class, 'index']);

// Property detail endpoint
Route::get('/api/properties/{id}', [PropertyApiController::class, 'propertyDetails']);

// Building units endpoint
Route::get('/api/buildings', [PropertyApiController::class, 'buildings']);

// Place properties search endpoint
Route::get('/api/propertiesByPlace', [PropertyApiController::class, 'places']);

Route::get('/api/propertyByMap', [PropertyApiController::class, 'getPropertiesInMapBounds']);

Route::get('/api/getAllProperties', [PropertyApiController::class, 'getAllProperties'])->name('api.properties.all');

Route::get('/wp/properties/{id}', [PropertyController::class, 'getPropertyDetails']);

// Building details
Route::get('/wp/buildings', [PropertyController::class, 'getBuildingDetails']);

// Properties by location
Route::get('/wp1/properties/search', [PropertyController::class, 'getPropertiesByLocation']);

Route::get('/api/wp1/properties', [PropertyController::class, 'getProperties']);

// In your routes file (web.php or api.php)
Route::get('/wp1/map-properties/bounds', [PropertyController::class, 'getPropertiesInBounds']);

// Get nearby properties from local database
Route::get('/api/wpi/properties/nearby', [PropertyController::class, 'getNearbyProperties']);


require __DIR__.'/auth.php';
