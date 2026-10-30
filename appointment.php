<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireCustomerLogin();

$success = $error = '';

// Gown pre-selection from URL (coming from gowns page)
$preselect_gown_id   = (int)($_GET['gown_id'] ?? $_POST['gown_id'] ?? 0);
$preselect_gown_name = htmlspecialchars($_GET['gown_name'] ?? $_POST['gown_name'] ?? '');

// ─── Philippine Regular Holidays (2025–2026) ──────────────────────────────────
// Format: 'YYYY-MM-DD'
$ph_holidays = [
    // 2025
    '2025-01-01', // New Year's Day
    '2025-03-31', // Eid'l Fitr (approx)
    '2025-04-09', // Araw ng Kagitingan
    '2025-04-17', // Maundy Thursday
    '2025-04-18', // Good Friday
    '2025-05-01', // Labor Day
    '2025-06-07', // Eid'l Adha (approx)
    '2025-06-12', // Independence Day
    '2025-08-25', // National Heroes Day (last Monday of August)
    '2025-11-30', // Bonifacio Day
    '2025-12-25', // Christmas Day
    '2025-12-30', // Rizal Day
    // 2026
    '2026-01-01', // New Year's Day
    '2026-03-20', // Eid'l Fitr (approx)
    '2026-04-02', // Maundy Thursday
    '2026-04-03', // Good Friday
    '2026-04-09', // Araw ng Kagitingan
    '2026-05-01', // Labor Day
    '2026-05-27', // Eid'l Adha (approx)
    '2026-06-12', // Independence Day
    '2026-08-31', // National Heroes Day (last Monday of August)
    '2026-11-30', // Bonifacio Day
    '2026-12-25', // Christmas Day
    '2026-12-30', // Rizal Day
];

// Shop open hours
define('SHOP_OPEN',  '08:00:00');
define('SHOP_CLOSE', '17:00:00'); // Last bookable slot (appointment ends at 6 PM)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';
    } else {
        // ── Block bookings outside business hours (Philippine Time) ──────────
        $ph_now  = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $ph_hour = (int)$ph_now->format('G'); // 0–23, no leading zero
        if ($ph_hour < 8 || $ph_hour >= 18) {
            $error = 'Online booking is only available during business hours (8:00 AM – 6:00 PM, Philippine Time). Please visit us or try again during open hours.';
        } else {
            $first_name  = trim($conn->real_escape_string($_POST['first_name'] ?? ''));
            $last_name   = trim($conn->real_escape_string($_POST['last_name'] ?? ''));
            $email       = trim($conn->real_escape_string($_POST['email'] ?? ''));
            $phone       = trim($conn->real_escape_string($_POST['phone'] ?? ''));
            $appt_date   = $conn->real_escape_string($_POST['appointment_date'] ?? '');
            $appt_time   = $conn->real_escape_string($_POST['appointment_time'] ?? '');
            $appt_type   = '1-hour';
            $appt_for    = $conn->real_escape_string($_POST['appointment_for'] ?? '');
            $notes       = trim($conn->real_escape_string($_POST['notes'] ?? ''));
            $gown_id     = (int)($_POST['gown_id'] ?? 0) ?: null;
            $gown_name   = $conn->real_escape_string(trim($_POST['gown_name'] ?? ''));
            $customer_id = isCustomerLoggedIn() ? $_SESSION['customer_id'] : null;

            if (!$first_name || !$last_name || !$email || !$phone || !$appt_date || !$appt_time || !$appt_for) {
                $error = 'Please fill in all required fields.';

            // ── Block Philippine regular holidays ────────────────────────────
            } elseif (in_array($appt_date, $ph_holidays)) {
                $error = 'Sorry, we are closed on that date as it is a Philippine regular holiday. Please choose another date.';

            // ── Enforce business hours (8:00 AM – 5:00 PM last slot) ─────────
            } elseif ($appt_time < SHOP_OPEN || $appt_time > SHOP_CLOSE) {
                $error = 'Appointments can only be booked between 8:00 AM and 5:00 PM (shop closes at 6:00 PM). Please choose a valid time.';

            } else {
                $check = $conn->prepare("SELECT id FROM appointments WHERE appointment_date=? AND appointment_time=? AND status != 'cancelled'");
                $check->bind_param("ss", $appt_date, $appt_time);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $error = 'That time slot is already taken. Please choose another time.';
                } else {
                    // Rolling 24h booking limit (tracked by email)
                    $limit_check = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE email = ? AND created_at >= NOW() - INTERVAL 24 HOUR AND status != 'cancelled'");
                    $limit_check->bind_param("s", $email);
                    $limit_check->execute();
                    $daily_count = (int)$limit_check->get_result()->fetch_assoc()['cnt'];
                    if ($daily_count >= 10) {
                        $oldest = $conn->prepare("SELECT created_at FROM appointments WHERE email = ? AND created_at >= NOW() - INTERVAL 24 HOUR AND status != 'cancelled' ORDER BY created_at ASC LIMIT 1");
                        $oldest->bind_param("s", $email);
                        $oldest->execute();
                        $oldest_row = $oldest->get_result()->fetch_assoc();
                        $reset_time = $oldest_row ? date('g:i A', strtotime($oldest_row['created_at']) + 86400) : 'in 24 hours';
                        $error = "Daily booking limit reached (10/day). You can book again at $reset_time.";
                    } else {
                        $auto_status = 'accepted';
                        $stmt = $conn->prepare("INSERT INTO appointments (customer_id,first_name,last_name,email,phone,appointment_date,appointment_time,appointment_type,appointment_for,gown_id,gown_name,notes,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                        $stmt->bind_param("issssssssisss", $customer_id, $first_name, $last_name, $email, $phone, $appt_date, $appt_time, $appt_type, $appt_for, $gown_id, $gown_name, $notes, $auto_status);
                        if ($stmt->execute()) {
                            if ($customer_id) {
                                $gown_note = $gown_name ? " for $gown_name" : '';
                                $msg = "Your appointment on " . date('M j, Y', strtotime($appt_date)) . " at " . date('g:i A', strtotime($appt_time)) . "$gown_note has been booked and accepted.";
                                $ns = $conn->prepare("INSERT INTO notifications (customer_id, message) VALUES (?,?)");
                                $ns->bind_param("is", $customer_id, $msg);
                                $ns->execute();
                            }
                            $success = 'Your appointment has been booked successfully!';
                        } else {
                            $error = 'Something went wrong. Please try again.';
                        }
                    }
                }
            }
        } // end business-hours gate
    }
}

