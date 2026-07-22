<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — SmartFarm</title>

    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family:'Space Grotesk',sans-serif;
            min-height:100vh;
            display:flex;
            background:#0f172a;
            overflow-x:hidden;
        }

        .left-side{
            flex:1;
            background:
                linear-gradient(rgba(15,23,42,0.75), rgba(15,23,42,0.9)),
                url('https://images.unsplash.com/photo-1500937386664-56d1dfef3854?q=80&w=1600&auto=format&fit=crop');
            background-size:cover;
            background-position:center;
            display:flex;
            flex-direction:column;
            justify-content:center;
            padding:60px;
            color:white;
            position:relative;
        }

        .left-side::before{
            content:'';
            position:absolute;
            inset:0;
            background:linear-gradient(135deg, rgba(34,197,94,0.2), transparent);
        }

        .brand{
            position:relative;
            z-index:2;
        }

        .brand h1{
            font-size:52px;
            font-weight:700;
            margin-bottom:10px;
        }

        .brand p{
            font-size:18px;
            color:#cbd5e1;
            line-height:1.7;
            max-width:520px;
        }

        .right-side{
            width:470px;
            background:white;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:40px;
            position:relative;
            overflow-y:auto;
        }

        .login-box{
            width:100%;
            max-width:360px;
        }

        .login-header{
            margin-bottom:28px;
        }

        .login-header h2{
            font-size:34px;
            color:#0f172a;
            margin-bottom:8px;
        }

        .login-header p{
            color:#64748b;
            line-height:1.6;
            font-size:14px;
        }

        .form-group{
            margin-bottom:16px;
        }

        .form-group label{
            display:block;
            margin-bottom:6px;
            font-size:14px;
            font-weight:600;
            color:#334155;
        }

        .form-control{
            width:100%;
            padding:12px 16px;
            border:1px solid #cbd5e1;
            border-radius:12px;
            font-size:14px;
            outline:none;
            transition:0.2s;
            background:#f8fafc;
        }

        .form-control:focus{
            border-color:#22c55e;
            box-shadow:0 0 0 4px rgba(34,197,94,0.15);
            background:white;
        }

        .bottom-link{
            margin-top:20px;
            text-align:center;
            font-size:14px;
            color:#64748b;
        }

        .bottom-link a{
            color:#16a34a;
            text-decoration:none;
            font-weight:700;
        }

        .bottom-link a:hover{
            text-decoration:underline;
        }

        .btn-login{
            width:100%;
            border:none;
            background:linear-gradient(135deg,#16a34a,#15803d);
            color:white;
            padding:14px;
            border-radius:12px;
            font-size:15px;
            font-weight:700;
            cursor:pointer;
            transition:0.2s;
            box-shadow:0 8px 20px rgba(22,163,74,0.25);
            margin-top:10px;
        }

        .btn-login:hover{
            transform:translateY(-2px);
            box-shadow:0 12px 25px rgba(22,163,74,0.35);
        }

        .error-text{
            color:#dc2626;
            font-size:13px;
            margin-top:6px;
        }

        @media(max-width:950px){
            .left-side{
                display:none;
            }

            .right-side{
                width:100%;
                min-height:100vh;
            }
        }
    </style>
</head>
<body>

    <div class="left-side">
        <div class="brand">
            <h1>🌽 SmartFarm</h1>
            <p>
                Sistem monitoring hama jagung berbasis IoT dan Fuzzy Sugeno 
                untuk membantu petani mendeteksi potensi serangan hama secara real-time.
            </p>
        </div>
    </div>

    <div class="right-side">
        <div class="login-box">

            <div class="login-header">
                <h2>Daftar Akun</h2>
                <p>
                    Buat akun petani untuk mengakses sistem monitoring SmartFarm dan deteksi hama terbaru.
                </p>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Nama Lengkap -->
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="form-control"
                        placeholder="Masukkan nama lengkap"
                        required
                        autofocus
                        autocomplete="name"
                    >
                    @error('name')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control"
                        placeholder="Masukkan email"
                        required
                        autocomplete="username"
                    >
                    @error('email')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        required
                        autocomplete="new-password"
                    >
                    @error('password')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Konfirmasi Password -->
                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Password</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        placeholder="Ulangi password"
                        required
                        autocomplete="new-password"
                    >
                    @error('password_confirmation')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn-login">
                    Daftar Sekarang
                </button>

                <div class="bottom-link">
                    Sudah memiliki akun?
                    <a href="{{ route('login') }}">
                        Masuk
                    </a>
                </div>
            </form>

        </div>
    </div>

</body>
</html>