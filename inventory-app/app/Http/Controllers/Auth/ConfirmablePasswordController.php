<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required'],
        ], [], [
            'password' => 'รหัสผ่าน',
        ]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            return back()->withErrors([
                'password' => 'รหัสผ่านไม่ถูกต้อง',
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended();
    }
}
