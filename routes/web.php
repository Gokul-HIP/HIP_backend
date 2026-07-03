<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\TransactionsExportController as AdminTransactionsExportController;
use App\Http\Controllers\Editor\TinyMceUploadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Healthcare\DashboardController as HealthcareDashboardController;
use App\Http\Controllers\Healthcare\TransactionsExportController;
use App\Http\Controllers\Doctor\NotificationController as DoctorNotificationController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\Admin\SettingsController;
use App\Livewire\Admin\Settings\RewardTiers;
use App\Livewire\HospitalAdmin\Settings\RewardTiers as HealthcareRewardTiers;

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

Route::prefix('pharmacist')->name('pharmacist.')->group(function () {
    Route::get('login', [AuthController::class, 'pharmacistLogin'])->name('auth.login');
    Route::post('login', [AuthController::class, 'pharmacistLoginStore'])->name('auth.login.store');
    Route::post('logout', [AuthController::class, 'pharmacistLogout'])->name('auth.logout');
});

Route::prefix('technician')->name('technician.')->group(function () {
    Route::get('login', [AuthController::class, 'technicianLogin'])->name('auth.login');
    Route::post('login', [AuthController::class, 'technicianLoginStore'])->name('auth.login.store');
    Route::post('logout', [AuthController::class, 'technicianLogout'])->name('auth.logout');
});

Route::prefix('receptionist')->name('receptionist.')->group(function () {
    Route::get('login', [AuthController::class, 'receptionistLogin'])->name('auth.login');
    Route::post('login', [AuthController::class, 'receptionistLoginStore'])->name('auth.login.store');
    Route::post('logout', [AuthController::class, 'receptionistLogout'])->name('auth.logout');
});

// Doctor Admin Login Routes
Route::prefix('doctor')->name('doctor.')->group(function () {
    Route::get('login', [AuthController::class, 'doctorLogin'])->name('auth.login');
    Route::post('login', [AuthController::class, 'doctorLoginStore'])->name('auth.login.store');
    Route::match(['get', 'post'], 'logout', [AuthController::class, 'doctorLogout'])->name('auth.logout');
});

// Super Admin Dashboard Routes (Custom Dashboard)
Route::prefix('admin')->name('admin.')->middleware(['auth:filament', 'role:super-admin-hip'])->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
        Route::post('/dashboard/reviews/{review}/approve', [DashboardController::class, 'approveDoctorReview'])->name('dashboard.reviews.approve');
        Route::post('/dashboard/reviews/{review}/reject', [DashboardController::class, 'rejectDoctorReview'])->name('dashboard.reviews.reject');

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
        Route::view('/organization/hospital/{id}/pharmacist-credentials', 'admin.hospital.pharmacist-credentials')->name('hospital-pharmacist-credentials.index');
        Route::view('/organization/hospital/{id}/technician-credentials', 'admin.hospital.technician-credentials')->name('hospital-technician-credentials.index');
        Route::view('/organization/hospital/{id}/receptionist-credentials', 'admin.hospital.receptionist-credentials')->name('hospital-receptionist-credentials.index');

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

        // Pharmacy Products (medicines)
        Route::view('organization/pharmacy/{id}/products', 'admin.hospital.pharmacy.products.index')->name('organizations.pharmacy.products.index');

        // Pharmacy catalog products
        Route::view('organization/pharmacy/{id}/catalog-products', 'admin.hospital.pharmacy.catalog-products.index')->name('organizations.pharmacy.catalog-products.index');

        // Diagnostic Packages
        Route::view('organization/diagnostic/{id}/package-index', 'admin.hospital.diagnostics.package')->name('organizations.diagnostic.package.index');
        Route::view('organization/diagnostic/{id}/disease-package-index', 'admin.hospital.diagnostics.disease-package')->name('organizations.diagnostic.disease-package.index');
  		
  		// Hospital Onboarding
        Route::view('hospital-onboarding', 'admin.hospital-onboarding.index')->name('hospital-onboarding.index');
        Route::view('hospital-onboarding/{id}/review', 'admin.hospital-onboarding.review')->name('hospital-onboarding.review');

        // Doctor Booking
        Route::view('doctor-booking', 'admin.doctor-booking.index')->name('doctor-booking.index');
        Route::view('doctor-booking/{id}/appointment-details', 'admin.doctor-booking.appointment-details')->name('doctor-booking.appointment-details');

        // Second Opinion Booking
        Route::view('second-opinion', 'admin.second-opinion.index')->name('second-opinion.index');
        Route::view('second-opinion/{id}/appointment-details', 'admin.second-opinion.appointment-details')->name('second-opinion.appointment-details');

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
        Route::post('content/tinymce/upload', TinyMceUploadController::class)->name('tinymce.upload');

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

        // Settings
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.setting');
        Route::get('settings/reward-tiers', RewardTiers::class)->name('settings.reward-tiers');
        Route::post('settings/update', [SettingsController::class, 'update'])->name('settings.update');

        Route::view('content/how-to-earn', 'admin.how-to-earn.index')->name('how-to-earn.index');

        Route::view('membership-packages', 'admin.membership-packages.index')->name('membership-packages.index');
        Route::view('membership-packages/subscriptions', 'admin.membership-packages.subscriptions')->name('membership-packages.subscriptions');

    });

