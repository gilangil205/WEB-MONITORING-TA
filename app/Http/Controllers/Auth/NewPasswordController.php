<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'token'    => ['required'],
                'email'    => ['required', 'email'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);
        } catch (ValidationException $e) {
            $passErrors = $e->errors()['password'] ?? [];
            $hasConfirmationErr = false;
            foreach ($passErrors as $err) {
                if (str_contains(strtolower($err), 'confirmation') || str_contains(strtolower($err), 'konfirmasi') || str_contains(strtolower($err), 'same') || str_contains(strtolower($err), 'match')) {
                    $hasConfirmationErr = true;
                    break;
                }
            }

            if ($hasConfirmationErr) {
                session()->flash('popup', [
                    'type'    => 'error',
                    'title'   => 'Reset Password Gagal',
                    'message' => 'Password baru dan konfirmasi password harus sama.',
                ]);
            } else {
                session()->flash('popup', [
                    'type'    => 'error',
                    'title'   => 'Reset Password Gagal',
                    'message' => $e->validator->errors()->first(),
                ]);
            }

            throw $e;
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('popup', [
                'type'    => 'success',
                'title'   => 'Reset Password Berhasil',
                'message' => 'Password berhasil diperbarui. Silakan masuk menggunakan password baru.',
            ]);

            return redirect()->route('login');
        }

        $errorTitle = 'Reset Password Gagal';
        $errorMessage = 'Data pengaturan ulang password tidak valid.';

        if ($status === Password::INVALID_TOKEN || $status === Password::INVALID_USER) {
            $errorTitle = 'Tautan Tidak Valid';
            $errorMessage = 'Tautan pengaturan ulang password tidak valid. Silakan kirim permintaan reset password kembali.';
        }

        session()->flash('popup', [
            'type'    => 'error',
            'title'   => $errorTitle,
            'message' => $errorMessage,
        ]);

        return back()->withInput($request->only('email'));
    }
}