$pageTitle = 'Book An Appointment';
require_once __DIR__ . '/includes/header.php';

// Fetch all gowns grouped by category for the inline browser (JSON for JS)
$gown_categories = ['Wedding / Bridesmaid','Kids / Flower Girl','JS Prom','Debut / Birthday','Barong / Coat'];
$all_gowns_by_cat = [];
$gown_res = $conn->query("SELECT id, name, designer, category, silhouette, sleeve_type, color, available_sizes, price, in_store, image_path, description FROM gowns ORDER BY category, designer, name");
while ($g = $gown_res->fetch_assoc()) {
    $cat_key = $g['category'];
    if (!isset($all_gowns_by_cat[$cat_key])) $all_gowns_by_cat[$cat_key] = [];
    $all_gowns_by_cat[$cat_key][] = $g;
}

// Get occupied slots for the next 3 months (for calendar JS)
$occupied = [];
$res = $conn->query("SELECT appointment_date, appointment_time FROM appointments WHERE appointment_date >= CURDATE() AND status != 'cancelled'");
while ($row = $res->fetch_assoc()) {
    $d = $row['appointment_date'];
    if (!isset($occupied[$d])) $occupied[$d] = [];
    $occupied[$d][] = $row['appointment_time'];
}
?>

<div class="page-hero">
    <h1>Book An Appointment</h1>
    <p>Private, personalized • One-on-one with our expert stylists</p>
</div>