// Healthcare Admin Dashboard Routes
Route::prefix('healthcare')->name('healthcare.')->middleware(['auth:filament', 'role:healthcare_admin'])
    ->group(function () {
        
        Route::get('/', [HealthcareDashboardController::class, 'index'])->name('dashboard.index');
        Route::get('dashboard', [HealthcareDashboardController::class, 'index'])->name('admin.dashboard.index');
        Route::view('hospitals', 'hospital-admin.hospitals.index')->name('hospitals.index');
        Route::view('diagnostics', 'hospital-admin.diagnostics.index')->name('diagnostics.index');
        Route::view('pharmacy', 'hospital-admin.pharmacy.index')->name('pharmacy.index');
        Route::view('diagnostics/{id}/lab-tests', 'hospital-admin.diagnostics.lab-test.index')->name('diagnostics.lab-test.index');
        Route::view('diagnostics/{id}/packages', 'hospital-admin.diagnostics.package')->name('diagnostics.package.index');
        Route::view('diagnostics/{id}/disease-packages', 'hospital-admin.diagnostics.disease-package')->name('diagnostics.disease-package.index');
        Route::view('pharmacy/{id}/products', 'hospital-admin.pharmacy.products.index')->name('pharmacy.products.index');
        Route::view('pharmacy/{id}/catalog-products', 'hospital-admin.pharmacy.catalog-products.index')->name('pharmacy.catalog-products.index');
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
        Route::post('content/tinymce/upload', TinyMceUploadController::class)->name('tinymce.upload');
       Route::view('ads/dashboard', 'hospital-admin.ads.dashboard')->name('ads.dashboard');
       Route::view('ads/ad-management', 'hospital-admin.ads.ad-management')->name('ads.ad-management.index');
       Route::view('ads/create-ad', 'hospital-admin.ads.create-ad')->name('ads.ad-management.create-ad');
       Route::view('ads/edit-ad/{id}', 'hospital-admin.ads.edit-ad')->name('ads.ad-management.edit-ad');
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

         Route::view('second-opinion-bookings', 'hospital-admin.bookings.second-opinion-booking')->name('second-opinion.booking');
         Route::get('second-opinion-bookings/{id}/appointment-details', function ($id) {
              return view('hospital-admin.bookings.second-opinion-appointment-details', ['id' => (int) $id]);
         })->name('second-opinion.booking.appointment-details');

        Route::view('content/how-to-earn', 'hospital-admin.content.how-to-earn')->name('how-to-earn.index');

        // CSV export: direct download (full page request so browser receives attachment)
       Route::get('transactions/export', TransactionsExportController::class)->name('transactions.export');

        // Settings
        Route::get('settings/reward-tiers', HealthcareRewardTiers::class)->name('settings.reward-tiers');
        Route::view('settings/membership-packages', 'hospital-admin.settings.membership-packages.index')->name('settings.membership-packages.index');
        Route::view('settings/membership-packages/subscriptions', 'hospital-admin.settings.membership-packages.subscriptions')->name('settings.membership-packages.subscriptions');

});

// Cashier Admin Dashboard Routes
Route::prefix('cashier')->name('cashier.')->middleware(['auth:filament', 'role:cashier_admin'])
    ->group(function () {

        // After login, cashier admins should see the dashboard
        Route::get('/', \App\Http\Controllers\Cashier\DashboardController::class)->name('dashboard.index');
        
        Route::view('payments', 'cashier-admin.payments.index')->name('payments.index');
        Route::view('payments/create', 'cashier-admin.payments.create-payment')->name('payments.create');

        // CSV export: direct download (full page request so browser receives attachment)
        Route::get('payments/export', \App\Http\Controllers\Cashier\PaymentsExportController::class)->name('payments.export');

        Route::view('manage-subscriptions', 'cashier.subscriptions.index')->name('manage-subscriptions.index');
        Route::view('manage-subscriptions/create', 'cashier.subscriptions.create')->name('manage-subscriptions.create');

    });

