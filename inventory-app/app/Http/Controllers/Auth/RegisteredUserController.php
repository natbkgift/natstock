<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [], [
            'name' => 'ชื่อ-สกุล',
            'email' => 'อีเมล',
            'password' => 'รหัสผ่าน',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'viewer',
        ]);

        event(new Registered($user));

        auth()->login($user);

        return redirect()->route('admin.dashboard')->with('status', 'สมัครสมาชิกสำเร็จและเข้าสู่ระบบแล้ว');
    }
}