<section style="background:var(--cream);padding:60px 0;">
    <div class="container">

        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

        <div style="max-width:820px;margin:0 auto;">
            <!-- Info box -->
            <div class="alert alert-info" style="margin-bottom:32px;">
                <strong>Welcome to Allen and Rej Bridal!</strong> We're delighted to help you find the gown that makes your moment unforgettable. Each experience is private and personalized, giving you one-on-one time with one of our expert stylists. Dresses are available to <strong>purchase off the rack or order</strong>, depending on your event date and designer availability.
            </div>

            <!-- Calendar -->
            <div class="form-card" style="margin-bottom:28px;">
                <h3 style="font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:400;margin-bottom:8px;">Choose a Date & Time</h3>
                <span class="gold-line"></span>

                <div class="cal-wrap" style="box-shadow:none;padding:0;">
                    <div class="cal-header">
                        <button class="cal-nav" id="prevMonth">‹</button>
                        <h3 id="calMonthLabel" style="font-family:'Cormorant Garamond',serif;font-weight:400;font-size:22px;"></h3>
                        <button class="cal-nav" id="nextMonth">›</button>
                    </div>
                    <div class="cal-grid" id="calGrid"></div>
                    <div class="cal-legend" style="margin-top:14px;">
                        <span class="legend-available"><span class="legend-dot"></span>Available</span>
                        <span class="legend-occupied"><span class="legend-dot"></span>Fully Booked</span>
                        <span class="legend-holiday"><span class="legend-dot" style="background:#e57373;"></span>Holiday</span>
                    </div>
                </div>

                <div id="timeSection" style="display:none;margin-top:24px;">
                    <h4 style="font-size:13px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:var(--gray);margin-bottom:12px;">Available Times</h4>
                    <div class="time-slots" id="timeSlots"></div>
                </div>

                <input type="hidden" id="selected_date" name="appointment_date" value="">
                <input type="hidden" id="selected_time" name="appointment_time" value="">
            </div>

            <!-- Personal Info Form -->
            <div class="form-card">
                <h3 style="font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:400;margin-bottom:8px;">Your Information</h3>
                <span class="gold-line"></span>

                <?php if ($preselect_gown_name): ?>
                <div style="background:var(--gold-pale);border-left:4px solid var(--gold);border-radius:6px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:14px;">
                    <span style="font-size:28px;">👗</span>
                    <div>
                        <div style="font-size:12px;text-transform:uppercase;letter-spacing:0.1em;color:var(--gold);font-weight:600;">Selected Gown</div>
                        <div style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:400;color:var(--dark);"><?= $preselect_gown_name ?></div>
                        <div style="font-size:12px;color:var(--gray);margin-top:2px;">This gown will be noted in your appointment request.</div>
                    </div>
                    <a href="/appointment.php" style="margin-left:auto;font-size:12px;color:var(--gray);text-decoration:none;" title="Remove gown selection">✕ Remove</a>
                </div>
                <?php endif; ?>
                <form method="POST" id="apptForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="appointment_date" id="form_appt_date">
                    <input type="hidden" name="appointment_time" id="form_appt_time">
                    <input type="hidden" name="gown_id" value="<?= $preselect_gown_id ?>">
                    <input type="hidden" name="gown_name" value="<?= $preselect_gown_name ?>">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group full">
                            <label>Appointment For *</label>
                            <?php
                            $default_for = $_POST['appointment_for'] ?? '';
                            if (!$default_for && $preselect_gown_id) {
                                $gr = $conn->query("SELECT category FROM gowns WHERE id=$preselect_gown_id");
                                if ($gr && $gr->num_rows) $default_for = $gr->fetch_assoc()['category'];
                            }
                            ?>
                            <select name="appointment_for" required>
                                <option value="">Select a category…</option>
                                <option value="Wedding / Bridesmaid" <?= $default_for === 'Wedding / Bridesmaid' ? 'selected' : '' ?>>Wedding / Bridesmaid</option>
                                <option value="Kids / Flower Girl" <?= $default_for === 'Kids / Flower Girl' ? 'selected' : '' ?>>Kids / Flower Girl</option>
                                <option value="JS Prom" <?= $default_for === 'JS Prom' ? 'selected' : '' ?>>JS Prom</option>
                                <option value="Debut / Birthday" <?= $default_for === 'Debut / Birthday' ? 'selected' : '' ?>>Debut / Birthday</option>
                                <option value="Barong / Coat" <?= $default_for === 'Barong / Coat' ? 'selected' : '' ?>>Barong / Coat</option>
                                <option value="Alterations" <?= $default_for === 'Alterations' ? 'selected' : '' ?>>Alterations</option>
                                <option value="Other" <?= $default_for === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <!-- ── Gown Browser ── -->
                        <div class="form-group full" id="gownBrowserWrap" style="display:none;">
                            <label style="margin-bottom:10px;">Browse Gowns <span style="font-weight:400;color:var(--gray);font-size:12px;text-transform:none;letter-spacing:0;">(Optional — click a gown to attach it to your appointment)</span></label>
                            <div id="gownGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;max-height:520px;overflow-y:auto;padding:4px 2px;"></div>
                            <div id="gownNoneMsg" style="display:none;text-align:center;padding:40px;color:var(--gray);">
                                <div style="font-size:42px;margin-bottom:10px;">👗</div>
                                <p>No gowns found for this category yet.</p>
                            </div>
                        </div>

                        <!-- Selected gown banner (shown when user picks from browser) -->
                        <div class="form-group full" id="selectedGownBanner" style="display:none;">
                            <div style="background:var(--gold-pale);border-left:4px solid var(--gold);border-radius:6px;padding:14px 18px;display:flex;align-items:center;gap:14px;">
                                <span style="font-size:26px;">👗</span>
                                <div style="flex:1;">
                                    <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:var(--gold);font-weight:600;">Selected Gown</div>
                                    <div id="selectedGownName" style="font-family:'Cormorant Garamond',serif;font-size:18px;color:var(--dark);"></div>
                                </div>
                                <button type="button" onclick="clearGownSelection()" style="background:none;border:none;cursor:pointer;color:var(--gray);font-size:14px;padding:4px 8px;">✕ Remove</button>
                            </div>
                        </div>

                        <div class="form-group full">
                            <label>Special Notes / Requests</label>
                            <textarea name="notes" placeholder="Tell us about your event date, style preferences, or any special requests…"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="form-submit">
                        <button type="submit" class="btn btn-gold" style="padding:16px 48px;font-size:14px;">
                            Request Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>">
