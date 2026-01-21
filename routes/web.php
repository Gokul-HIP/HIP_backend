<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AuthController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('login', [AuthController::class, 'loginStore'])->name('auth.login.store');
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
});

Route::prefix('admin')->name('admin.')->middleware(['auth:hip', 'role:super_admin'])->group(function () {

    Route::view('/', 'admin.dashboard')->name('dashboard.index');
    // Dashboard
    // Route::view('/dashboard', 'admin.dashboard')->name('dashboard');

    // Organizations
    Route::view('/organizations', 'admin.organizations.index')->name('organizations.index');

    Route::view('/organizations/create', 'admin.organizations.create')->name('organizations.create');

    Route::get('/organizations/{id}', function ($id) {
        return view('admin.organizations.show', compact('id'));
    })->name('organizations.show');

    // Route::get('/organizations/{id}/edit', function ($id) {
    //     return view('admin.organizations.edit', compact('id'));
    // })->name('organizations.edit');

    Route::post('/organizations', function () {
        return 'Organization stored successfully!';
    })->name('organizations.store');


    // Hospital
    Route::view('organization/{id}/hospital', 'admin.hospital.index')->name('organizations.Add-hospital.index');

    Route::view('add-hospital','admin.hospital.create')->name('hospital.create');

    Route::view('/organization/hospital/{id}/details', 'admin.hospital.hospital-details')->name('organizations.hospital.show');

    Route::view('/view-doctor', 'admin.hospital.view-doctor')->name('view-doctor.index');

    Route::view('/organization/hospital/{id}/view-doctor', 'admin.hospital.view-doctor')->name('view-doctor.ind');

    Route::view('/organization/hospital/{id}/view-specialities', 'admin.hospital.specialities')->name('view-specialities.index');

    // Route::view('/organization/hospital/{id}/show-create-specialities','admin.hospital.create-specialities')->name('add-specialitie.show');

    // Route::view('/organization/hospital/{id}/show-create-bulk-specialities','admin.hospital.create-bulk-speciality')->name('add-bulk-specialitie.show');


    // Pharmacy
    Route::view('organization/{id}/pharmacy-index','admin.hospital.pharmacy.index')->name('organizations.pharmacy.index');

    Route::view('/add-pharmacy','admin.hospital.pharmacy.create-pharmacy')->name('organizations.add-pharmacy');


    // Diagnostics
    Route::view('organization/{id}/diagnostic-index','admin.hospital.diagnostics.index')->name('organizations.diagnostic.index');

    Route::view('/organization/diagnostic/{id}/lab-test-index', 'admin.hospital.diagnostics.lab-test.index')->name('organizations.diagnostic.lab-test.index');


    // Procedures
    Route::view('organization/hospital/{id}/procedure', 'admin.procedure.index')->name('procedure.index');

    Route::view('procedure', 'admin.procedure.index')->name('organizations.procedure.index');

    Route::view('/show-procedure','admin.procedure.create')->name('procedure.create');

    Route::view('/show-bulk-procedure','admin.procedure.create-bulk')->name('bulk-procedure.create');

    Route::get('/procedure/{id}', function ($id) {
        return view('admin.procedure.show', compact('id'));
    })->name('procedure.show');

    //Doctor-Profile
    Route::view('doctor-profile','admin.doctor-profile.index')->name('doctor-profile.index');

    // Member-Profile
    Route::view('member-profile','admin.member-profile.member-index')->name('member-profile.member-index');

    // Pharmacy Products
    Route::view('organization/pharmacy/{id}/products', 'admin.hospital.pharmacy.products.index')->name('organizations.pharmacy.products.index');

    // Diagnostic Packages
    Route::view('organization/diagnostic/{id}/package-index', 'admin.hospital.diagnostics.package')->name('organizations.diagnostic.package.index');

});