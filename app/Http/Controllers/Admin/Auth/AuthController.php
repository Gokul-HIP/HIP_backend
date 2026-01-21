<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\User;

class AuthController extends Controller
{
    public function login()
    {   
        if (Auth::guard('hip')->check()) {
            return redirect()->route('admin.dashboard.index');
        }

        return view('admin.auth.login');
    }

    public function loginStore(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('hip')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {

            $request->session()->regenerate();

            return redirect()->route('admin.dashboard.index');
            
        }

        return back()->with('error', 'Invalid email or password');

    }

    public function logout(Request $request)
    {
        
        Auth::guard('hip')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.auth.login');

    }

}
