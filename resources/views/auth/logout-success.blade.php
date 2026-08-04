@php
    $popup = [
        'type'         => 'success',
        'title'        => 'Logout Berhasil',
        'message'      => 'Anda berhasil keluar dari sistem. Anda akan diarahkan ke halaman login.',
        'button_text'  => 'Ke Halaman Login',
        'redirect_url' => route('login'),
    ];
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logout Berhasil — SmartFarm TA</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

@include('components.popup-alert', ['popup' => $popup])

<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            window.location.href = "{{ route('login') }}";
        }, 4000);
    });
</script>

</body>
</html>