Route::prefix('pharmacist')->name('pharmacist.')->middleware(['auth:filament', 'role:pharmacist'])->group(function () {
    Route::get('/', \App\Http\Controllers\Pharmacist\DashboardController::class)->name('dashboard.index');
    Route::view('payments', 'pharmacist-admin.payments.index')->name('payments.index');
    Route::view('payments/create', 'pharmacist-admin.payments.create-payment')->name('payments.create');
    Route::get('payments/export', \App\Http\Controllers\Pharmacist\PaymentsExportController::class)->name('payments.export');
    Route::view('manage-subscriptions', 'pharmacist.subscriptions.index')->name('manage-subscriptions.index');
    Route::view('manage-subscriptions/create', 'pharmacist.subscriptions.create')->name('manage-subscriptions.create');
});

Route::prefix('technician')->name('technician.')->middleware(['auth:filament', 'role:technician'])->group(function () {
    Route::get('/', \App\Http\Controllers\Technician\DashboardController::class)->name('dashboard.index');
    Route::view('payments', 'technician-admin.payments.index')->name('payments.index');
    Route::view('payments/create', 'technician-admin.payments.create-payment')->name('payments.create');
    Route::get('payments/export', \App\Http\Controllers\Technician\PaymentsExportController::class)->name('payments.export');
    Route::view('manage-subscriptions', 'technician.subscriptions.index')->name('manage-subscriptions.index');
    Route::view('manage-subscriptions/create', 'technician.subscriptions.create')->name('manage-subscriptions.create');

    Route::view('diagnostic-bookings', 'technician-admin.diagnostic-bookings.index')->name('diagnostic-bookings.index');
    Route::view('diagnostic-bookings/{id}/appointment-details', 'technician-admin.diagnostic-bookings.appointment-details')->name('diagnostic-bookings.appointment-details');
    Route::view('patients', 'technician-admin.patients.index')->name('patients.index');

    Route::view('upload-report', 'technician-admin.upload-report.index')->name('upload-report.index');
    Route::view('upload-report/{booking_id}', 'technician-admin.upload-report.create')->name('upload-report.create');
    Route::view('patient-documents/{booking_id}', 'technician-admin.patient-documents.view')->name('patient-documents.view');
});

Route::prefix('receptionist')->name('receptionist.')->middleware(['auth:filament', 'role:receptionist'])->group(function () {
    Route::view('/', 'receptionist-admin.dashboard')->name('dashboard.index');
    Route::view('payments', 'receptionist-admin.payments.index')->name('payments.index');
    Route::view('payments/create', 'receptionist-admin.payments.create-payment')->name('payments.create');
    Route::get('payments/export', \App\Http\Controllers\Receptionist\PaymentsExportController::class)->name('payments.export');
    Route::view('manage-subscriptions', 'receptionist.subscriptions.index')->name('manage-subscriptions.index');
    Route::view('manage-subscriptions/create', 'receptionist.subscriptions.create')->name('manage-subscriptions.create');
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

        Route::view('my-appointment', 'doctor-admin.appointment.my-appointment')->name('my-appointment.index');
        Route::view('online-consultation', 'doctor-admin.appointment.online-consultation')->name('online-consultation.index');
        Route::view('upload-prescription', 'doctor-admin.prescription.upload-prescription')->name('upload-prescription.index');
        Route::view('create-prescription/{patient_id}', 'doctor-admin.prescription.create-prescription')->name('upload-prescription.create-prescription.index');
        Route::view('patient-document', 'doctor-admin.patientDocumet.patient-document')->name('patient-document.index');
        Route::view('patient-document/{patient_id}/view-document', 'doctor-admin.patientDocumet.view-document')->name('patient-document.view-document');

        Route::get('notifications', [DoctorNotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/unread-count', [DoctorNotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::post('notifications/{id}/read', [DoctorNotificationController::class, 'markRead'])->name('notifications.mark-read');

});

Route::get('/pay/invoice', [InvoicePaymentController::class, 'show'])->name('payments.invoice.page');
