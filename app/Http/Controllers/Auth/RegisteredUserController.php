<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.required'      => 'Nama lengkap wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email tersebut sudah terdaftar pada sistem.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Password dan konfirmasi password harus sama.',
        ]);

        if ($validator->fails()) {
            $errs = $validator->errors();
            $msg  = $errs->first();

            if ($errs->has('email') && (str_contains($errs->first('email'), 'terdaftar') || str_contains($errs->first('email'), 'unique') || str_contains($errs->first('email'), 'taken'))) {
                $msg = 'Email tersebut sudah terdaftar pada sistem.';
            } elseif ($errs->has('password') && (str_contains($errs->first('password'), 'sama') || str_contains($errs->first('password'), 'confirmed') || str_contains($errs->first('password'), 'konfirmasi'))) {
                $msg = 'Password dan konfirmasi password harus sama.';
            } elseif ($errs->has('password') && (str_contains($errs->first('password'), '8') || str_contains($errs->first('password'), 'min'))) {
                $msg = 'Password minimal 8 karakter.';
            } elseif (count($errs->all()) > 1) {
                $msg = 'Lengkapi seluruh data registrasi yang diperlukan.';
            }

            session()->flash('popup', [
                'type'    => 'error',
                'title'   => 'Registrasi Gagal',
                'message' => $msg,
            ]);

            return back()->withErrors($validator)->withInput($request->except('password', 'password_confirmation'));
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'user', // Locked role: Petani
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        session()->flash('popup', [
            'type'    => 'success',
            'title'   => 'Registrasi Berhasil',
            'message' => 'Akun Anda berhasil dibuat. Selamat datang di Dashboard Petani.',
        ]);

        return redirect()->route('dashboard');
    }
}