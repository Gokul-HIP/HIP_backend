<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AuthController;

// Super Admin Login Routes (Custom Dashboard)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('login', [AuthController::class, 'loginStore'])->name('auth.login.store');
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
});

// Hospital Admin Login Routes
Route::prefix('hospital')->name('hospital.')->group(function () {
    Route::get('login', [AuthController::class, 'hospitalLogin'])->name('auth.login');
    Route::post('login', [AuthController::class, 'hospitalLoginStore'])->name('auth.login.store');
});

// Cashier Admin Login Routes
Route::prefix('cashier')->name('cashier.')->group(function () {
    Route::get('login', [AuthController::class, 'cashierLogin'])->name('auth.login');
    Route::post('login', [AuthController::class, 'cashierLoginStore'])->name('auth.login.store');
    Route::post('logout', [AuthController::class, 'cashierLogout'])->name('auth.logout');
});


// Super Admin Dashboard Routes (Custom Dashboard)
Route::prefix('admin')->name('admin.')->middleware(['auth:filament', 'role:super-admin-hip'])->group(function () {

        Route::view('/', 'admin.dashboard')->name('dashboard.index');

        // Organizations
        Route::view('/organizations', 'admin.organizations.index')->name('organizations.index');
        Route::view('/organizations/create', 'admin.organizations.create')->name('organizations.create');
        
        Route::get('/organizations/{id}', function ($id) {
            return view('admin.organizations.show', compact('id'));
        })->name('organizations.show');

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
        Route::view('/organization/hospital/{id}/hospital-admin', 'admin.hospital.hospital-admin')->name('hospital-admin.index');

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

        // Doctor Profile
        Route::view('doctor-profile','admin.doctor-profile.index')->name('doctor-profile.index');

        // Member Profile
        Route::view('member-profile','admin.member-profile.member-index')->name('member-profile.member-index');

        // Pharmacy Products
        Route::view('organization/pharmacy/{id}/products', 'admin.hospital.pharmacy.products.index')->name('organizations.pharmacy.products.index');

        // Diagnostic Packages
        Route::view('organization/diagnostic/{id}/package-index', 'admin.hospital.diagnostics.package')->name('organizations.diagnostic.package.index');
  		
  		// Hospital Onboarding
        Route::view('hospital-onboarding', 'admin.hospital-onboarding.index')->name('hospital-onboarding.index');
        Route::view('hospital-onboarding/{id}/review', 'admin.hospital-onboarding.review')->name('hospital-onboarding.review');

        // Doctor Booking
        Route::view('doctor-booking', 'admin.doctor-booking.index')->name('doctor-booking.index');
        Route::view('doctor-booking/{id}/appointment-details', 'admin.doctor-booking.appointment-details')->name('doctor-booking.appointment-details');

        // Diagnostic Test Booking
        Route::view('diagnostic-test-booking', 'admin.diagnostic-test-booking.index')->name('diagnostic-test-booking.index');
        Route::view('diagnostic-test-booking/{id}/appointment-details', 'admin.diagnostic-test-booking.appointment-details')->name('diagnostic-test-booking.appointment-details');

        // Wellness Services
        Route::view('wellness-services', 'admin.wellness-services.index')->name('wellness-services.index');
        Route::view('wellness-services/create', 'admin.wellness-services.form')->name('wellness-services.create');
        Route::view('wellness-services/{id}/edit', 'admin.wellness-services.form')->name('wellness-services.edit');
        Route::view('wellness-services/{id}/view', 'admin.wellness-services.view')->name('wellness-services.view');

        // Wellness Booking
        Route::view('wellness-booking', 'admin.wellness-booking.index')->name('wellness-booking.index');
        Route::get('wellness-booking/{id}/appointment-details', function ($id) {
            return view('admin.wellness-booking.appointment-details', compact('id'));
        })->name('wellness-booking.appointment-details');
  		
        // Stemcell Booking
        Route::view('stemcell-booking', 'admin.stemcell-booking.index')->name('stemcell-booking.index');
        Route::get('stemcell-booking/{id}/appointment-details', function ($id) {
            return view('admin.stemcell-booking.appointment-details', compact('id'));
        })->name('stemcell-booking.appointment-details');

        // Caregiver
        Route::view('caregiver', 'admin.caregiver.index')->name('caregiver.index');
        Route::view('add-caregiver', 'admin.caregiver.add-caregiver')->name('caregiver.add-caregiver');
        Route::view('edit/{id}/caregiver', 'admin.caregiver.edit-caregiver')->name('caregiver.edit-caregiver');

        // Caregiver Booking
        Route::view('caregiver-booking', 'admin.caregiver-booking.index')->name('caregiver-booking.index');
        Route::view('caregiver-booking/{id}/appointment-details', 'admin.caregiver-booking.appointment-details')->name('caregiver-booking.appointment-details');

        // Procedure Booking
        Route::view('procedure-booking', 'admin.procedure-booking.index')->name('procedure-booking.index');
        Route::get('procedure-booking/{id}/appointment-details', function ($id) {
            return view('admin.procedure-booking.appointment-details', compact('id'));
        })->name('procedure-booking.appointment-details');

        // Content & Reviews
        Route::view('content/dashboard', 'admin.content.dashboard')->name('content.dashboard');
        Route::view('content/content-moderation', 'admin.content.content-moderation')->name('content-moderation.index');
        Route::view('content/create-content', 'admin.content.create-content')->name('content-moderation.create');
        Route::view('content/edit-content/{id}', 'admin.content.edit-content')->name('content-moderation.edit');

        // Doctor Reviews
        Route::view('doctor-review/index', 'admin.doctor-review.index')->name('doctor-review.index');
        Route::view('hospital-review/index', 'admin.hospital-review.index')->name('hospital-review.index');

        // Ads
        Route::view('ads/dashboard', 'admin.ads.dashboard')->name('ads.dashboard');
        Route::view('ads/ad-management', 'admin.ads.ad-management')->name('ads.ad-management.index');
        Route::view('ads/create-ad', 'admin.ads.create-ad')->name('ads.ad-management.create-ad');
        Route::get('ads/edit-ad/{id}', function ($id) {
            return view('admin.ads.edit-ad', ['id' => (int) $id]);
        })->name('ads.ad-management.edit-ad');
        Route::view('ads/engagement', 'admin.ads.ad-engagement')->name('ads.ad-management.engagement');
    });

