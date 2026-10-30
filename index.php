<?php
require_once __DIR__ . '/includes/admin_header_pre.php';
$adminPageTitle = 'Dashboard';
require_once __DIR__ . '/includes/admin_header_output.php';

$total_appts = $conn->query("SELECT COUNT(*) as c FROM appointments")->fetch_assoc()['c'];
$accepted    = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status='accepted'")->fetch_assoc()['c'];
$total_cust  = $conn->query("SELECT COUNT(*) as c FROM customers")->fetch_assoc()['c'];
$total_gowns = $conn->query("SELECT COUNT(*) as c FROM gowns")->fetch_assoc()['c'];

// Get all appointment dates for calendar dots
$appt_dates = [];
$res = $conn->query("SELECT DISTINCT appointment_date FROM appointments WHERE status != 'cancelled' AND appointment_date >= DATE_FORMAT(NOW(),'%Y-%m-01') AND appointment_date < DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 2 MONTH),'%Y-%m-01')");
while ($r = $res->fetch_assoc()) $appt_dates[] = $r['appointment_date'];

// Selected date schedule
$sel_date = $_GET['date'] ?? '';
$schedule = null;
if ($sel_date) {
    $safe_date = $conn->real_escape_string($sel_date);
    $schedule = $conn->query("SELECT a.*, c.email as cust_email FROM appointments a LEFT JOIN customers c ON a.customer_id=c.id WHERE a.appointment_date='$safe_date' AND a.status != 'cancelled' ORDER BY a.appointment_time ASC");
}
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-family:'Cormorant Garamond',serif;font-size:34px;font-weight:400;margin-bottom:4px;">Dashboard</h1>
        <span style="color:#888;font-size:13px;"><?= date('l, F j, Y') ?></span>
    </div>
</div>

<!-- Stat Cards -->
<div class="stat-cards" style="margin-bottom:28px;">
    <div style="background:white;border-radius:10px;padding:22px 24px;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
        <div style="font-family:'Cormorant Garamond',serif;font-size:44px;font-weight:300;color:var(--gold);line-height:1;"><?= $total_appts ?></div>
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:#888;margin-top:6px;">Total Appointments</div>
        <div style="font-size:12px;color:#aaa;margin-top:4px;"><?= $accepted ?> accepted</div>
    </div>
    <div style="background:white;border-radius:10px;padding:22px 24px;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
        <div style="font-family:'Cormorant Garamond',serif;font-size:44px;font-weight:300;color:#27AE60;line-height:1;"><?= $accepted ?></div>
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:#888;margin-top:6px;">Accepted</div>
        <div style="font-size:12px;color:#aaa;margin-top:4px;">Auto-accepted on booking</div>
    </div>
    <div style="background:white;border-radius:10px;padding:22px 24px;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
        <div style="font-family:'Cormorant Garamond',serif;font-size:44px;font-weight:300;color:#3B82F6;line-height:1;"><?= $total_cust ?></div>
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:#888;margin-top:6px;">Registered Customers</div>
        <div style="font-size:12px;color:#aaa;margin-top:4px;"><?= $total_gowns ?> gowns in catalog</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:320px 1fr;gap:20px;align-items:start;" id="cal-layout">

<!-- ── Appointment Calendar ── -->
<div style="background:white;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:24px;">
    <h3 style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:400;margin-bottom:16px;">Appointment Calendar</h3>
    <div id="adminCal"></div>
    <div style="margin-top:14px;display:flex;align-items:center;gap:8px;font-size:12px;color:#888;">
        <span style="width:10px;height:10px;border-radius:50%;background:var(--gold);display:inline-block;"></span> Has appointments
    </div>
</div>

<!-- ── Day Schedule ── -->
<div style="background:white;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:24px;">
    <?php if (!$sel_date): ?>
    <div style="text-align:center;padding:48px 20px;color:#aaa;">
        <i class="fas fa-calendar-alt" style="font-size:48px;color:var(--border);margin-bottom:16px;"></i>
        <p style="font-size:15px;">Click a date on the calendar to see the schedule</p>
    </div>
    <?php else: ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--border);">
        <h3 style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:400;">
            <?= date('l, F j, Y', strtotime($sel_date)) ?>
        </h3>
        <a href="appointments.php" class="btn btn-outline btn-sm">View All</a>
    </div>

    <?php
    // Build 8AM–6PM time grid
    $time_slots = [];
    for ($h = 8; $h <= 17; $h++) {
        $time_slots[sprintf('%02d:00:00', $h)] = null;
    }
    if ($schedule && $schedule->num_rows > 0) {
        while ($a = $schedule->fetch_assoc()) {
            $t = $a['appointment_time'];
            $time_slots[$t] = $a;
        }
    }
    ?>

    <div style="display:flex;flex-direction:column;gap:8px;">
        <?php foreach ($time_slots as $time => $appt): ?>
        <div style="display:grid;grid-template-columns:70px 1fr;gap:12px;align-items:center;min-height:52px;">
            <div style="font-size:12px;font-weight:600;color:#888;text-align:right;padding-right:12px;border-right:2px solid <?= $appt ? 'var(--gold)' : 'var(--border)' ?>;">
                <?= date('g:i A', strtotime($time)) ?>
            </div>
            <?php if ($appt): ?>
            <div style="background:var(--gold-pale);border-radius:8px;padding:10px 14px;border-left:3px solid var(--gold);">
                <div style="font-weight:600;font-size:13px;color:var(--dark);"><?= htmlspecialchars($appt['first_name'] . ' ' . $appt['last_name']) ?></div>
                <div style="font-size:12px;color:var(--gray);margin-top:2px;">
                    <?= htmlspecialchars($appt['appointment_for']) ?> &nbsp;·&nbsp; <?= htmlspecialchars($appt['appointment_type']) ?>
                    <?php if (!empty($appt['gown_name'])): ?>&nbsp;·&nbsp; 👗 <?= htmlspecialchars($appt['gown_name']) ?><?php endif; ?>
                </div>
                <div style="font-size:11px;margin-top:4px;">
                    <span style="color:var(--gold);"><?= htmlspecialchars($appt['phone']) ?></span>
                    <?php
                    $sc=['accepted'=>'#DCFCE7','completed'=>'#E0F2FE','cancelled'=>'#FEE2E2'];
                    $st=['accepted'=>'#166534','completed'=>'#075985','cancelled'=>'#991B1B'];
                    echo '<span style="margin-left:8px;padding:1px 8px;border-radius:10px;font-size:10px;background:'.($sc[$appt['status']]??'#eee').';color:'.($st[$appt['status']]??'#333').';">'.ucfirst($appt['status']).'</span>';
                    ?>
                </div>
            </div>
            <?php else: ?>
            <div style="background:#F9F9F9;border-radius:8px;padding:10px 14px;color:#ccc;font-size:13px;">Available</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</div>