const occupied = <?= json_encode($occupied) ?>;
const gownsByCategory = <?= json_encode($all_gowns_by_cat) ?>;

// ── Philippine Regular Holidays (JS side for calendar blocking) ───────────────
const phHolidays = new Set([
    // 2025
    '2025-01-01','2025-03-31','2025-04-09','2025-04-17','2025-04-18',
    '2025-05-01','2025-06-07','2025-06-12','2025-08-25',
    '2025-11-30','2025-12-25','2025-12-30',
    // 2026
    '2026-01-01','2026-03-20','2026-04-02','2026-04-03','2026-04-09',
    '2026-05-01','2026-05-27','2026-06-12','2026-08-31',
    '2026-11-30','2026-12-25','2026-12-30'
]);

// ── Business hours constants ───────────────────────────────────────────────────
const SHOP_OPEN_HOUR  = 8;   // 8:00 AM
const SHOP_CLOSE_HOUR = 17;  // 5:00 PM (last bookable slot; appointment ends at 6 PM)

// ─── Calendar ───
let currentDate = new Date();
let selectedDate = null;
let selectedTime = null;

const dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

const slots = ['08:00:00','09:00:00','10:00:00','11:00:00','12:00:00','13:00:00','14:00:00','15:00:00','16:00:00','17:00:00'];
const businessHours = {
    0: slots, 1: slots, 2: slots, 3: slots, 4: slots, 5: slots, 6: slots
};

function formatTime(t) {
    const [h,m] = t.split(':');
    const hr = parseInt(h);
    return (hr > 12 ? hr-12 : hr) + ':' + m + ' ' + (hr >= 12 ? 'PM' : 'AM');
}

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    document.getElementById('calMonthLabel').textContent = monthNames[month] + ' ' + year;
    const grid = document.getElementById('calGrid');
    grid.innerHTML = dayNames.map(d => `<div class="cal-day-name">${d}</div>`).join('');

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month+1, 0).getDate();
    const today = new Date(); today.setHours(0,0,0,0);

    for (let i = 0; i < firstDay; i++) {
        grid.innerHTML += '<div class="cal-day empty"></div>';
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const date = new Date(year, month, d);
        const dateStr = year + '-' + String(month+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
        const dow = date.getDay();
        const hours = businessHours[dow];
        const bookedSlots = occupied[dateStr] || [];
        const isPast = date < today;
        const isClosed = hours.length === 0;
        const isFullyBooked = !isClosed && bookedSlots.length >= hours.length;
        const isSelected = dateStr === selectedDate;
        const isToday = date.getTime() === today.getTime();
        const isHoliday = phHolidays.has(dateStr);

        let cls = 'cal-day';
        let label = d;

        if (isPast || isClosed) {
            cls += ' disabled';
        } else if (isHoliday) {
            cls += ' disabled holiday';
            label = `${d}<br><span style="font-size:9px;color:#e57373;">Holiday</span>`;
        } else if (isFullyBooked) {
            cls += ' occupied';
            label = `${d}<br><span style="font-size:9px">Full</span>`;
        } else if (isSelected) {
            cls += ' selected';
        } else if (isToday) {
            cls += ' today';
        }

        const clickable = !isPast && !isClosed && !isFullyBooked && !isHoliday;
        grid.innerHTML += `<div class="${cls}" ${clickable ? `data-date="${dateStr}" data-dow="${dow}"` : ''}>${label}</div>`;
    }
}

