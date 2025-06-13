<?php
// main.php
require_once __DIR__ . '/../config.php';
$settings = load_settings();
$theme = $settings['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/bootstrap/icons/bootstrap-icons.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            overflow: hidden;
        }
        .main-container {
            display: flex;
            height: 100%;
            width: 100%;
            background-color: #f8f9fa;
            transition: background-color 0.3s ease;
        }
        .menu-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            flex-basis: 50%;
            padding: 2rem;
            background: linear-gradient(to right, #ffffff, #e9ecef);
            transition: background 0.3s ease;
        }
        .poster-panel {
            flex-basis: 50%;
            background-image: url('/img/poster.jpg');
            background-size: cover;
            background-position: center;
        }
        .clock {
            font-size: 4rem;
            font-weight: 300;
            color: #343a40;
        }
        .date {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 2rem;
        }
        .app-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            width: 100%;
            max-width: 400px;
        }
        .app-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            border-radius: 12px;
            background-color: rgba(255, 255, 255, 0.7);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            text-decoration: none;
            color: #343a40;
            transition: transform 0.2s, box-shadow 0.2s, background-color 0.3s;
        }
        .app-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            color: #0d6efd;
        }
        .app-button i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        .app-button span {
            font-weight: 500;
        }

        /* --- Dark Mode Overrides --- */
        [data-bs-theme="dark"] .main-container {
            background-color: #121212;
        }
        [data-bs-theme="dark"] .menu-panel {
            background: linear-gradient(to right, #1f2937, #111827);
        }
        [data-bs-theme="dark"] .clock {
            color: #e5e7eb;
        }
        [data-bs-theme="dark"] .date {
            color: #9ca3af;
        }
        [data-bs-theme="dark"] .app-button {
            background-color: rgba(55, 65, 81, 0.7);
            color: #e5e7eb;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        [data-bs-theme="dark"] .app-button:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            color: #38bdf8;
        }
        [data-bs-theme="dark"] .app-button:hover i {
            color: #38bdf8 !important;
        }

        /* Mobile Responsive */
        @media (max-width: 767.98px) {
            .main-container { flex-direction: column; }
            .menu-panel { flex-basis: auto; height: 55%; justify-content: center; }
            .poster-panel { flex-basis: auto; height: 45%; }
            .clock { font-size: 3rem; }
            .app-grid { gap: 1rem; }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="menu-panel">
            <div id="clock" class="clock"></div>
            <div id="date" class="date"></div>
            
            <div class="app-grid">
                <a href="/files" class="app-button">
                    <i class="bi bi-hdd-stack-fill text-primary"></i>
                    <span>My Storage</span>
                </a>
                
                <a href="http://192.168.1.36:8080" target="_blank" rel="noopener noreferrer" class="app-button">
                    <i class="bi bi-house-door-fill text-success"></i>
                    <span>CasaOS</span>
                </a>
            </div>
        </div>
        <div class="poster-panel"></div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const timeOpts = { hour: '2-digit', minute: '2-digit', hour12: false };
            const dateOpts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };

            document.getElementById('clock').textContent = now.toLocaleTimeString('en-US', timeOpts);
            document.getElementById('date').textContent = now.toLocaleDateString('en-US', dateOpts);
        }

        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>