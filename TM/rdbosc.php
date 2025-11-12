<?php
require_once '../includes/auth.php';
require_once 'data.php';
$current_user_id = $_SESSION['user_id'];
$statusCounts = getApplicationStatusCounts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Rwanda FDA - OSC Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@700;800;900&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --bg: #0a0a14;
            --text: #ffffff;
            --text-muted: #cccccc;
            --border: rgba(255,255,255,0.08);
            --glass-bg: rgba(30, 30, 46, 0.78);
            --glass-border: rgba(255, 255, 255, 0.15);
            --shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }

        [data-theme="light"] {
            --bg: #f5f8fc;
            --text: #1a1a1a;
            --text-muted: #555555;
            --border: rgba(0,0,0,0.08);
            --glass-bg: rgba(255, 255, 255, 0.82);
            --glass-border: rgba(0, 0, 0, 0.1);
            --shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; transition: all 0.3s ease; }

        html, body { height: 100%; overflow: hidden; font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); }
        body { display: flex; flex-direction: column; }

        /* HEADER */
        .header {
            text-align: center; padding: 1.5rem 1rem; border-bottom: 1px solid var(--border);
            background: var(--glass-bg); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            position: relative; flex-shrink: 0;
        }
        .header h1 { font-size: 3.2rem; font-weight: 900; margin: 0; color: var(--text); }
        .header p { font-size: 1.3rem; color: var(--text-muted); margin-top: 0.4rem; font-weight: 700; }

        /* TOGGLE */
        .theme-toggle {
            position: absolute; top: 1.2rem; right: 1.8rem; width: 60px; height: 60px;
            background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color: #ffd43b; cursor: pointer; backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .theme-toggle:hover { transform: scale(1.1); }
        .theme-toggle i { transition: transform 0.5s ease; }

        /* DASHBOARD */
        .dashboard-container { flex: 1; padding: 2rem 3rem; display: flex; align-items: center; justify-content: center; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.8rem; width: 100%; max-width: 1800px; }

        /* CARD BASE */
        .stat-card {
            background: var(--glass-bg); border-radius: 1.6rem; padding: 2.2rem;
            box-shadow: var(--shadow); border-left: 7px solid var(--border-color);
            backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border); display: flex; flex-direction: column;
            height: 100%; min-height: 380px;
        }

        /* === CARD 1: PENDING === */
        .card-pending {
            --border-color: #e63946;
            --title-color: #f94144;
            --number-color: #d62828;
            --icon-bg: rgba(230, 57, 70, 0.18);
            --card-bg: rgba(230, 57, 70, 0.08);
        }
        [data-theme="light"] .card-pending {
            --border-color: #e63946;
            --title-color: #e63946;
            --number-color: #c1121f;
            --icon-bg: rgba(230, 57, 70, 0.15);
            --card-bg: rgba(230, 57, 70, 0.06);
        }

        /* === CARD 2: UNDER REVIEW === */
        .card-review {
            --border-color: #f77f00;
            --title-color: #f48c06;
            --number-color: #F7981BFF;
            --icon-bg: rgba(247, 127, 0, 0.18);
            --card-bg: rgba(247, 127, 0, 0.08);
        }
        [data-theme="light"] .card-review {
            --border-color: #f77f00;
            --title-color: #f77f00;
            --number-color: #F99634FF;
            --icon-bg: rgba(247, 127, 0, 0.15);
            --card-bg: rgba(247, 127, 0, 0.06);
        }

        /* === CARD 3: AWAITING RESPONSE === */
        .card-completed{
            --border-color: #2a9d8f;
            --title-color: #2a9d8f;
            --number-color: #2a9d8f;
            --icon-bg: rgba(42, 157, 143, 0.18);
            --card-bg: rgba(42, 157, 143, 0.08);
        }
        [data-theme="light"] .card-completed {
            --border-color: #2a9d8f;
            --title-color: #2a9d8f;
            --number-color: #2a9d8f;
            --icon-bg: rgba(42, 157, 143, 0.15);
            --card-bg: rgba(42, 157, 143, 0.06);
        }

        /* === CARD 4: COMPLETED === */
        .card-response {
            --border-color: #3a86ff;
            --title-color: #3a86ff;
            --number-color: #1d4ed8;
            --icon-bg: rgba(58, 134, 255, 0.18);
            --card-bg: rgba(58, 134, 255, 0.08);
        }
        [data-theme="light"] .card-response {
            --border-color: #3a86ff;
            --title-color: #3a86ff;
            --number-color: #1d4ed8;
            --icon-bg: rgba(58, 134, 255, 0.15);
            --card-bg: rgba(58, 134, 255, 0.06);
        }

        /* APPLY COLORS */
        .stat-card { background: var(--card-bg); }
        .stat-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 7px; background: var(--border-color); border-radius: 1.6rem 0 0 1.6rem; }
        .stat-icon { background: var(--icon-bg); color: var(--border-color); }
        .stat-title { color: var(--title-color) !important; }
        .stat-value { color: var(--number-color); animation: pulse 2s infinite; }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.9; transform: scale(1.02); }
        }

        /* ELEMENTS */
        .stat-icon { width: 75px; height: 75px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.2rem; }
        .stat-title { font-size: 1.5rem; font-weight: 800; margin: 0 0 0.8rem; display: flex; align-items: center; gap: 10px; }
        .stat-value { font-size: 4.2rem; font-weight: 900; margin: 0.4rem 0; line-height: 1; }
        .stat-subtitle { font-size: 1.21rem; color: var(--text-muted); font-weight: 700; margin-bottom: 1.2rem; }

        .status-list { display: flex; flex-direction: column; gap: 0.8rem; margin-top: auto; }
        .status-item { display: flex; align-items: center; gap: 12px; font-size: 1.1rem; font-weight: 600; color: var(--text-muted); }
        .status-dot { width: 14px; height: 14px; border-radius: 50%; }
        .status-dot.ontime { background: #51cf66; }
        .status-dot.delayed { background: #ff6b6b; }

        .category-icons { display: flex; justify-content: center; gap: 1.6rem; margin-top: 1.8rem; }
        .cat-icon { width: 60px; height: 60px; background: rgba(255,255,255,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; color: var(--text); position: relative; }
        .cat-icon:hover .tooltip { visibility: visible; opacity: 1; transform: translate(-50%, -10px); }
        .tooltip { visibility: hidden; opacity: 0; position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); background: var(--glass-bg); color: var(--text); padding: 8px 14px; border-radius: 10px; font-size: 1rem; white-space: nowrap; box-shadow: var(--shadow); transition: all 0.3s ease; z-index: 10; margin-bottom: 10px; font-weight: 700; border: 1px solid var(--glass-border); backdrop-filter: blur(10px); }
        .tooltip::after { content: ''; position: absolute; top: 100%; left: 50%; transform: translateX(-50%); border: 6px solid transparent; border-top-color: var(--glass-bg); }

        .footer-refresh { text-align: center; padding: 1rem; color: var(--text-muted); font-size: 1.1rem; font-weight: 600; border-top: 1px solid var(--border); background: var(--glass-bg); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }

        /* RESPONSIVE */
        @media (max-width: 1600px) { .stats-grid { gap: 1.5rem; } .stat-value { font-size: 3.8rem; } .header h1 { font-size: 2.8rem; } }
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: 1fr; } .dashboard-container { padding: 1.5rem; } }
        @media (max-height: 800px) { .stat-value { font-size: 3.5rem; } .header h1 { font-size: 2.6rem; } }
    </style>