function selectDate(dateStr, dow) {
    selectedDate = dateStr;
    selectedTime = null;
    document.getElementById('selected_date').value = dateStr;
    document.getElementById('selected_time').value = '';
    renderCalendar();
    renderTimeSlots(dateStr, dow);
}

function renderTimeSlots(dateStr, dow) {
    const section = document.getElementById('timeSection');
    const container = document.getElementById('timeSlots');
    const hours = businessHours[dow];
    const booked = occupied[dateStr] || [];

    const now = new Date();
    const todayStr = now.getFullYear() + '-'
        + String(now.getMonth()+1).padStart(2,'0') + '-'
        + String(now.getDate()).padStart(2,'0');
    const isToday = dateStr === todayStr;

    section.style.display = 'block';
    container.innerHTML = hours.map(t => {
        const isTaken = booked.includes(t);

        let isPastSlot = false;
        if (isToday) {
            const slotHour = parseInt(t.split(':')[0]);
            isPastSlot = slotHour <= now.getHours();
        }

        const isDisabled = isTaken || isPastSlot;
        const isSelected = t === selectedTime;
        const tooltip = isPastSlot ? 'title="This time has already passed"' : '';

        return `<div class="time-slot ${isDisabled ? 'taken' : ''} ${isSelected ? 'selected' : ''}" ${!isDisabled ? `data-time="${t}"` : ''} ${tooltip}>${formatTime(t)}${isPastSlot ? '<br><span style="font-size:9px">Passed</span>' : ''}</div>`;
    }).join("");
}

function selectTime(t, el) {
    selectedTime = t;
    document.getElementById('selected_time').value = t;
    document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
    if (el) el.classList.add('selected');
}

function syncFormValues() {
    if (!selectedDate) { alert('Please select a date.'); return false; }
    if (!selectedTime) { alert('Please select a time slot.'); return false; }
    document.getElementById('form_appt_date').value = selectedDate;
    document.getElementById('form_appt_time').value = selectedTime;
    return true;
}

document.getElementById('prevMonth').addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
});
document.getElementById('nextMonth').addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
});

document.getElementById('calGrid').addEventListener('click', function(e) {
    const day = e.target.closest('.cal-day[data-date]');
    if (day) selectDate(day.dataset.date, parseInt(day.dataset.dow));
});

document.getElementById('timeSlots').addEventListener('click', function(e) {
    const slot = e.target.closest('.time-slot[data-time]');
    if (slot) selectTime(slot.dataset.time, slot);
});

document.getElementById('apptForm').addEventListener('submit', function(e) {
    if (!syncFormValues()) e.preventDefault();
});

renderCalendar();

// ─── Gown Browser ────────────────────────────────────────────────────────────
let pickedGownId   = <?= $preselect_gown_id ?: 'null' ?>;
let pickedGownName = <?= $preselect_gown_name ? json_encode($preselect_gown_name) : 'null' ?>;

if (pickedGownName) {
    document.getElementById('selectedGownBanner').style.display = 'block';
    document.getElementById('selectedGownName').textContent = pickedGownName;
}

