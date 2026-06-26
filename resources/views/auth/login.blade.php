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
            font-family:'Space Grotesk',sans-serif;
            min-height:100vh;
            display:flex;
            background:#0f172a;
            overflow:hidden;
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

        .iot-box{
            margin-top:30px;
            display:flex;
            gap:15px;
            flex-wrap:wrap;
            position:relative;
            z-index:2;
        }

        .iot-card{
            background:rgba(255,255,255,0.08);
            border:1px solid rgba(255,255,255,0.08);
            backdrop-filter:blur(10px);
            padding:18px;
            border-radius:14px;
            min-width:150px;
        }

        .iot-card h3{
            font-size:13px;
            color:#94a3b8;
            margin-bottom:6px;
        }

        .iot-card p{
            font-size:28px;
            font-weight:700;
            color:#4ade80;
        }

        .right-side{
            width:470px;
            background:white;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:40px;
            position:relative;
        }

        .login-box{
            width:100%;
            max-width:360px;
        }

        .login-header{
            margin-bottom:35px;
        }

        .login-header h2{
            font-size:34px;
            color:#0f172a;
            margin-bottom:10px;
        }

        .login-header p{
            color:#64748b;
            line-height:1.6;
            font-size:14px;
        }

        .form-group{
            margin-bottom:20px;
        }

        .form-group label{
            display:block;
            margin-bottom:8px;
            font-size:14px;
            font-weight:600;
            color:#334155;
        }

        .form-control{
            width:100%;
            padding:14px 16px;
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
        
        .remember{
            display:flex;
            align-items:center;
            justify-content:space-between;
            margin-top:5px;
            margin-bottom:25px;
            font-size:13px;
        }

        .remember label{
            display:flex;
            align-items:center;
            gap:8px;
            color:#475569;
        }

        .remember a{
            text-decoration:none;
            color:#16a34a;
            font-weight:600;
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

        .status-message{
            background:#dcfce7;
            color:#166534;
            padding:14px;
            border-radius:10px;
            margin-bottom:20px;
            font-size:14px;
        }

        .footer-text{
            text-align:center;
            margin-top:25px;
            color:#94a3b8;
            font-size:12px;
        }

        @media(max-width:950px){

            .left-side{
                display:none;
            }

            .right-side{
                width:100%;
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
                <h2>Login</h2>
                <p>
                    Masuk ke sistem monitoring SmartFarm untuk melihat kondisi lahan dan deteksi hama terbaru.
                </p>
            </div>

            @if (session('status'))
                <div class="status-message">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label>Email</label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control"
                        placeholder="Masukkan email"
                        required
                        autofocus
                    >

                    @error('email')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Password</label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        required
                    >

                    @error('password')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="remember">

                    <label>
                        <input type="checkbox" name="remember">
                        Remember me
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}">
                            Lupa password?
                        </a>
                    @endif

                </div>

                <button type="submit" class="btn-login">
                    Masuk ke Dashboard
                </button>

            </form>

            <div class="bottom-link">
                    belum punya akun??
                    <a href="{{ route('register') }}">
                        Daftar di sini
                    </a>
                </div>

        </div>

    </div>

</body>
</html>