// Hospital Admin Dashboard Routes
Route::prefix('hospital')->name('hospital.')->middleware(['auth:filament', 'role:hospital_admin'])
    ->group(function () {
        
        Route::view('/', 'hospital-admin.login')->name('dashboard.index');
        Route::view('dashboard', 'hospital-admin.dashboard')->name('admin.dashboard.index');
        Route::view('hospital-profile', 'hospital-admin.hospital-profile.hospital-profile')->name('hospital-profile.index');

        Route::view('hospital-profile/basic-details', 'hospital-admin.hospital-profile.steps.hospital-details')
        ->name('hospital-profile.basic_details');
    
        Route::view('hospital-profile/hospital-location', 'hospital-admin.hospital-profile.steps.hospital-location')
            ->name('hospital-profile.hospital_location');
        
        Route::view('hospital-profile/hospital-capacity', 'hospital-admin.hospital-profile.steps.hospital-capacity')
            ->name('hospital-profile.hospital_capacity');
        
        Route::view('hospital-profile/medical-compliance', 'hospital-admin.hospital-profile.steps.medical-compliance')
            ->name('hospital-profile.medical_compliance');
        
        Route::view('hospital-profile/contact-details', 'hospital-admin.hospital-profile.steps.contact-details')
            ->name('hospital-profile.contact-details');

});

// Cashier Admin Dashboard Routes
Route::prefix('cashier')->name('cashier.')->middleware(['auth:filament', 'role:cashier_admin'])
    ->group(function () {

        // After login, cashier admins should see the dashboard
        Route::view('/', 'cashier-admin.dashboard')->name('dashboard.index');
        
        Route::view('payments', 'cashier-admin.payments.index')->name('payments.index');
        Route::view('payments/create', 'cashier-admin.payments.create-payment')->name('payments.create');

        // CSV export: direct download (full page request so browser receives attachment)
        Route::get('payments/export', \App\Http\Controllers\Cashier\PaymentsExportController::class)->name('payments.export');

});