function showGownBrowser(category) {
    const wrap = document.getElementById('gownBrowserWrap');
    const grid = document.getElementById('gownGrid');
    const none = document.getElementById('gownNoneMsg');

    const browsable = ['Wedding / Bridesmaid','Kids / Flower Girl','JS Prom','Debut / Birthday','Barong / Coat'];
    if (!category || !browsable.includes(category)) {
        wrap.style.display = 'none';
        return;
    }

    const gowns = gownsByCategory[category] || [];
    grid.innerHTML = '';

    if (gowns.length === 0) {
        grid.style.display = 'none';
        none.style.display = 'block';
        wrap.style.display = 'block';
        return;
    }

    none.style.display = 'none';
    grid.style.display = 'grid';
    wrap.style.display = 'block';

    gowns.forEach(g => {
        const isSelected = pickedGownId && pickedGownId == g.id;
        const imgHtml = g.image_path
            ? `<img src="/${g.image_path}" alt="${escHtml(g.name)}" style="width:100%;height:180px;object-fit:cover;border-radius:6px 6px 0 0;display:block;">`
            : `<div style="width:100%;height:180px;background:var(--cream);border-radius:6px 6px 0 0;display:flex;align-items:center;justify-content:center;font-size:48px;">👗</div>`;

        const tags = [g.silhouette, g.sleeve_type, g.color].filter(Boolean)
            .map(t => `<span style="display:inline-block;background:var(--cream);border:1px solid var(--border);border-radius:20px;font-size:10px;padding:2px 8px;margin:2px 2px 0 0;color:var(--gray);">${escHtml(t)}</span>`)
            .join('');

        const price = g.price ? `<div style="color:var(--gold);font-weight:600;font-size:14px;margin-top:6px;">₱${Number(g.price).toLocaleString()}</div>` : '';
        const inStore = g.in_store ? `<span style="background:#d4edda;color:#155724;font-size:10px;padding:2px 7px;border-radius:20px;margin-left:4px;">✓ In Store</span>` : '';

        const card = document.createElement('div');
        card.style.cssText = `border:2px solid ${isSelected ? 'var(--gold)' : 'var(--border)'};border-radius:8px;cursor:pointer;transition:border-color .2s,box-shadow .2s;background:#fff;overflow:hidden;`;
        card.dataset.gownId   = g.id;
        card.dataset.gownName = g.name + (g.designer ? ' by ' + g.designer : '');
        card.innerHTML = `
            ${imgHtml}
            <div style="padding:12px;">
                <div style="font-size:11px;color:var(--gray);text-transform:uppercase;letter-spacing:.08em;">${escHtml(g.designer||'')}</div>
                <div style="font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:500;margin-top:2px;">${escHtml(g.name)}${inStore}</div>
                <div style="margin-top:4px;">${tags}</div>
                ${price}
                ${g.description ? `<p style="font-size:11px;color:var(--gray);margin-top:6px;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">${escHtml(g.description)}</p>` : ''}
                <div style="margin-top:10px;text-align:center;">
                    <span class="pick-btn" style="display:inline-block;padding:6px 16px;border-radius:4px;font-size:12px;font-weight:600;letter-spacing:.06em;background:${isSelected ? 'var(--gold)' : 'transparent'};color:${isSelected ? '#fff' : 'var(--gold)'};border:1px solid var(--gold);transition:background .2s,color .2s;">
                        ${isSelected ? '✓ Selected' : 'Select Gown'}
                    </span>
                </div>
            </div>`;

        card.addEventListener('click', () => selectGown(g.id, card.dataset.gownName, card));
        grid.appendChild(card);
    });
}

