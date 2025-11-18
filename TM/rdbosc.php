<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/auth.php';
require_once 'data.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$current_user_id = $_SESSION['user_id'] ?? null;

if (!function_exists('getApplicationStatusCounts')) die("Function getApplicationStatusCounts() not found in data.php");
if (!function_exists('getDivisionTotalByStatus')) die("Function getDivisionTotalByStatus() not found in data.php");
if (!function_exists('getDivisionOnTimeDelayedByStatus')) die("Function getDivisionOnTimeDelayedByStatus() not found in data.php");

try {
    $statusCounts   = getApplicationStatusCounts();
    $pendingDivs    = getDivisionTotalByStatus('pending');
    $reviewDivs     = getDivisionOnTimeDelayedByStatus('under-review');
    $responseDivs   = getDivisionTotalByStatus('awaiting-applicant-response');
    $completedDivs  = getDivisionTotalByStatus('completed');
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

function renderAllDivisions($divisions, $isReviewCard = false) {
    $services = array_keys($divisions);
    if (empty($services)) {
        echo '<p class="text-muted text-center">No data</p>';
        return;
    }
    ?>
    <div class="division-list <?= $isReviewCard ? 'review-mode' : 'total-mode' ?>">
        <?php foreach ($services as $svc):
            $val     = $divisions[$svc];
            $ontime  = $isReviewCard ? ($val['ontime'] ?? 0) : 0;
            $delayed = $isReviewCard ? ($val['delayed'] ?? 0) : 0;
            $total   = $isReviewCard ? $ontime + $delayed : $val;
        ?>
            <div class="division-row">
                <span class="division-label" title="<?=htmlspecialchars($svc)?>"><?=htmlspecialchars($svc)?></span>
                <?php if ($isReviewCard): ?>
                    <div class="chip ontime"><?=number_format($ontime)?></div>
                    <div class="chip delayed"><?=number_format($delayed)?></div>
                <?php else: ?>
                    <div class="chip total"><?=number_format($total)?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Rwanda FDA - OSC Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@600;700;800;900&display=swap" rel="stylesheet"/>
<style>
:root {
    --bg:#0a0a14; --text:#fff; --muted:#aaa; --border:rgba(255,255,255,.08);
    --glass:rgba(30,30,46,.8); --shadow:0 10px 25px rgba(0,0,0,.4);
    --header-footer-soft: rgba(20,20,30,0.8); /* dark mode header/footer */
}
[data-theme="light"] {
    --bg:#f5f8fc; 
    --text:#1a1a1a; 
    --muted:#555; 
    --border:rgba(0,0,0,.08); 
    --glass: rgba(255,255,255,.85);
    --header-footer-soft: rgba(100,150,255,0.12); /* soft blue in light mode */
}

html, body {height:100%; width:100%; margin:0; padding:0; background:var(--bg); color:var(--text); font-family:'Inter',sans-serif;}
body {display:flex; flex-direction:column; overflow:hidden;}

.header {text-align:center; padding:1.2rem; background:var(--header-footer-soft); backdrop-filter:blur(10px); border-bottom:1px solid var(--border);}
.header h1 {font-size:2.8rem; font-weight:900; margin:0;}
.header p {font-size:1.2rem; color:var(--muted); margin:.3rem 0 0;}

.theme-toggle {
    position:absolute; top:1rem; right:1.5rem; width:50px; height:50px; border-radius:50%;
    background:rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center;
    cursor:pointer; color:#ffd43b; font-size:1.3rem; backdrop-filter:blur(10px); box-shadow:0 3px 10px rgba(0,0,0,.2);
}
.theme-toggle:hover {transform:scale(1.1);}

.legend {display:flex; justify-content:center; gap:2rem; margin:1rem 0; font-size:1rem; font-weight:700; color:var(--muted);}
.legend-item{display:flex; align-items:center; gap:.5rem;}
.legend-chip{width:20px; height:20px; border-radius:5px;}
.legend-chip.ontime{background:#51cf66;}
.legend-chip.delayed{background:#ff6b6b;}
.legend-chip.total{background:#6c757d;}

.dashboard-container {flex:1; display:flex; align-items:center; justify-content:center; padding:1rem; overflow:hidden;}
.stats-grid {display:grid; grid-template-columns:repeat(4,1fr); gap:1.5rem; width:100%; height:100%; max-width:2000px;}

.home-link {
    position: absolute; top: 1rem; left: 1.5rem; color: #ffd43b; font-size:1.5rem; text-decoration:none;
    display:flex; align-items:center; justify-content:center; width:50px; height:50px; border-radius:50%;
    background: rgba(255,255,255,.2); backdrop-filter:blur(10px); box-shadow:0 3px 10px rgba(0,0,0,.2); transition: transform .3s ease;
}
.home-link:hover { transform: scale(1.1); }

.stat-card {
    background:var(--glass); border-radius:1.2rem; padding:1.5rem; box-shadow:var(--shadow);
    border-left:6px solid var(--color); display:flex; flex-direction:column; min-width:0; overflow:hidden;
}
[data-theme="light"] .card-pending { background: rgba(230,57,70,.08);}
[data-theme="light"] .card-review { background: rgba(244,140,6,.08);}
[data-theme="light"] .card-response { background: rgba(58,134,255,.08);}
[data-theme="light"] .card-completed { background: rgba(42,157,143,.08); }

.stat-icon {
    width:70px; height:70px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-size:2rem; margin-bottom:1rem; background:var(--color); color:#fff;
}
.stat-title {font-size:1.4rem; font-weight:800; margin:0; color:var(--color);}
.stat-value {font-size:4rem; font-weight:900; line-height:1; margin:.3rem 0; color:var(--color);
    animation:pulse 1s infinite alternate;}
@keyframes pulse {0%{transform:scale(1);opacity:1;}50%{transform:scale(1.12);opacity:0.85;}100%{transform:scale(1);opacity:1;}}
.stat-subtitle {font-size:1.05rem; font-weight:600; margin-bottom:.5rem; color:var(--subtitle-color);}

.division-list {flex:1; overflow-y:auto; padding-right:5px; margin-top:.3rem; font-size:1rem;}
.division-row {display:flex; align-items:center; gap:.5rem; margin-bottom:.25rem;}
.division-label {flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:700;}
.chip {padding:.25rem .6rem; border-radius:.5rem; font-weight:800; font-size:1rem; min-width:45px; text-align:center;}
.chip.ontime{background:#51cf66; color:#fff;}
.chip.delayed{background:#ff6b6b; color:#fff;}
.chip.total{background:#6c757d; color:#fff;}

.footer-refresh {
    text-align:center; padding:.8rem; background:var(--header-footer-soft); font-size:1.1rem; color:var(--muted); border-top:1px solid var(--border); position:sticky; bottom:0;
}

/* Card Colors & subtitle soft colors */
.card-pending{--color:#e63946; --subtitle-color:#f28c8c;}
.card-review{--color:#f48c06; --subtitle-color:#f5b97b;}
.card-response{--color:#3a86ff; --subtitle-color:#7fb5ff;}
.card-completed{--color:#2a9d8f; --subtitle-color:#7fd9c8;}

/* RESPONSIVE */
@media(max-width:1600px){.stat-value{font-size:3.2rem;}.stat-title{font-size:1.2rem;}.chip{font-size:.95rem;}}
@media(max-width:1300px){.stat-value{font-size:2.8rem;}.chip{font-size:.9rem;}}
@media(max-width:1100px){.stats-grid{grid-template-columns:repeat(2,1fr);}.stat-value{font-size:3rem;}}
@media(max-width:768px){.stats-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>

<div class="header">
  <h1>Rwanda FDA - OSC Dashboard</h1>
  <a href="../landing_page.php" class="home-link" title="Go to Homepage"><i class="fas fa-home"></i></a>
  <p>Real-time Application Status Overview</p>
  <div class="theme-toggle" id="themeToggle"><i class="fas fa-moon"></i></div>
</div>

<div class="legend">
  <div class="legend-item"><span>Service</span></div>
  <div class="legend-item"><div class="legend-chip ontime"></div><span>On-Time</span></div>
  <div class="legend-item"><div class="legend-chip delayed"></div><span>Delayed</span></div>
  <div class="legend-item"><div class="legend-chip total"></div><span>Total</span></div>
</div>

<div class="dashboard-container">
  <div class="stats-grid">
    <div class="stat-card card-pending">
      <div class="stat-icon"><i class="fas fa-clock"></i></div>
      <h3 class="stat-title">Pending</h3>
      <div class="stat-value"><?=number_format($statusCounts['pending'] ?? 0)?></div>
      <p class="stat-subtitle">Awaiting Initial Review</p>
      <?php renderAllDivisions($pendingDivs,false); ?>
    </div>

    <div class="stat-card card-review">
      <div class="stat-icon"><i class="fas fa-search"></i></div>
      <h3 class="stat-title">Under Review</h3>
      <div class="stat-value"><?=number_format($statusCounts['under-review'] ?? 0)?></div>
      <p class="stat-subtitle">Currently Being Evaluated</p>
      <?php renderAllDivisions($reviewDivs,true); ?>
    </div>

    <div class="stat-card card-response">
      <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
      <h3 class="stat-title">Awaiting Response</h3>
      <div class="stat-value"><?=number_format($statusCounts['awaiting-applicant-response'] ?? 0)?></div>
      <p class="stat-subtitle">Waiting for Applicant Feedback</p>
      <?php renderAllDivisions($responseDivs,false); ?>
    </div>

    <div class="stat-card card-completed">
      <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
      <h3 class="stat-title">Completed</h3>
      <div class="stat-value"><?=number_format($statusCounts['completed'] ?? 0)?></div>
      <p class="stat-subtitle">Successfully Processed</p>
      <?php renderAllDivisions($completedDivs,false); ?>
    </div>
  </div>
</div>

<div class="footer-refresh">
  Auto-refreshing every 30 minutes | Last updated: <span id="clock"></span>
</div>

<script>
const toggleBtn = document.getElementById('themeToggle'),
      body = document.body,
      icon = toggleBtn.querySelector('i');
const saved = localStorage.getItem('theme') || 'dark';
body.setAttribute('data-theme', saved);
icon.className = saved === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
toggleBtn.addEventListener('click', () => {
    const isDark = body.getAttribute('data-theme') === 'dark';
    const newTheme = isDark ? 'light' : 'dark';
    body.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    icon.className = newTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
});

function updateClock(){
    const d=new Date();
    document.getElementById('clock').textContent=d.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
setInterval(updateClock,1000);
updateClock();

// Auto-refresh every 30 minutes
setInterval(()=>location.reload(),30*60*1000);
</script>

</body>
</html>
