<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\TransactionsExportController as AdminTransactionsExportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Healthcare\TransactionsExportController;
use App\Http\Controllers\InvoicePaymentController;

// Super Admin Login Routes (Custom Dashboard)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('login', [AuthController::class, 'loginStore'])->name('auth.login.store');
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
});

// Healthcare Admin Login Routes
Route::prefix('healthcare')->name('healthcare.')->group(function () {
    Route::get('login', [AuthController::class, 'hospitalLogin'])->name('auth.login');
    Route::post('login', [AuthController::class, 'hospitalLoginStore'])->name('auth.login.store');
});

// Cashier Admin Login Routes
Route::prefix('cashier')->name('cashier.')->group(function () {
    Route::get('login', [AuthController::class, 'cashierLogin'])->name('auth.login');
    Route::post('login', [AuthController::class, 'cashierLoginStore'])->name('auth.login.store');
    Route::post('logout', [AuthController::class, 'cashierLogout'])->name('auth.logout');
});

// Doctor Admin Login Routes
Route::prefix('doctor')->name('doctor.')->group(function () {
    Route::get('login', [AuthController::class, 'doctorLogin'])->name('auth.login');
    Route::post('login', [AuthController::class, 'doctorLoginStore'])->name('auth.login.store');
    Route::post('logout', [AuthController::class, 'doctorLogout'])->name('auth.logout');
});

// Super Admin Dashboard Routes (Custom Dashboard)
Route::prefix('admin')->name('admin.')->middleware(['auth:filament', 'role:super-admin-hip'])->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

        // Organizations
        Route::view('/organizations', 'admin.organizations.index')->name('organizations.index');
        Route::view('/organizations/create', 'admin.organizations.create')->name('organizations.create');
        Route::view('/organizations/{id}/admin-credentials', 'admin.organizations.credentials')->name('organizations.credentials.index');
        
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
        Route::get('doctor-profile/{id?}', function ($id = null) {
            return view('admin.doctor-profile.index', ['organization_id' => $id ? (int) $id : null]);
        })->name('organizations.doctor-profile.index');

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

        // Transactions
        Route::view('transactions', 'admin.transactions.index')->name('transactions.index');
        Route::get('transactions/export', AdminTransactionsExportController::class)->name('transactions.export');

        // Content & Reviews
        Route::view('content/dashboard', 'admin.content.dashboard')->name('content.dashboard');
        Route::view('content/content-moderation', 'admin.content.content-moderation')->name('content-moderation.index');
        Route::view('content/create-content', 'admin.content.create-content')->name('content-moderation.create');
        Route::view('content/edit-content/{id}', 'admin.content.edit-content')->name('content-moderation.edit');

        // Doctor Reviews
        Route::view('doctor-review/index', 'admin.doctor-review.index')->name('doctor-review.index');
        Route::view('doctor-review/{id}/view', 'admin.doctor-review.viewReview')->name('doctor-review.view');
        // Hospital Reviews
        Route::view('hospital-review/index', 'admin.hospital-review.index')->name('hospital-review.index');
        Route::view('hospital-review/{id}/view', 'admin.hospital-review.viewReview')->name('hospital-review.view');

        // Ads
        Route::view('ads/dashboard', 'admin.ads.dashboard')->name('ads.dashboard');
        Route::view('ads/ad-management', 'admin.ads.ad-management')->name('ads.ad-management.index');
        Route::view('ads/create-ad', 'admin.ads.create-ad')->name('ads.ad-management.create-ad');
        Route::get('ads/edit-ad/{id}', function ($id) {
            return view('admin.ads.edit-ad', ['id' => (int) $id]);
        })->name('ads.ad-management.edit-ad');
        Route::view('ads/engagement', 'admin.ads.ad-engagement')->name('ads.ad-management.engagement');
    });

