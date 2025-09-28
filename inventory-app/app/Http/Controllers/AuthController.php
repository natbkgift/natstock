<?php
namespace App\Http\Controllers;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        view('auth/login');
    }

    public function login(): void
    {
        verify_csrf();
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$email || !$password) {
            flash('error', 'กรุณากรอกข้อมูลให้ครบ');
            $_SESSION['_old'] = ['email' => $email];
            redirect('/login');
        }

        if (!auth()->attempt($email, $password)) {
            flash('error', 'อีเมลหรือรหัสผ่านไม่ถูกต้อง');
            $_SESSION['_old'] = ['email' => $email];
            redirect('/login');
        }

        redirect('/admin/dashboard');
    }

    public function logout(): void
    {
        auth()->logout();
        redirect('/login');
    }
}
