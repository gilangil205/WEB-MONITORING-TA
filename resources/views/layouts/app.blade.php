<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title') — SmartFarm</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>

    <style>
        /* ── RESET ─────────────────────────────────────────────────── */
        * { margin:0; padding:0; box-sizing:border-box; }
        /* ── VARIABEL ──────────────────────────────────────────────── */
        :root {
            --hijau:      #16a34a;
            --hijau-muda: #22c55e;
            --kuning:     #ca8a04;
            --merah:      #dc2626;
            --biru:       #2563eb;
            --abu:        #64748b;
            --bg:         #f0fdf4;
            --card:       #ffffff;
            --border:     #dcfce7;
            --teks:       #0f172a;
            --teks2:      #475569;
            --radius:     14px;

            --shadow:     0 4px 24px rgba(0,0,0,0.07);

            --font:       'Space Grotesk', sans-serif;

            --mono:       'JetBrains Mono', monospace;

        }



        body {

            display: flex;

            background: var(--bg);

            min-height: 100vh;

            font-family: var(--font);

        }



        /* ── ANIMASI — pakai prefix sf- agar tidak bentrok @keyframes Blade ── */

        @keyframes sf-blink {

            0%, 100% { opacity: 1; }

            50%       { opacity: 0.3; }

        }



        @keyframes sf-spin {

            to { transform: rotate(360deg); }

        }



        /* ── SIDEBAR ───────────────────────────────────────────────── */

        .sidebar {

            width: 240px;

            min-height: 100vh;

            background: #0f172a;

            padding: 24px 16px;

            position: fixed;

            top: 0; left: 0;

            display: flex;

            flex-direction: column;

        }



        .sidebar-brand {

            padding: 0 8px 20px;

            border-bottom: 1px solid #1e293b;

            margin-bottom: 8px;

        }



        .sidebar-brand span {

            font-size: 16px;

            font-weight: 700;

            color: #f8fafc;

            line-height: 1.4;

        }



        .sidebar-brand small {

            font-size: 10px;

            font-weight: 400;

            color: #64748b;

            display: block;

        }



        .sidebar-label {

            font-size: 10px;

            font-weight: 600;

            text-transform: uppercase;

            letter-spacing: 1px;

            color: #475569;

            padding: 0 12px;

            margin: 12px 0 6px;

        }



        .sidebar a {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 10px 12px;

            border-radius: 8px;

            color: #94a3b8;

            text-decoration: none;

            margin-bottom: 4px;

            font-size: 14px;

            font-weight: 500;

            transition: background 0.15s, color 0.15s;

        }



        .sidebar a:hover { background: #1e293b; color: #e2e8f0; }

        .sidebar a.active { background: #14532d; color: #86efac; }

        .sidebar a svg { width: 16px; height: 16px; flex-shrink: 0; }



        .sidebar-status {

            margin-top: auto;

            padding: 12px;

            border-radius: 10px;

            background: #1e293b;

        }



        .sidebar-status p { color: #94a3b8; font-size: 11px; margin-bottom: 6px; }



        /* ── MAIN ──────────────────────────────────────────────────── */

        .main {

            margin-left: 240px;

            padding: 28px 30px;

            width: 100%;

            min-height: 100vh;

        }



        /* ── ALERT ─────────────────────────────────────────────────── */

        .alert-success {

            background: #dcfce7;

            border-left: 4px solid #22c55e;

            color: #166534;

            padding: 12px 16px;

            border-radius: 8px;

            margin-bottom: 20px;

            font-size: 14px;

        }



        /* ── PAGE HEADER ───────────────────────────────────────────── */

        .page-header {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            flex-wrap: wrap;

            gap: 12px;

            margin-bottom: 24px;

        }



        .page-header h1 { font-size: 22px; font-weight: 700; color: var(--teks); margin-bottom: 2px; }

        .page-header p  { font-size: 13px; color: var(--abu); }



        /* ── UPDATE BADGE ──────────────────────────────────────────── */

        .update-badge {

            display: flex;

            align-items: center;

            gap: 6px;

            background: white;

            border: 1px solid var(--border);

            padding: 6px 12px;

            border-radius: 99px;

            font-size: 12px;

            color: var(--abu);

            font-family: var(--mono);

        }



        .update-badge .dot {

            width: 7px; height: 7px;

            background: var(--hijau-muda);

            border-radius: 50%;

            animation: sf-blink 1.4s ease-in-out infinite;

        }



        /* ── LIVE PILL ─────────────────────────────────────────────── */

        .live-pill {

            display: flex;

            align-items: center;

            gap: 7px;

            background: #0f172a;

            color: white;

            padding: 7px 14px;

            border-radius: 99px;

            font-size: 12px;

            font-weight: 700;

        }



        .live-dot {

            width: 8px; height: 8px;

            background: #ef4444;

            border-radius: 50%;

            animation: sf-blink 1s infinite;

        }



        /* ── PANEL ─────────────────────────────────────────────────── */

        .panel {

            background: var(--card);

            border-radius: var(--radius);

            border: 1px solid var(--border);

            box-shadow: var(--shadow);

            overflow: hidden;

        }



        .panel-header {

            padding: 14px 20px;

            border-bottom: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

        }



        .panel-title { font-size: 14px; font-weight: 700; color: var(--teks); display: flex; align-items: center; gap: 8px; }

        .panel-body  { padding: 20px; }



        /* ── SENSOR GRID (dashboard) ───────────────────────────────── */

        .sensor-grid {

            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 16px;

            margin-bottom: 20px;

        }



        .sensor-card {

            background: var(--card);

            border-radius: var(--radius);

            padding: 20px;

            border: 1px solid var(--border);

            box-shadow: var(--shadow);

            position: relative;

            overflow: hidden;

            transition: transform 0.2s;

        }



        .sensor-card:hover { transform: translateY(-2px); }



        .sensor-card::before {

            content: '';

            position: absolute;

            top: 0; left: 0; right: 0;

            height: 3px;

        }



        .sensor-card.suhu::before   { background: linear-gradient(90deg, #ef4444, #f97316); }

        .sensor-card.udara::before  { background: linear-gradient(90deg, #3b82f6, #06b6d4); }

        .sensor-card.tanah::before  { background: linear-gradient(90deg, #22c55e, #16a34a); }

        .sensor-card.status::before { background: linear-gradient(90deg, #f59e0b, #ef4444); }



        .sensor-card .sc-icon  { font-size: 28px; margin-bottom: 12px; display: block; }

        .sensor-card .sc-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--abu); margin-bottom: 4px; }

        .sensor-card .sc-value { font-size: 32px; font-weight: 700; color: var(--teks); font-family: var(--mono); line-height: 1; }

        .sensor-card .sc-sub   { font-size: 12px; color: var(--abu); margin-top: 6px; }



        .sensor-card.status-hama    { background: linear-gradient(135deg, #fef2f2, #fee2e2); border-color: #fca5a5; }

        .sensor-card.status-waspada { background: linear-gradient(135deg, #fffbeb, #fef9c3); border-color: #fde68a; }

        .sensor-card.status-aman    { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-color: #86efac; }



        .status-badge-besar {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 8px 14px;

            border-radius: 99px;

            font-size: 13px;

            font-weight: 700;

            margin-top: 10px;

        }



        /* ── MINI GRID (kamera) ────────────────────────────────────── */

        .mini-grid {

            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 12px;

            margin-bottom: 20px;

        }



        .mini-card {

            background: var(--card);

            border: 1px solid var(--border);

            border-radius: var(--radius);

            padding: 14px 16px;

            box-shadow: var(--shadow);

            display: flex;

            align-items: center;

            gap: 12px;

        }



        .mini-card .mc-icon  { font-size: 24px; flex-shrink: 0; }

        .mini-card .mc-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: var(--abu); margin-bottom: 2px; }

        .mini-card .mc-val   { font-size: 20px; font-weight: 700; color: var(--teks); font-family: var(--mono); line-height: 1; }



        .mini-card.mc-status-hama    { background: linear-gradient(135deg,#fef2f2,#fee2e2); border-color:#fca5a5; }

        .mini-card.mc-status-waspada { background: linear-gradient(135deg,#fffbeb,#fef9c3); border-color:#fde68a; }

        .mini-card.mc-status-aman    { background: linear-gradient(135deg,#f0fdf4,#dcfce7); border-color:#86efac; }



        /* ── BADGE STATUS ──────────────────────────────────────────── */

        .badge-hama    { background: #dc2626; color: white; }

        .badge-waspada { background: #d97706; color: white; }

        .badge-aman    { background: #16a34a; color: white; }



        .badge-status { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; }

        .bs-hama    { background: #fee2e2; color: #991b1b; }

        .bs-waspada { background: #fef9c3; color: #713f12; }

        .bs-aman    { background: #dcfce7; color: #166534; }



        /* badge lama dipakai prediksi & riwayat */

        .status-high   { background:#ef4444; color:white; padding:4px 10px; border-radius:6px; font-size:12px; font-weight:600; }

        .status-medium { background:#facc15; color:#713f12; padding:4px 10px; border-radius:6px; font-size:12px; font-weight:600; }

        .status-low    { background:#22c55e; color:white; padding:4px 10px; border-radius:6px; font-size:12px; font-weight:600; }



        /* ── TOMBOL MANUAL ─────────────────────────────────────────── */

        .btn-manual {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            background: linear-gradient(135deg, #16a34a, #15803d);

            color: white;

            border: none;

            padding: 10px 20px;

            border-radius: 10px;

            cursor: pointer;

            font-size: 13px;

            font-weight: 600;

            font-family: var(--font);

            box-shadow: 0 4px 12px rgba(22,163,74,0.35);

            transition: all 0.2s;

            margin-bottom: 20px;

        }



        .btn-manual:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(22,163,74,0.45); }



        /* ── GRID LAYOUT ───────────────────────────────────────────── */

        .content-grid  { display: grid; grid-template-columns: 2fr 1fr; gap: 18px; margin-bottom: 18px; }

        .kamera-grid   { display: grid; grid-template-columns: 3fr 2fr; gap: 18px; margin-bottom: 18px; }

        .grid-prediksi { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }



        /* ── ANALISIS ──────────────────────────────────────────────── */

        .analisis-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8fafc; font-size: 13px; }

        .analisis-item:last-child { border-bottom: none; }

        .analisis-label { color: var(--abu); font-weight: 500; }

        .analisis-val   { font-weight: 700; color: var(--teks); font-family: var(--mono); }



        /* ── REKOMENDASI BOX ───────────────────────────────────────── */

        .rekomendasi-box { border-radius: 10px; padding: 14px; margin-top: 14px; border-left: 4px solid; }

        .rekomendasi-box.hama    { background: #fef2f2; border-color: #dc2626; }

        .rekomendasi-box.waspada { background: #fffbeb; border-color: #d97706; }

        .rekomendasi-box.aman    { background: #f0fdf4; border-color: #16a34a; }

        .rekomendasi-box .rek-judul { font-size: 13px; font-weight: 700; margin-bottom: 6px; }

        .rekomendasi-box .rek-isi   { font-size: 12px; line-height: 1.7; color: var(--teks2); }



        /* ── FUZZY METER ───────────────────────────────────────────── */

        .fuzzy-meter        { position: relative; margin: 16px 0; }

        .fuzzy-meter-val    { font-size: 42px; font-weight: 700; font-family: var(--mono); text-align: center; line-height: 1; margin-bottom: 4px; }

        .fuzzy-meter-label  { text-align: center; font-size: 12px; color: var(--abu); margin-bottom: 14px; }



        .meter-track {

            height: 14px;

            border-radius: 99px;

            background: linear-gradient(90deg, #22c55e 0%, #22c55e 44%, #facc15 44%, #facc15 70%, #ef4444 70%);

            position: relative;

            overflow: visible;

        }



        .meter-pointer {

            position: absolute;

            top: -5px;

            width: 24px; height: 24px;

            background: white;

            border: 3px solid var(--teks);

            border-radius: 50%;

            transform: translateX(-50%);

            transition: left 1s ease;

            box-shadow: 0 2px 8px rgba(0,0,0,0.2);

        }



        .meter-ticks { display: flex; justify-content: space-between; margin-top: 6px; font-size: 10px; font-family: var(--mono); color: var(--abu); }



        /* ── CHART ─────────────────────────────────────────────────── */

        .chart-wrap { position: relative; height: 220px; }

        canvas { width: 100% !important; }



        /* ── TABEL ─────────────────────────────────────────────────── */

        .tabel-wrap { overflow-x: auto; }



        .tabel-data { width: 100%; border-collapse: collapse; font-size: 13px; }

        .tabel-data thead tr { background: #f8fafc; }

        .tabel-data th { padding: 10px 14px; text-align: left; font-weight: 600; color: var(--abu); border-bottom: 2px solid var(--border); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }

        .tabel-data td { padding: 10px 14px; border-bottom: 1px solid #f8fafc; color: var(--teks); vertical-align: middle; }

        .tabel-data tr:hover td { background: #fafffe; }

        .tabel-data tr:last-child td { border-bottom: none; }



        /* smart-table dipakai riwayat */

        .smart-table { width: 100%; border-collapse: collapse; font-size: 13px; }

        .smart-table thead { background: #f8fafc; }

        .smart-table th { padding: 10px 12px; text-align: left; font-weight: 600; color: var(--abu); border-bottom: 2px solid #e2e8f0; white-space: nowrap; }

        .smart-table td { padding: 10px 12px; border-top: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

        .smart-table tr:hover td { background: #f8fafc; }



        /* ── CELL BADGE ────────────────────────────────────────────── */

        .cell  { padding: 4px 8px; border-radius: 6px; font-weight: 600; font-size: 12px; white-space: nowrap; }

        .suhu  { background: #fee2e2; color: #dc2626; }

        .udara { background: #dbeafe; color: #2563eb; }

        .tanah { background: #dcfce7; color: #16a34a; }



        /* ── PAGINATION ────────────────────────────────────────────── */

        .pagination { display: flex; gap: 4px; flex-wrap: wrap; }

        .pagination li { list-style: none; }

        .pagination li a,

        .pagination li span { display: block; padding: 5px 10px; border-radius: 6px; border: 1px solid #e2e8f0; text-decoration: none; color: var(--abu); font-size: 13px; }

        .pagination li.active span { background: #3b82f6; color: white; border: none; }

        .pagination li a:hover { background: #f1f5f9; }



        /* ── SECTION (prediksi & riwayat) ─────────────────────────── */

        .section { background: white; padding: 22px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); margin-bottom: 20px; }

        .section h3 { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 4px; }



        /* card-box lama (prediksi) */

        .card-box { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 24px; }

        .card-item { padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); color: white; }

        .card-item h4 { font-size: 13px; font-weight: 500; margin-bottom: 8px; opacity: 0.9; }

        .card-item p  { font-size: 28px; font-weight: 700; line-height: 1; }

        .card-item small { font-size: 12px; opacity: 0.85; display: block; margin-top: 6px; }

        .gradient-red  { background: linear-gradient(135deg, #ef4444, #b91c1c); }

        .gradient-blue { background: linear-gradient(135deg, #3b82f6, #1e40af); }

        .gradient-green{ background: linear-gradient(135deg, #22c55e, #15803d); }

        .card-item.status-high   { background: linear-gradient(135deg, #ef4444, #991b1b); }

        .card-item.status-medium { background: linear-gradient(135deg, #f59e0b, #b45309); }

        .card-item.status-low    { background: linear-gradient(135deg, #22c55e, #15803d); }



        /* ── KAMERA ────────────────────────────────────────────────── */

        .kamera-box { position: relative; background: #0a0a0a; border-radius: 10px; overflow: hidden; aspect-ratio: 16/9; display: flex; align-items: center; justify-content: center; }

        .kamera-box img { width: 100%; height: 100%; object-fit: cover; display: block; }



        .cam-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.75)); padding: 20px 14px 10px; display: flex; align-items: flex-end; justify-content: space-between; }

        .cam-timestamp { color: rgba(255,255,255,0.85); font-size: 11px; font-family: var(--mono); }



        .cam-status-chip { padding: 4px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; }

        .chip-hama    { background: #dc2626; color: white; }

        .chip-waspada { background: #d97706; color: white; }

        .chip-aman    { background: #16a34a; color: white; }



        .badge-live { position: absolute; top: 12px; left: 12px; background: #dc2626; color: white; font-size: 10px; font-weight: 700; letter-spacing: 1px; padding: 3px 9px; border-radius: 4px; display: flex; align-items: center; gap: 5px; }



        .cam-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; color: rgba(255,255,255,0.4); font-size: 13px; height: 100%; width: 100%; }

        .cam-placeholder .ph-icon { font-size: 48px; opacity: 0.4; }



        .refresh-info { display: flex; align-items: center; gap: 6px; margin-top: 10px; font-size: 12px; color: var(--abu); }

        .refresh-spin { width: 12px; height: 12px; border: 2px solid #d1d5db; border-top-color: var(--hijau); border-radius: 50%; animation: sf-spin 1.5s linear infinite; }



        .status-besar { text-align: center; padding: 20px 16px; border-radius: 12px; margin-bottom: 16px; }

        .status-besar.hama    { background: linear-gradient(135deg,#fef2f2,#fee2e2); border: 1px solid #fca5a5; }

        .status-besar.waspada { background: linear-gradient(135deg,#fffbeb,#fef9c3); border: 1px solid #fde68a; }

        .status-besar.aman    { background: linear-gradient(135deg,#f0fdf4,#dcfce7); border: 1px solid #86efac; }



        .status-besar .sb-icon  { font-size: 48px; display: block; margin-bottom: 10px; }

        .status-besar .sb-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--abu); margin-bottom: 4px; }

        .status-besar .sb-val   { font-size: 28px; font-weight: 800; line-height: 1; margin-bottom: 8px; }

        .sb-val.hama    { color: #dc2626; }

        .sb-val.waspada { color: #d97706; }

        .sb-val.aman    { color: #16a34a; }



        .status-besar .sb-fuzzy { font-family: var(--mono); font-size: 13px; color: var(--abu); background: rgba(255,255,255,0.6); display: inline-block; padding: 3px 10px; border-radius: 99px; margin-bottom: 10px; }

        .status-besar .sb-desc  { font-size: 13px; line-height: 1.6; color: var(--teks2); }



        .info-row { display: flex; align-items: center; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid #f8fafc; font-size: 13px; }

        .info-row:last-child { border-bottom: none; }

        .info-label { color: var(--abu); font-weight: 500; }

        .info-val   { font-weight: 700; color: var(--teks); font-family: var(--mono); }



        .aksi-list { list-style: none; padding: 0; margin: 0; }

        .aksi-list li { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; font-size: 13px; color: var(--teks2); border-bottom: 1px solid #f8fafc; line-height: 1.5; }

        .aksi-list li:last-child { border-bottom: none; }

        .aksi-list .aksi-num { width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; margin-top: 1px; }

        .num-hama    { background: #fee2e2; color: #dc2626; }

        .num-waspada { background: #fef9c3; color: #d97706; }

        .num-aman    { background: #dcfce7; color: #16a34a; }



        .foto-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px,1fr)); gap: 10px; }

        .foto-item { position: relative; border-radius: 8px; overflow: hidden; aspect-ratio: 4/3; cursor: pointer; border: 2px solid transparent; transition: border-color 0.2s, transform 0.2s; }

        .foto-item:hover { border-color: var(--hijau); transform: scale(1.02); }

        .foto-item img { width: 100%; height: 100%; object-fit: cover; }

        .foto-item .foto-badge { position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%); padding: 2px 7px; border-radius: 4px; font-size: 10px; font-weight: 700; white-space: nowrap; }

        .foto-placeholder { background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: var(--abu); padding: 16px; grid-column: 1/-1; }



        /* ── POPUP ─────────────────────────────────────────────────── */

        .popup-warning { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.55); display:none; align-items:center; justify-content:center; z-index:9999; }

        .popup-box { background:white; padding:30px 28px; border-radius:14px; text-align:center; width:340px; box-shadow:0 20px 40px rgba(0,0,0,0.2); }

        .popup-box .popup-icon { font-size:44px; margin-bottom:10px; }

        .popup-box h3 { color:#dc2626; font-size:18px; margin-bottom:8px; font-weight:700; }

        .popup-box p  { color:#475569; font-size:14px; line-height:1.5; }

        .popup-box button { margin-top:18px; padding:10px 24px; border:none; background:#ef4444; color:white; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600; font-family:var(--font); transition:background 0.15s; }

        .popup-box button:hover { background:#dc2626; }



        /* ── RESPONSIVE ────────────────────────────────────────────── */

        @media (max-width: 900px) {
            .sensor-grid  { grid-template-columns: repeat(2,1fr); }
            .mini-grid    { grid-template-columns: repeat(2,1fr); }
            .content-grid { grid-template-columns: 1fr; }
            .kamera-grid  { grid-template-columns: 1fr; }
            .grid-prediksi{ grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand">
        <span>🌽 SmartFarm<small>Monitoring Hama Jagung</small></span>
    </div>
  {{-- Info User (Petani) --}}

    @auth

    <div style="padding: 15px 12px; background: #1e293b; border-radius: 10px; margin: 10px 0; border-left: 3px solid var(--hijau);">

        <small style="color: #64748b; font-size: 10px; text-transform: uppercase;">Petani Aktif</small>

        <p style="color: white; font-size: 14px; font-weight: 600;">{{ Auth::user()->name }}</p>

    </div>

    @endauth


    <div class="sidebar-label">Menu Utama</div>

    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i data-feather="home"></i> Dashboard
    </a>
    <a href="{{ route('kamera') }}" class="{{ request()->routeIs('kamera') ? 'active' : '' }}">
        <i data-feather="camera"></i> Kamera
    </a>
    <a href="{{ route('prediksi') }}" class="{{ request()->routeIs('prediksi') ? 'active' : '' }}">
        <i data-feather="activity"></i> Prediksi
    </a>
    <a href="{{ route('riwayat') }}" class="{{ request()->routeIs('riwayat') ? 'active' : '' }}">
        <i data-feather="clock"></i> Riwayat
    </a>

    {{-- Tombol Logout --}}

    <div style="margin-top: auto; padding-top: 20px;">

        <form action="{{ route('logout') }}" method="POST">

            @csrf

            <button type="submit" style="width:100%; background: #ef4444; color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; font-weight:600; display:flex; align-items:center; gap:8px; justify-content:center;">

                <i data-feather="log-out" style="width:14px;"></i> Keluar

            </button>

        </form>

    </div>

    <div class="sidebar-status">

        <p>Status Sistem Saat Ini</p>

        @php

            $badgeColor = match($statusGlobal ?? 'AMAN') {

                'HAMA'    => '#dc2626',

                'WASPADA' => '#d97706',

                default   => '#16a34a',

            };

        @endphp

        <span style="display:inline-block;padding:4px 12px;background:{{ $badgeColor }};color:white;border-radius:6px;font-size:12px;font-weight:700;">

            {{ $statusGlobal ?? 'AMAN' }}

        </span>

    </div>

</div>



<!-- MAIN -->

<div class="main">

    @if(session('success'))

        <div class="alert-success">✅ {{ session('success') }}</div>

    @endif



    @yield('content')

</div>



<!-- POPUP -->

<div id="popupWarning" class="popup-warning">

    <div class="popup-box">

        <div class="popup-icon">🚨</div>

        <h3>PERINGATAN HAMA!</h3>

        <p>Sistem mendeteksi risiko serangan hama tinggi!<br>Segera periksa kondisi lahan jagung Anda.</p>

    <div style="display:flex; gap:10px; justify-content:center; margin-top:18px;">

        <button onclick="hidePopupTemporary()"
            style="background:#64748b;">
            Sembunyikan
        </button>

        <button onclick="keepPopupActive()">
            Tetap Ingatkan
        </button>
    </div>

    </div>

</div>



<script>

    feather.replace();

    function showPopup() {
        document.getElementById('popupWarning').style.display = 'flex';
    }

    function hidePopup() {
        document.getElementById('popupWarning').style.display = 'none';
    }

    // tombol "Sembunyikan"
    // popup hilang walau pindah halaman
    function hidePopupTemporary() {

        hidePopup();

        localStorage.setItem('popup_hidden', '1');
    }

    // tombol "Tetap Ingatkan"
    // popup tetap muncul tiap pindah halaman
    function keepPopupActive() {

        hidePopup();

        localStorage.removeItem('popup_hidden');
    }

    window.addEventListener('load', function () {

        var status = "{{ $statusGlobal ?? 'AMAN' }}";

        var popupHidden = localStorage.getItem('popup_hidden');

        // kalau status HAMA dan belum disembunyikan
        if (status === 'HAMA' && !popupHidden) {
            showPopup();
        }

        // kalau status sudah aman
        // reset supaya nanti kalau HAMA lagi popup muncul lagi
        if (status !== 'HAMA') {
            localStorage.removeItem('popup_hidden');
        }

    });

</script>

</body>

</html>