// Healthcare Admin Dashboard Routes
Route::prefix('healthcare')->name('healthcare.')->middleware(['auth:filament', 'role:healthcare_admin'])
    ->group(function () {
        
        Route::view('/', 'hospital-admin.dashboard')->name('dashboard.index');
        Route::view('dashboard', 'hospital-admin.dashboard')->name('admin.dashboard.index');
        Route::view('hospitals', 'hospital-admin.hospitals.index')->name('hospitals.index');
        Route::view('diagnostics', 'hospital-admin.diagnostics.index')->name('diagnostics.index');
        Route::view('pharmacy', 'hospital-admin.pharmacy.index')->name('pharmacy.index');
        Route::view('diagnostics/{id}/lab-tests', 'hospital-admin.diagnostics.lab-test.index')->name('diagnostics.lab-test.index');
        Route::view('diagnostics/{id}/packages', 'hospital-admin.diagnostics.package')->name('diagnostics.package.index');
        Route::view('pharmacy/{id}/products', 'hospital-admin.pharmacy.products.index')->name('pharmacy.products.index');
        Route::view('hospitals/{id}/specialities', 'hospital-admin.specialities.index')->name('hospitals.specialities.index');
        Route::view('hospitals/{id}/procedures', 'hospital-admin.procedures.index')->name('hospitals.procedures.index');
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

       Route::view('payment-report','hospital-admin.transaction.index')->name('transactions.index');

       Route::view('doctors', 'hospital-admin.users.doctors')->name('doctors.index');
       Route::view('members', 'hospital-admin.users.member-profile')->name('members.index');

        Route::view('content/dashboard', 'hospital-admin.content.dashboard')->name('content.dashboard');
        Route::view('content/content-moderation', 'hospital-admin.content.content-moderation')->name('content.index');
        Route::view('content/create-content', 'hospital-admin.content.create-content')->name('content.create');
        Route::view('content/edit-content/{id}', 'hospital-admin.content.edit-content')->name('content.edit');
       Route::get('doctors/{id}/profile', function ($id) {
            return view('hospital-admin.users.doctor-profile', ['id' => (string) $id]);
       })->name('doctors.profile');

       Route::view('diagnostic-bookings', 'hospital-admin.bookings.diagnostic-booking')->name('diagnostic.booking');
       Route::get('diagnostic-bookings/{id}/appointment-details', function ($id) {
            return view('hospital-admin.bookings.diagnostic-appointment-details', ['id' => (int) $id]);
       })->name('diagnostic.booking.appointment-details');

       Route::view('procedure-bookings', 'hospital-admin.bookings.procedure-booking')->name('procedure.booking');
       Route::get('procedure-bookings/{id}/appointment-details', function ($id) {
            return view('hospital-admin.bookings.procedure-appointment-details', ['id' => (int) $id]);
       })->name('procedure.booking.appointment-details');

         Route::view('doctor-bookings', 'hospital-admin.bookings.doctor-booking')->name('doctor.booking');
         Route::get('doctor-bookings/{id}/appointment-details', function ($id) {
              return view('hospital-admin.bookings.doctor-appointment-details', ['id' => (int) $id]);
         })->name('doctor.booking.appointment-details');

        // CSV export: direct download (full page request so browser receives attachment)
       Route::get('transactions/export', TransactionsExportController::class)->name('transactions.export');

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

// Doctor Admin Dashboard Routes
Route::prefix('doctor')->name('doctor.')->middleware(['auth:filament', 'role:doctor'])->group(function () {

        Route::view('/', 'doctor-admin.dashboard')->name('dashboard.index');
        Route::view('dashboard', 'doctor-admin.dashboard')->name('admin.dashboard.index');
        Route::view('members', 'doctor-admin.members.index')->name('member-profile.member-index');
        Route::view('referral/send', 'doctor-admin.referral.send-referral')->name('referral.send');
        Route::view('referral/receive', 'doctor-admin.referral.receive-referral')->name('referral.receive');
        Route::view('referral/add', 'doctor-admin.referral.add-referral')->name('referral.send.add');
        Route::get('referral/{id}', function ($id) {
            return view('doctor-admin.referral.referral-details', ['id' => (int) $id]);
        })->name('referral.receive.show');
        Route::get('referral/{id}/edit', function ($id) {
            return view('doctor-admin.referral.edit-referral', ['id' => (int) $id]);
        })->name('referral.send.edit');
        Route::get('referral/{id}/view', function ($id) {
            return redirect()->route('doctor.referral.receive.show', ['id' => (int) $id]);
        })->name('referral.send.view');

});

Route::get('/pay/invoice', [InvoicePaymentController::class, 'show'])->name('payments.invoice.page');
