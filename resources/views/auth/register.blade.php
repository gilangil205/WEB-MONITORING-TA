<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SmartFarm</title>

    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family:'Segoe UI',sans-serif;
            background:
                linear-gradient(rgba(15,23,42,.88), rgba(15,23,42,.92)),
                url('https://images.unsplash.com/photo-1500937386664-56d1dfef3854?q=80&w=1600&auto=format&fit=crop');
            background-size:cover;
            background-position:center;
            min-height:100vh;
        }

        .register-wrapper{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:30px;
        }

        .register-card{
            width:100%;
            max-width:500px;
            background:rgba(255,255,255,0.08);
            backdrop-filter:blur(16px);
            border:1px solid rgba(255,255,255,0.12);
            border-radius:24px;
            padding:40px 35px;
            box-shadow:0 15px 40px rgba(0,0,0,0.35);
        }

        .logo-box{
            text-align:center;
            margin-bottom:28px;
        }

        .logo-box .icon{
            font-size:58px;
            margin-bottom:10px;
        }

        .logo-box h1{
            color:white;
            font-size:30px;
            font-weight:800;
            margin-bottom:6px;
        }

        .logo-box p{
            color:#cbd5e1;
            font-size:14px;
            line-height:1.6;
        }

        .input-group{
            margin-bottom:18px;
        }

        .input-group label{
            display:block;
            color:#e2e8f0;
            margin-bottom:8px;
            font-size:14px;
            font-weight:600;
        }

        .input-group input{
            width:100%;
            padding:14px 16px;
            border-radius:14px;
            border:1px solid rgba(255,255,255,0.12);
            background:rgba(255,255,255,0.07);
            color:white;
            font-size:14px;
            outline:none;
            transition:.2s;
        }

        .input-group input:focus{
            border-color:#22c55e;
            box-shadow:0 0 0 4px rgba(34,197,94,0.18);
            background:rgba(255,255,255,0.1);
        }

        .input-group input::placeholder{
            color:#94a3b8;
        }

        .error-text{
            color:#f87171;
            font-size:12px;
            margin-top:6px;
        }

        .register-btn{
            width:100%;
            padding:14px;
            border:none;
            border-radius:14px;
            background:linear-gradient(135deg,#22c55e,#15803d);
            color:white;
            font-size:15px;
            font-weight:700;
            cursor:pointer;
            transition:.2s;
            margin-top:8px;
            box-shadow:0 10px 20px rgba(34,197,94,0.25);
        }

        .register-btn:hover{
            transform:translateY(-2px);
            box-shadow:0 15px 25px rgba(34,197,94,0.35);
        }

        .bottom-link{
            margin-top:22px;
            text-align:center;
            font-size:14px;
            color:#cbd5e1;
        }

        .bottom-link a{
            color:#4ade80;
            text-decoration:none;
            font-weight:700;
        }

        .bottom-link a:hover{
            text-decoration:underline;
        }

        .iot-status{
            margin-top:24px;
            padding:12px;
            border-radius:12px;
            background:rgba(34,197,94,0.08);
            border:1px solid rgba(34,197,94,0.2);
            display:flex;
            align-items:center;
            gap:10px;
        }

        .iot-dot{
            width:10px;
            height:10px;
            border-radius:50%;
            background:#22c55e;
            animation:blink 1s infinite;
        }

        .iot-status span{
            color:#d1fae5;
            font-size:13px;
        }

        @keyframes blink{
            0%,100%{opacity:1;}
            50%{opacity:.3;}
        }

        @media(max-width:600px){
            .register-card{
                padding:30px 22px;
            }

            .logo-box h1{
                font-size:24px;
            }
        }
    </style>

    <div class="register-wrapper">
        <div class="register-card">

            <div class="logo-box">
                <div class="icon">🌽</div>
                <h1>SmartFarm Register</h1>
                <p>
                    Buat akun petani untuk mengakses sistem monitoring hama jagung berbasis IoT & Fuzzy Sugeno.  
                    Iya ribet dikit. Teknologi manusia emang doyan form.
                </p>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Name -->
                <div class="input-group">
                    <label for="name">Nama Lengkap</label>

                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Masukkan nama petani"
                        required
                        autofocus
                        autocomplete="name"
                    >

                    <x-input-error :messages="$errors->get('name')" class="error-text" />
                </div>

                <!-- Email -->
                <div class="input-group">
                    <label for="email">Email</label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="contoh@email.com"
                        required
                        autocomplete="username"
                    >

                    <x-input-error :messages="$errors->get('email')" class="error-text" />
                </div>

                <!-- Password -->
                <div class="input-group">
                    <label for="password">Password</label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Minimal jangan password123 lah"
                        required
                        autocomplete="new-password"
                    >

                    <x-input-error :messages="$errors->get('password')" class="error-text" />
                </div>

                <!-- Confirm Password -->
                <div class="input-group">
                    <label for="password_confirmation">Konfirmasi Password</label>

                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        placeholder="Ulangin lagi biar server percaya"
                        required
                        autocomplete="new-password"
                    >

                    <x-input-error :messages="$errors->get('password_confirmation')" class="error-text" />
                </div>

                <button type="submit" class="register-btn">
                    🚀 Daftar Sekarang
                </button>

                <div class="bottom-link">
                    Udah punya akun?
                    <a href="{{ route('login') }}">
                        Masuk di sini
                    </a>
                </div>

                <div class="iot-status">
                    <div class="iot-dot"></div>
                    <span>Sistem Monitoring SmartFarm Aktif • IoT Connected</span>
                </div>
            </form>

        </div>
    </div>