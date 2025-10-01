<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct()
    {
        // ใช้ Policy เพื่อตรวจสอบสิทธิ์ในทุกเมธอดของ Controller
        $this->authorizeResource(User::class, 'user');
    }

    public function index(): View
    {
        $users = User::latest('id')->paginate(15);

        return view('admin.users.index', ['users' => $users]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(['admin', 'staff'])],
        ], [], [
            'name' => 'ชื่อ-สกุล',
            'email' => 'อีเมล',
            'password' => 'รหัสผ่าน',
            'role' => 'บทบาท',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return redirect()->route('admin.users.index')->with('status', 'สร้างผู้ใช้ใหม่สำเร็จ');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(['admin', 'staff'])],
        ], [], [
            'name' => 'ชื่อ-สกุล',
            'email' => 'อีเมล',
            'password' => 'รหัสผ่าน',
            'role' => 'บทบาท',
        ]);

        // ป้องกันไม่ให้ Admin ลดระดับสิทธิ์ของตัวเอง
        if ($user->id === auth()->id() && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'ไม่สามารถลดระดับสิทธิ์ของตัวเองได้'])->withInput();
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('status', 'อัปเดตข้อมูลผู้ใช้สำเร็จ');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'ลบผู้ใช้สำเร็จ');
    }
}