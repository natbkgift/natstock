<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return view('auth.verify-email', ['status' => session('status')]);
    }

    public function verify(Request $request): RedirectResponse
    {
        if (! hash_equals((string) $request->route('id'), (string) $request->user()->getKey())) {
            abort(403);
        }

        if (! hash_equals((string) $request->route('hash'), sha1($request->user()->getEmailForVerification()))) {
            abort(403);
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if ($request->user() instanceof MustVerifyEmail && $request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->route('admin.dashboard')->with('status', 'ยืนยันอีเมลเรียบร้อยแล้ว');
    }
}
