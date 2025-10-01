<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [], [
            'current_password' => 'รหัสผ่านปัจจุบัน',
            'password' => 'รหัสผ่านใหม่',
        ]);

        if (! Hash::check($request->input('current_password'), $request->user()->password)) {
            return back()->withErrors([
                'current_password' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง',
            ]);
        }

        $request->user()->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return back()->with('status', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
    }
}
