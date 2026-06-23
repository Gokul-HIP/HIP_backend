<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HIPUser;
use App\Models\Doctor;
use App\Models\DoctorCredential;

class AuthController extends Controller
{
    
    // Show super admin login form (for custom dashboard only)
    public function login()
    {   
        // If already authenticated and has super-admin-hip role, redirect to dashboard
        if (Auth::guard('filament')->check()) {
            $user = Auth::guard('filament')->user();
            
            // Only super-admin-hip can access custom dashboard
            if ($user->hasRole('super-admin-hip')) {
                return redirect()->route('admin.dashboard.index');
            }
            
            // If logged in but not super-admin-hip, logout
            Auth::guard('filament')->logout();
        }

        return view('admin.auth.login');
    }

    // Process super admin login (for custom dashboard only)
    public function loginStore(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt authentication
        if (Auth::guard('filament')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $user = Auth::guard('filament')->user();
            
            // Check if user has super-admin-hip role (NOT super-admin)
            if ($user->hasRole('super-admin-hip')) {
                $request->session()->regenerate();
                
                return redirect()->intended(route('admin.dashboard.index'));
            }
            
            // If not super-admin-hip, logout and show error
            Auth::guard('filament')->logout();
            $request->session()->invalidate();
            
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'You do not have permission to access this dashboard. Use /master for Filament panel.');
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'Invalid email or password.');
    }

    // Show hospital admin login form
    public function hospitalLogin()
    {
        // If already authenticated and has hospital admin role, redirect to dashboard
        if (Auth::guard('filament')->check()) {
            $user = Auth::guard('filament')->user();
            
            if ($user->hasRole('healthcare_admin')) {
                return redirect()->route('healthcare.admin.dashboard.index');
            }
            
            // If logged in but not hospital admin, logout
            Auth::guard('filament')->logout();
        }

        return view('hospital-admin.login');
    }

    // Process hospital admin login
    public function hospitalLoginStore(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt authentication
        if (Auth::guard('filament')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $user = Auth::guard('filament')->user();
            
            // Check if user has hospital admin role
            if ($user->hasRole('healthcare_admin')) {
                $request->session()->regenerate();
                
                return redirect()->intended(route('healthcare.admin.dashboard.index'));
            }
            
            // If not hospital admin, logout and show error
            Auth::guard('filament')->logout();
            $request->session()->invalidate();
            
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'You do not have permission to access this dashboard.');
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'Invalid email or password.');
    }

    // Logout user and redirect to appropriate login page
    public function logout(Request $request)
    {
        $user = Auth::guard('filament')->user();
        
        // Determine which login page to redirect to based on current role
        $redirectRoute = 'admin.auth.login';
        
        if ($user) {
            if ($user->hasRole('healthcare_admin')) {
                $redirectRoute = 'healthcare.auth.login';
            } elseif ($user->hasRole('cashier_admin')) {
                $redirectRoute = 'cashier.auth.login';
            } elseif ($user->hasRole('pharmacist')) {
                $redirectRoute = 'pharmacist.auth.login';
            } elseif ($user->hasRole('technician')) {
                $redirectRoute = 'technician.auth.login';
            } elseif ($user->hasRole('receptionist')) {
                $redirectRoute = 'receptionist.auth.login';
            } elseif ($user->hasRole('doctor')) {
                $redirectRoute = 'doctor.auth.login';
            } elseif ($user->hasRole('super-admin-hip')) {
                $redirectRoute = 'admin.auth.login';
            }
            // Note: super-admin users use Filament's logout, not this controller
        }
        
        // Logout and clear session
        Auth::guard('filament')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route($redirectRoute)->with('success', 'You have been logged out successfully.');
    }


    public function cashierLogin()
    {
        return view('cashier-admin.login');
    }

    public function cashierLoginStore(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('filament')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $user = Auth::guard('filament')->user();
            
            if ($user->hasRole('cashier_admin')) {
                $request->session()->regenerate();
                return redirect()->intended(route('cashier.dashboard.index'));
            }

            Auth::guard('filament')->logout();
            $request->session()->invalidate();
            
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'You do not have permission to access this dashboard.');
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'Invalid email or password.');
    }

    public function cashierLogout(Request $request)
    {
        Auth::guard('filament')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('cashier.auth.login')->with('success', 'You have been logged out successfully.');
    }

    public function doctorLogin()
    {
        if (Auth::guard('filament')->check()) {
            $user = Auth::guard('filament')->user();

            if ($user->hasRole('doctor')) {
                return redirect()->route('doctor.dashboard.index');
            }

            Auth::guard('filament')->logout();
        }

        return view('doctor-admin.login');
    }

    public function doctorLoginStore(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('filament')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $user = Auth::guard('filament')->user();

            if ($user->hasRole('doctor')) {
                $doctorId = DoctorCredential::where('email', $user->email)->value('doctor_id');

                if (!$doctorId) {
                    $doctorId = Doctor::where('email', $user->email)->value('id');
                }

                $request->session()->regenerate();
                $request->session()->put('doctor_id', $doctorId);

                return redirect()->intended(route('doctor.dashboard.index'));
            }

            Auth::guard('filament')->logout();
            $request->session()->invalidate();

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'You do not have permission to access this dashboard.');
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'Invalid email or password.');
    }

    public function doctorLogout(Request $request)
    {
        Auth::guard('filament')->logout();
        $request->session()->forget('doctor_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('doctor.auth.login')->with('success', 'You have been logged out successfully.');
    }

    public function pharmacistLogin()
    {
        return view('pharmacist-admin.login');
    }

    public function pharmacistLoginStore(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);

        if (Auth::guard('filament')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $user = Auth::guard('filament')->user();
            if ($user->hasRole('pharmacist')) {
                $request->session()->regenerate();
                return redirect()->intended(route('pharmacist.dashboard.index'));
            }
            Auth::guard('filament')->logout();
            $request->session()->invalidate();
            return back()->withInput($request->only('email'))->with('error', 'You do not have permission to access this dashboard.');
        }

        return back()->withInput($request->only('email'))->with('error', 'Invalid email or password.');
    }

    public function pharmacistLogout(Request $request)
    {
        Auth::guard('filament')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('pharmacist.auth.login')->with('success', 'You have been logged out successfully.');
    }

    public function technicianLogin()
    {
        return view('technician-admin.login');
    }

    public function technicianLoginStore(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);

        if (Auth::guard('filament')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $user = Auth::guard('filament')->user();
            if ($user->hasRole('technician')) {
                $request->session()->regenerate();
                return redirect()->intended(route('technician.dashboard.index'));
            }
            Auth::guard('filament')->logout();
            $request->session()->invalidate();
            return back()->withInput($request->only('email'))->with('error', 'You do not have permission to access this dashboard.');
        }

        return back()->withInput($request->only('email'))->with('error', 'Invalid email or password.');
    }

    public function technicianLogout(Request $request)
    {
        Auth::guard('filament')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('technician.auth.login')->with('success', 'You have been logged out successfully.');
    }

    public function receptionistLogin()
    {
        return view('receptionist-admin.login');
    }

    public function receptionistLoginStore(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);

        if (Auth::guard('filament')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $user = Auth::guard('filament')->user();
            if ($user->hasRole('receptionist')) {
                $request->session()->regenerate();
                return redirect()->intended(route('receptionist.dashboard.index'));
            }
            Auth::guard('filament')->logout();
            $request->session()->invalidate();
            return back()->withInput($request->only('email'))->with('error', 'You do not have permission to access this dashboard.');
        }

        return back()->withInput($request->only('email'))->with('error', 'Invalid email or password.');
    }

    public function receptionistLogout(Request $request)
    {
        Auth::guard('filament')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('receptionist.auth.login')->with('success', 'You have been logged out successfully.');
    }

}