function escHtml(str) {
    return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function selectGown(id, name, cardEl) {
    document.querySelectorAll('#gownGrid > div').forEach(c => {
        c.style.borderColor = 'var(--border)';
        c.style.boxShadow = '';
        const btn = c.querySelector('.pick-btn');
        if (btn) { btn.style.background = 'transparent'; btn.style.color = 'var(--gold)'; btn.textContent = 'Select Gown'; }
    });

    if (pickedGownId == id) {
        clearGownSelection();
        return;
    }

    pickedGownId   = id;
    pickedGownName = name;

    cardEl.style.borderColor = 'var(--gold)';
    cardEl.style.boxShadow   = '0 0 0 3px rgba(180,148,68,.15)';
    const btn = cardEl.querySelector('.pick-btn');
    if (btn) { btn.style.background = 'var(--gold)'; btn.style.color = '#fff'; btn.textContent = '✓ Selected'; }

    document.querySelector('input[name="gown_id"]').value   = id;
    document.querySelector('input[name="gown_name"]').value = name;

    document.getElementById('selectedGownName').textContent = name;
    document.getElementById('selectedGownBanner').style.display = 'block';
}

function clearGownSelection() {
    pickedGownId   = null;
    pickedGownName = null;
    document.querySelector('input[name="gown_id"]').value   = '';
    document.querySelector('input[name="gown_name"]').value = '';
    document.getElementById('selectedGownBanner').style.display = 'none';

    document.querySelectorAll('#gownGrid > div').forEach(c => {
        c.style.borderColor = 'var(--border)';
        c.style.boxShadow   = '';
        const btn = c.querySelector('.pick-btn');
        if (btn) { btn.style.background = 'transparent'; btn.style.color = 'var(--gold)'; btn.textContent = 'Select Gown'; }
    });
}

document.querySelector('select[name="appointment_for"]').addEventListener('change', function() {
    clearGownSelection();
    showGownBrowser(this.value);
});

(function() {
    const sel = document.querySelector('select[name="appointment_for"]');
    if (sel && sel.value) showGownBrowser(sel.value);
})();

// ─── Business-Hours Gate (Philippine Time) ────────────────────────────────────
// Displays a full-screen overlay when the shop is closed (outside 8 AM – 6 PM PH time).
// Checks every second and auto-removes the overlay once business hours begin.
(function () {
    const OPEN_HOUR  = 8;   // 8:00 AM Philippine Time
    const CLOSE_HOUR = 18;  // 6:00 PM Philippine Time

    // Returns the current Date object adjusted to Asia/Manila timezone
    function getPhTime() {
        return new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
    }

    function checkHours() {
        const now    = getPhTime();
        const hour   = now.getHours();
        const isOpen = hour >= OPEN_HOUR && hour < CLOSE_HOUR;

        let overlay = document.getElementById('shopClosedOverlay');

        if (!isOpen) {
            // Build overlay only once
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'shopClosedOverlay';

                const isBeforeOpen = hour < OPEN_HOUR;
                const nextOpen     = isBeforeOpen ? 'today at 8:00 AM' : 'tomorrow at 8:00 AM';

                overlay.innerHTML = `
                    <div style="
                        position:fixed;inset:0;z-index:9999;
                        background:rgba(245,241,234,0.97);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'Cormorant Garamond',serif;
                        padding:24px;
                    ">
                        <div style="
                            max-width:480px;width:100%;
                            background:#fff;border-radius:12px;
                            border:1px solid #e8dcc8;
                            padding:48px 40px;text-align:center;
                            box-shadow:0 8px 40px rgba(0,0,0,0.08);
                        ">
                            <div style="font-size:52px;margin-bottom:16px;">🌙</div>
                            <h2 style="font-size:32px;font-weight:400;color:#2c2c2c;margin:0 0 8px;">
                                We're Currently Closed
                            </h2>
                            <p style="font-size:14px;color:#888;font-family:sans-serif;margin:0 0 24px;line-height:1.6;">
                                Online booking is available
                                <strong>Monday – Sunday, 8:00 AM – 6:00 PM</strong>
                                (Philippine Time).<br>
                                We'll be open again <strong>${nextOpen}</strong>.
                            </p>
                            <div style="
                                background:#faf7f2;border:1px solid #e8dcc8;
                                border-radius:8px;padding:16px 20px;margin-bottom:24px;
                            ">
                                <div style="
                                    font-size:11px;letter-spacing:.12em;text-transform:uppercase;
                                    color:#b49444;font-family:sans-serif;font-weight:600;margin-bottom:4px;
                                ">Current Time (PH)</div>
                                <div id="phClockDisplay" style="
                                    font-size:28px;font-weight:400;color:#2c2c2c;letter-spacing:.04em;
                                "></div>
                            </div>
                            <p style="font-size:12px;color:#aaa;font-family:sans-serif;margin:0;">
                                This page will automatically unlock once business hours begin.
                            </p>
                        </div>
                    </div>`;

                document.body.appendChild(overlay);
            }
        } else {
            // Remove overlay during business hours
            if (overlay) overlay.remove();
        }

        // Keep the live clock ticking inside the overlay
        const clockEl = document.getElementById('phClockDisplay');
        if (clockEl) {
            clockEl.textContent = now.toLocaleTimeString('en-PH', {
                hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true
            });
        }
    }

    checkHours();                    // Run immediately on page load
    setInterval(checkHours, 1000);   // Re-check every second; auto-unlocks at 8:00:00 AM
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>