</head>
<body data-theme="dark">

    <div class="header">
        <h1>Rwanda FDA - OSC Dashboard</h1>
        <p>Real-time Application Status Overview</p>
        <div class="theme-toggle" id="themeToggle">
            <i class="fas fa-moon"></i>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="stats-grid">

            <!-- PENDING -->
            <div class="stat-card card-pending">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <h3 class="stat-title">Pending</h3>
                <div class="stat-value"><?= number_format($statusCounts['pending']) ?></div>
                <p class="stat-subtitle">Awaiting Initial Review</p>
                <div class="status-list">
                    <div class="status-item"><span class="status-dot ontime"></span> On Time</div>
                    <div class="status-item"><span class="status-dot delayed"></span> Delayed</div>
                </div>
                <div class="category-icons">
                    <div class="cat-icon"><i class="fas fa-heartbeat"></i><span class="tooltip">Medical Devices</span></div>
                    <div class="cat-icon"><i class="fas fa-pills"></i><span class="tooltip">Pharmaceuticals</span></div>
                    <div class="cat-icon"><i class="fas fa-capsules"></i><span class="tooltip">Supplements</span></div>
                </div>
            </div>

            <!-- UNDER REVIEW -->
            <div class="stat-card card-review">
                <div class="stat-icon"><i class="fas fa-search"></i></div>
                <h3 class="stat-title">Under Review</h3>
                <div class="stat-value"><?= number_format($statusCounts['under-review']) ?></div>
                <p class="stat-subtitle">Currently Being Evaluated</p>
                <div class="status-list">
                    <div class="status-item"><span class="status-dot ontime"></span> On Time</div>
                    <div class="status-item"><span class="status-dot delayed"></span> Delayed</div>
                </div>
                <div class="category-icons">
                    <div class="cat-icon"><i class="fas fa-heartbeat"></i><span class="tooltip">Medical Devices</span></div>
                    <div class="cat-icon"><i class="fas fa-pills"></i><span class="tooltip">Pharmaceuticals</span></div>
                    <div class="cat-icon"><i class="fas fa-capsules"></i><span class="tooltip">Supplements</span></div>
                </div>
            </div>

            <!-- AWAITING RESPONSE -->
            <div class="stat-card card-response">
                <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                <h3 class="stat-title">Awaiting Response</h3>
                <div class="stat-value"><?= number_format($statusCounts['awaiting-applicant-response']) ?></div>
                <p class="stat-subtitle">Waiting for Applicant Input</p>
                <div class="status-list">
                    <div class="status-item"><span class="status-dot ontime"></span> On Time</div>
                    <div class="status-item"><span class="status-dot delayed"></span> Delayed</div>
                </div>
                <div class="category-icons">
                    <div class="cat-icon"><i class="fas fa-heartbeat"></i><span class="tooltip">Medical Devices</span></div>
                    <div class="cat-icon"><i class="fas fa-pills"></i><span class="tooltip">Pharmaceuticals</span></div>
                    <div class="cat-icon"><i class="fas fa-capsules"></i><span class="tooltip">Supplements</span></div>
                </div>
            </div>

            <!-- COMPLETED -->
            <div class="stat-card card-completed">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <h3 class="stat-title">Completed</h3>
                <div class="stat-value"><?= number_format($statusCounts['completed']) ?></div>
                <p class="stat-subtitle">Successfully Processed</p>
                <div class="status-list">
                    <div class="status-item"><span class="status-dot ontime"></span> On Time</div>
                    <div class="status-item"><span class="status-dot delayed"></span> Delayed</div>
                </div>
                <div class="category-icons">
                    <div class="cat-icon"><i class="fas fa-heartbeat"></i><span class="tooltip">Medical Devices</span></div>
                    <div class="cat-icon"><i class="fas fa-pills"></i><span class="tooltip">Pharmaceuticals</span></div>
                    <div class="cat-icon"><i class="fas fa-capsules"></i><span class="tooltip">Supplements</span></div>
                </div>
            </div>

        </div>
    </div>

    <div class="footer-refresh">
        Auto-refreshing every 30 seconds | Last updated: <span id="clock"></span>
    </div>

    <script>
        const toggleBtn = document.getElementById('themeToggle');
        const body = document.body;
        const icon = toggleBtn.querySelector('i');
        const savedTheme = localStorage.getItem('theme') || 'dark';

        body.setAttribute('data-theme', savedTheme);
        icon.className = savedTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';

        toggleBtn.addEventListener('click', () => {
            const isDark = body.getAttribute('data-theme') === 'dark';
            const newTheme = isDark ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            icon.className = newTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
        });

        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('en-US', {
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        }
        setInterval(updateClock, 1000);
        updateClock();

        setInterval(() => location.reload(), 30000);
    </script>
</body>
</html>