<!-- Quick Links -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-top:20px;min-width:0;">
    <?php foreach ([
        ['appointments.php','fa-calendar-alt','Appointments','Manage all bookings'],
        ['customers.php','fa-users','Customers','View registered accounts'],
        ['brides.php','fa-heart','Bride Submissions','Approve photo submissions'],
        ['gowns.php','fa-tshirt','Gown Catalog','Add or remove gowns'],
    ] as [$href,$icon,$title,$desc]): ?>
    <a href="<?= $href ?>" style="background:white;border-radius:10px;padding:18px;box-shadow:0 2px 12px rgba(0,0,0,0.05);text-decoration:none;color:var(--dark);border:1.5px solid transparent;transition:border-color 0.2s;" onmouseover="this.style.borderColor='var(--gold)'" onmouseout="this.style.borderColor='transparent'">
        <i class="fas <?= $icon ?>" style="font-size:24px;color:var(--gold);margin-bottom:10px;display:block;"></i>
        <div style="font-weight:600;font-size:13px;margin-bottom:3px;"><?= $title ?></div>
        <div style="font-size:12px;color:#aaa;"><?= $desc ?></div>
    </a>
    <?php endforeach; ?>
</div>

<script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>">
// Mini calendar
var apptDates = <?= json_encode($appt_dates) ?>;
var selDate = <?= json_encode($sel_date) ?>;
var today = new Date();
var viewYear = today.getFullYear();
var viewMonth = today.getMonth();

function buildCal() {
    var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var days = ['Su','Mo','Tu','We','Th','Fr','Sa'];
    var html = '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">' +
        '<button data-move="-1" style="background:none;border:1px solid var(--border);border-radius:50%;width:30px;height:30px;cursor:pointer;font-size:16px;">‹</button>' +
        '<strong style="font-size:14px;">' + months[viewMonth] + ' ' + viewYear + '</strong>' +
        '<button data-move="1" style="background:none;border:1px solid var(--border);border-radius:50%;width:30px;height:30px;cursor:pointer;font-size:16px;">›</button>' +
        '</div><div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;">';
    days.forEach(function(d){ html += '<div style="font-size:10px;color:#aaa;padding:4px 0;font-weight:600;">' + d + '</div>'; });
    var first = new Date(viewYear, viewMonth, 1).getDay();
    var total = new Date(viewYear, viewMonth+1, 0).getDate();
    for (var i=0; i<first; i++) html += '<div></div>';
    for (var d=1; d<=total; d++) {
        var ds = viewYear + '-' + String(viewMonth+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
        var hasAppt = apptDates.indexOf(ds) >= 0;
        var isSel = ds === selDate;
        var isToday = ds === (today.getFullYear()+'-'+String(today.getMonth()+1).padStart(2,'0')+'-'+String(today.getDate()).padStart(2,'0'));
        var bg = isSel ? 'var(--gold)' : (hasAppt ? 'var(--gold-pale)' : 'transparent');
        var col = isSel ? 'white' : (isToday ? 'var(--gold)' : '#333');
        var fw = (isSel || isToday) ? '700' : '400';
        var dot = hasAppt && !isSel ? '<div style="width:4px;height:4px;border-radius:50%;background:var(--gold);margin:1px auto 0;"></div>' : '';
        html += '<div data-date="' + ds + '" style="cursor:pointer;border-radius:6px;padding:5px 2px;background:' + bg + ';color:' + col + ';font-weight:' + fw + ';font-size:12px;">' + d + dot + '</div>';
    }
    html += '</div>';
    document.getElementById('adminCal').innerHTML = html;
}

function moveMonth(dir) {
    viewMonth += dir;
    if (viewMonth > 11) { viewMonth = 0; viewYear++; }
    if (viewMonth < 0)  { viewMonth = 11; viewYear--; }
    buildCal();
}

// Event delegation — handles month nav buttons and day clicks
document.getElementById('adminCal').addEventListener('click', function(e) {
    var moveBtn = e.target.closest('[data-move]');
    if (moveBtn) { moveMonth(parseInt(moveBtn.dataset.move)); return; }
    var dayEl = e.target.closest('[data-date]');
    if (dayEl) { window.location.href = '?date=' + dayEl.dataset.date; }
});

buildCal();
</script>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>