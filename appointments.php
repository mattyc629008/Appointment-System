<?php
require_once __DIR__ . '/includes/admin_header_pre.php';

$success = $error = '';

// ── Delete ─────────────────────────────────────────────────────────────────
if (!isset($_SESSION['del_tok'])) {
    $_SESSION['del_tok'] = bin2hex(random_bytes(16));
}
if (isset($_GET['delete'], $_GET['tok']) && hash_equals($_SESSION['del_tok'], $_GET['tok'])) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM appointments WHERE id = $del_id");
    header('Location: appointments.php?msg=deleted'); exit;
}

// ── POST handlers ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';

    // Cancel appointment
    } elseif (isset($_POST['cancel_appointment'])) {
        $appt_id = (int)($_POST['appt_id'] ?? 0);
        $reason  = trim($_POST['cancel_reason'] ?? '');
        if ($appt_id) {
            $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND status != 'cancelled'");
            $stmt->bind_param("i", $appt_id);
            $stmt->execute();
            $row = $conn->prepare("SELECT customer_id, appointment_date, appointment_time FROM appointments WHERE id = ?");
            $row->bind_param("i", $appt_id);
            $row->execute();
            $appt = $row->get_result()->fetch_assoc();
            if ($appt && $appt['customer_id']) {
                $cid  = (int)$appt['customer_id'];
                $dstr = date('M j, Y', strtotime($appt['appointment_date']));
                $tstr = date('g:i A',  strtotime($appt['appointment_time']));
                $msg  = "Your appointment on $dstr at $tstr has been cancelled.";
                if ($reason !== '') {
                    $msg .= " Reason: $reason";
                }
                $ns = $conn->prepare("INSERT INTO notifications (customer_id, message) VALUES (?, ?)");
                $ns->bind_param("is", $cid, $msg);
                $ns->execute();
            }
            header('Location: appointments.php?msg=cancelled'); exit;
        } else {
            $error = 'Invalid cancel request.';
        }

    // Mark completed / accepted
    } elseif (isset($_POST['update_status'])) {
        $appt_id    = (int)($_POST['appt_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? '';
        if ($appt_id && in_array($new_status, ['accepted','completed'], true)) {
            $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $appt_id);
            $stmt->execute();
            $row = $conn->prepare("SELECT customer_id, appointment_date, appointment_time FROM appointments WHERE id = ?");
            $row->bind_param("i", $appt_id);
            $row->execute();
            $appt = $row->get_result()->fetch_assoc();
            if ($appt && $appt['customer_id']) {
                $cid  = (int)$appt['customer_id'];
                $dstr = date('M j, Y', strtotime($appt['appointment_date']));
                $tstr = date('g:i A',  strtotime($appt['appointment_time']));
                $msg  = "Your appointment on $dstr at $tstr has been marked as " . ucfirst($new_status) . ".";
                $ns   = $conn->prepare("INSERT INTO notifications (customer_id, message) VALUES (?, ?)");
                $ns->bind_param("is", $cid, $msg);
                $ns->execute();
            }
            header('Location: appointments.php?msg=status_updated'); exit;
        } else {
            $error = 'Invalid update request.';
        }

    // Reschedule
    } elseif (isset($_POST['reschedule'])) {
        $appt_id  = (int)($_POST['appt_id'] ?? 0);
        $new_date = trim($_POST['new_date'] ?? '');
        $new_time = trim($_POST['new_time'] ?? '');
        $valid_times = ['08:00:00','09:00:00','10:00:00','11:00:00','12:00:00',
                        '13:00:00','14:00:00','15:00:00','16:00:00','17:00:00'];

        if (!$appt_id || !$new_date || !in_array($new_time, $valid_times, true)) {
            $error = 'Please select a valid date and time.';
        } elseif (strtotime($new_date) < strtotime('today')) {
            $error = 'Reschedule date cannot be in the past.';
        } else {
            $cf = $conn->prepare(
                "SELECT id FROM appointments
                 WHERE appointment_date = ? AND appointment_time = ?
                 AND status != 'cancelled' AND id != ?"
            );
            $cf->bind_param("ssi", $new_date, $new_time, $appt_id);
            $cf->execute();
            if ($cf->get_result()->num_rows > 0) {
                $error = 'That time slot is already taken. Please choose another.';
            } else {
                $fetch = $conn->prepare("SELECT customer_id FROM appointments WHERE id = ?");
                $fetch->bind_param("i", $appt_id);
                $fetch->execute();
                $old = $fetch->get_result()->fetch_assoc();

                $upd = $conn->prepare(
                    "UPDATE appointments
                     SET appointment_date = ?, appointment_time = ?, status = 'accepted'
                     WHERE id = ?"
                );
                $upd->bind_param("ssi", $new_date, $new_time, $appt_id);
                $upd->execute();

                if ($old && !empty($old['customer_id'])) {
                    $cid  = (int)$old['customer_id'];
                    $dstr = date('M j, Y', strtotime($new_date));
                    $tstr = date('g:i A',  strtotime($new_time));
                    $msg  = "Your appointment has been rescheduled to $dstr at $tstr.";
                    $ns   = $conn->prepare("INSERT INTO notifications (customer_id, message) VALUES (?, ?)");
                    $ns->bind_param("is", $cid, $msg);
                    $ns->execute();
                }
                header('Location: appointments.php?msg=rescheduled'); exit;
            }
        }
    }
}

// ── Flash messages ─────────────────────────────────────────────────────────
$flash_map = [
    'deleted'        => 'Appointment deleted.',
    'status_updated' => 'Appointment status updated.',
    'rescheduled'    => 'Appointment rescheduled successfully.',
    'cancelled'      => 'Appointment has been cancelled.',
];
if (isset($_GET['msg'], $flash_map[$_GET['msg']])) {
    $success = $flash_map[$_GET['msg']];
}

// ── Filters ────────────────────────────────────────────────────────────────
$filter_status = $_GET['status'] ?? '';
$filter_date   = $_GET['date']   ?? '';
$filter_search = trim($_GET['q'] ?? '');

$where_parts = [];
if (in_array($filter_status, ['accepted','completed','cancelled'], true)) {
    $fs = $conn->real_escape_string($filter_status);
    $where_parts[] = "a.status = '$fs'";
}
if ($filter_date) {
    $fd = $conn->real_escape_string($filter_date);
    $where_parts[] = "a.appointment_date = '$fd'";
}
if ($filter_search) {
    $fq = $conn->real_escape_string($filter_search);
    $where_parts[] = "(a.first_name LIKE '%$fq%' OR a.last_name LIKE '%$fq%'
                       OR a.email LIKE '%$fq%' OR a.phone LIKE '%$fq%')";
}
$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

// ── Pagination ─────────────────────────────────────────────────────────────
$per_page    = 20;
$page        = max(1, (int)($_GET['p'] ?? 1));
$offset      = ($page - 1) * $per_page;
$total_count = (int)$conn->query("SELECT COUNT(*) AS c FROM appointments a $where_sql")->fetch_assoc()['c'];
$total_pages = max(1, (int)ceil($total_count / $per_page));

$appointments = $conn->query("
    SELECT a.*, c.email AS cust_account_email
    FROM appointments a
    LEFT JOIN customers c ON a.customer_id = c.id
    $where_sql
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT $per_page OFFSET $offset
");

// ── Status counts ──────────────────────────────────────────────────────────
$counts_res = $conn->query("SELECT status, COUNT(*) AS n FROM appointments GROUP BY status");
$cnt = ['accepted' => 0, 'completed' => 0, 'cancelled' => 0];
while ($cr = $counts_res->fetch_assoc()) $cnt[$cr['status']] = (int)$cr['n'];
$cnt['total'] = array_sum($cnt);

// Pass POST values back to JS if there was a reschedule validation error
$reopen_modal   = ($error && isset($_POST['reschedule']));
$reopen_appt_id = $reopen_modal ? (int)($_POST['appt_id'] ?? 0)                         : 0;
$reopen_date    = $reopen_modal ? htmlspecialchars($_POST['new_date'] ?? '', ENT_QUOTES)  : '';
$reopen_time    = $reopen_modal ? htmlspecialchars($_POST['new_time'] ?? '', ENT_QUOTES)  : '';

// Pass POST values back to JS if there was a cancel validation error
$reopen_cancel_modal   = ($error && isset($_POST['cancel_appointment']));
$reopen_cancel_appt_id = $reopen_cancel_modal ? (int)($_POST['appt_id'] ?? 0) : 0;

$adminPageTitle = 'Appointments';
require_once __DIR__ . '/includes/admin_header_output.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-family:'Cormorant Garamond',serif;font-size:34px;font-weight:400;margin-bottom:4px;">Appointments</h1>
        <span style="color:#888;font-size:13px;"><?= $total_count ?> record<?= $total_count !== 1 ? 's' : '' ?> found</span>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:20px;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error && !$reopen_modal && !$reopen_cancel_modal): ?>
<div class="alert alert-error" style="margin-bottom:20px;"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Status pills -->
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
<?php
$pills = [
    [''          , 'All',       $cnt['total'],     '#6B7280', '#F3F4F6'],
    ['accepted'  , 'Accepted',  $cnt['accepted'],  '#166534', '#DCFCE7'],
    ['completed' , 'Completed', $cnt['completed'], '#075985', '#E0F2FE'],
    ['cancelled' , 'Cancelled', $cnt['cancelled'], '#991B1B', '#FEE2E2'],
];
foreach ($pills as [$val, $label, $count, $col, $bg]):
    $active = ($filter_status === $val);
    $qs = http_build_query(array_filter(['status' => $val, 'date' => $filter_date, 'q' => $filter_search]));
?>
<a href="appointments.php<?= $qs ? "?$qs" : '' ?>"
   style="padding:7px 16px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;
          background:<?= $active ? $col : $bg ?>;color:<?= $active ? 'white' : $col ?>;
          border:1.5px solid <?= $col ?>;">
    <?= $label ?> <span style="font-weight:400;">(<?= $count ?>)</span>
</a>
<?php endforeach; ?>
</div>

<!-- Search / date filter -->
<form method="GET" action="appointments.php" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px;align-items:center;">
    <?php if ($filter_status): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>"><?php endif; ?>
    <input type="text" name="q" value="<?= htmlspecialchars($filter_search) ?>"
           placeholder="Search name, email, phone…"
           style="flex:1;min-width:200px;padding:9px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:14px;">
    <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>"
           style="padding:9px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:14px;">
    <button type="submit" class="btn btn-primary btn-sm" style="padding:9px 18px;">
        <i class="fas fa-search"></i> Filter
    </button>
    <?php if ($filter_search || $filter_date || $filter_status): ?>
    <a href="appointments.php" class="btn btn-outline btn-sm" style="padding:9px 18px;">
        <i class="fas fa-times"></i> Clear
    </a>
    <?php endif; ?>
</form>

<!-- Appointments table -->
<div style="background:white;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.06);overflow:hidden;">
<?php if ($appointments && $appointments->num_rows > 0): ?>
<div style="overflow-x:auto;">
<table style="width:100%;border-collapse:collapse;font-size:13px;">
    <thead>
        <tr style="background:#F9FAFB;border-bottom:1.5px solid var(--border);">
            <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151;white-space:nowrap;">Date & Time</th>
            <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151;">Client</th>
            <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151;">Contact</th>
            <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151;white-space:nowrap;">For</th>
            <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151;">Type</th>
            <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151;">Gown</th>
            <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151;">Status</th>
            <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151;">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $ss_map = [
        'accepted'  => ['bg' => '#DCFCE7', 'color' => '#166534'],
        'completed' => ['bg' => '#E0F2FE', 'color' => '#075985'],
        'cancelled' => ['bg' => '#FEE2E2', 'color' => '#991B1B'],
    ];
    while ($a = $appointments->fetch_assoc()):
        $ss   = $ss_map[$a['status']] ?? ['bg' => '#F3F4F6', 'color' => '#374151'];
        $past = strtotime($a['appointment_date']) < strtotime('today');
    ?>
    <tr style="border-bottom:1px solid var(--border);">
        <td style="padding:14px 16px;white-space:nowrap;">
            <div style="font-weight:600;color:var(--dark);"><?= date('M j, Y', strtotime($a['appointment_date'])) ?></div>
            <div style="color:#888;margin-top:2px;"><?= date('g:i A', strtotime($a['appointment_time'])) ?></div>
            <?php if ($past && $a['status'] === 'accepted'): ?>
            <div style="font-size:10px;color:#F59E0B;font-weight:700;margin-top:2px;">PAST DUE</div>
            <?php endif; ?>
        </td>
        <td style="padding:14px 16px;">
            <div style="font-weight:600;color:var(--dark);"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></div>
            <?php if ($a['customer_id']): ?>
            <div style="font-size:11px;color:var(--gold);margin-top:2px;"><i class="fas fa-user-check"></i> Registered</div>
            <?php endif; ?>
            <?php if ($a['notes']): ?>
            <div style="font-size:11px;color:#888;margin-top:3px;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                 title="<?= htmlspecialchars($a['notes']) ?>">
                <i class="fas fa-sticky-note"></i> <?= htmlspecialchars($a['notes']) ?>
            </div>
            <?php endif; ?>
        </td>
        <td style="padding:14px 16px;">
            <div><?= htmlspecialchars($a['email']) ?></div>
            <div style="color:#888;margin-top:2px;"><?= htmlspecialchars($a['phone']) ?></div>
        </td>
        <td style="padding:14px 16px;white-space:nowrap;"><?= htmlspecialchars($a['appointment_for']) ?></td>
        <td style="padding:14px 16px;white-space:nowrap;">
            <span style="padding:3px 10px;border-radius:12px;background:#F3F4F6;font-size:12px;color:#374151;">
                <?= htmlspecialchars($a['appointment_type']) ?>
            </span>
        </td>
        <td style="padding:14px 16px;">
            <?php if ($a['gown_name']): ?>
            <span style="font-size:12px;">👗 <?= htmlspecialchars($a['gown_name']) ?></span>
            <?php else: ?><span style="color:#ccc;">—</span><?php endif; ?>
        </td>
        <td style="padding:14px 16px;white-space:nowrap;">
            <span style="padding:4px 12px;border-radius:12px;font-size:11px;font-weight:700;
                         background:<?= $ss['bg'] ?>;color:<?= $ss['color'] ?>;">
                <?= ucfirst($a['status']) ?>
            </span>
        </td>
        <td style="padding:14px 16px;white-space:nowrap;">
            <div style="display:flex;gap:6px;flex-wrap:wrap;">

                <?php if ($a['status'] !== 'completed' && $a['status'] !== 'cancelled'): ?>
                <!-- Mark Done -->
                <form method="POST" action="appointments.php" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="appt_id"    value="<?= $a['id'] ?>">
                    <input type="hidden" name="new_status" value="completed">
                    <button type="submit"
                            style="background:#E0F2FE;color:#075985;border:none;padding:5px 10px;
                                   border-radius:6px;font-size:11px;cursor:pointer;font-weight:600;">
                        <i class="fas fa-check"></i> Done
                    </button>
                </form>

                <!-- Reschedule -->
                <button type="button"
                        class="js-reschedule"
                        data-id="<?= $a['id'] ?>"
                        data-date="<?= htmlspecialchars($a['appointment_date']) ?>"
                        data-time="<?= htmlspecialchars($a['appointment_time']) ?>"
                        data-name="<?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?>"
                        style="background:#FEF3C7;color:#92400E;border:none;padding:5px 10px;
                               border-radius:6px;font-size:11px;cursor:pointer;font-weight:600;">
                    <i class="fas fa-calendar-alt"></i> Reschedule
                </button>

                <!-- Cancel -->
                <button type="button"
                        class="js-cancel"
                        data-id="<?= $a['id'] ?>"
                        data-name="<?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?>"
                        data-date="<?= date('M j, Y', strtotime($a['appointment_date'])) ?>"
                        data-time="<?= date('g:i A',  strtotime($a['appointment_time'])) ?>"
                        style="background:#FEE2E2;color:#991B1B;border:none;padding:5px 10px;
                               border-radius:6px;font-size:11px;cursor:pointer;font-weight:600;">
                    <i class="fas fa-ban"></i> Cancel
                </button>
                <?php endif; ?>

                <!-- Delete -->
                <a href="appointments.php?delete=<?= $a['id'] ?>&tok=<?= urlencode($_SESSION['del_tok']) ?>"
                   class="js-confirm-delete"
                   style="background:#F3F4F6;color:#6B7280;padding:5px 10px;border-radius:6px;
                          font-size:11px;text-decoration:none;font-weight:600;">
                    <i class="fas fa-trash"></i>
                </a>
            </div>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>

<?php if ($total_pages > 1): ?>
<div style="display:flex;justify-content:center;gap:6px;padding:16px;">
    <?php
    $base_qs = array_filter(['status' => $filter_status, 'date' => $filter_date, 'q' => $filter_search]);
    for ($i = 1; $i <= $total_pages; $i++):
        $pqs = http_build_query(array_merge($base_qs, ['p' => $i]));
    ?>
    <a href="?<?= $pqs ?>"
       style="padding:6px 12px;border-radius:6px;font-size:13px;text-decoration:none;
              background:<?= $i === $page ? 'var(--gold)' : '#F3F4F6' ?>;
              color:<?= $i === $page ? 'white' : '#374151' ?>;
              font-weight:<?= $i === $page ? '700' : '400' ?>;">
        <?= $i ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php else: ?>
<div style="text-align:center;padding:64px 20px;color:#aaa;">
    <i class="fas fa-calendar-alt" style="font-size:48px;color:var(--border);margin-bottom:16px;display:block;"></i>
    <p style="font-size:15px;">No appointments found<?= ($filter_search || $filter_date || $filter_status) ? ' matching your filters' : '' ?>.</p>
    <?php if ($filter_search || $filter_date || $filter_status): ?>
    <a href="appointments.php" style="color:var(--gold);font-size:14px;">Clear filters</a>
    <?php endif; ?>
</div>
<?php endif; ?>
</div>

<!-- ── Reschedule Modal ───────────────────────────────────────────────────── -->
<div id="rescheduleModal"
     style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
            z-index:99999;background:rgba(0,0,0,0.55);">
    <div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;">
    <div style="background:white;border-radius:14px;padding:32px;width:100%;max-width:440px;
                margin:20px;box-shadow:0 20px 60px rgba(0,0,0,0.3);">

        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
            <div>
                <h3 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:400;margin:0;">
                    Reschedule Appointment
                </h3>
                <p id="modal_client_name" style="color:#888;font-size:13px;margin:4px 0 0;"></p>
            </div>
            <button id="btn-close-modal"
                    style="background:none;border:none;font-size:24px;cursor:pointer;
                           color:#aaa;line-height:1;padding:0;margin-left:12px;">&times;</button>
        </div>

        <div style="height:1px;background:var(--border);margin:16px 0 20px;"></div>

        <?php if ($reopen_modal): ?>
        <div style="background:#FEE2E2;color:#991B1B;padding:10px 14px;border-radius:8px;
                    font-size:13px;margin-bottom:16px;">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="appointments.php">
            <?= csrf_field() ?>
            <input type="hidden" name="reschedule" value="1">
            <input type="hidden" name="appt_id"   id="modal_appt_id" value="<?= $reopen_appt_id ?>">

            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">New Date</label>
                <input type="date" name="new_date" id="modal_new_date" required
                       min="<?= date('Y-m-d') ?>"
                       value="<?= $reopen_date ?>"
                       style="width:100%;padding:10px 14px;border:1.5px solid var(--border);
                              border-radius:8px;font-size:14px;box-sizing:border-box;">
            </div>

            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">New Time</label>
                <select name="new_time" id="modal_new_time" required
                        style="width:100%;padding:10px 14px;border:1.5px solid var(--border);
                               border-radius:8px;font-size:14px;background:white;box-sizing:border-box;">
                    <option value="">— Select a time slot —</option>
                    <?php
                    $time_options = [
                        '08:00:00' => '8:00 AM', '09:00:00' => '9:00 AM',
                        '10:00:00' => '10:00 AM','11:00:00' => '11:00 AM',
                        '12:00:00' => '12:00 PM','13:00:00' => '1:00 PM',
                        '14:00:00' => '2:00 PM', '15:00:00' => '3:00 PM',
                        '16:00:00' => '4:00 PM', '17:00:00' => '5:00 PM',
                    ];
                    foreach ($time_options as $val => $label):
                    ?>
                    <option value="<?= $val ?>"<?= $reopen_time === $val ? ' selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit"
                        style="flex:1;padding:12px;background:var(--gold);color:white;border:none;
                               border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">
                    <i class="fas fa-calendar-check"></i> Confirm Reschedule
                </button>
                <button type="button" id="btn-dismiss-modal"
                        style="padding:12px 18px;background:#F3F4F6;color:#374151;border:none;
                               border-radius:8px;font-size:14px;cursor:pointer;font-weight:500;">
                    Dismiss
                </button>
            </div>
        </form>
    </div>
    </div>
</div>

<!-- ── Cancel Modal ──────────────────────────────────────────────────────── -->
<div id="cancelModal"
     style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
            z-index:99999;background:rgba(0,0,0,0.55);">
    <div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;">
    <div style="background:white;border-radius:14px;padding:32px;width:100%;max-width:440px;
                margin:20px;box-shadow:0 20px 60px rgba(0,0,0,0.3);">

        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
            <div>
                <h3 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:400;margin:0;color:#991B1B;">
                    Cancel Appointment
                </h3>
                <p id="cancel_modal_meta" style="color:#888;font-size:13px;margin:4px 0 0;"></p>
            </div>
            <button id="btn-close-cancel-modal"
                    style="background:none;border:none;font-size:24px;cursor:pointer;
                           color:#aaa;line-height:1;padding:0;margin-left:12px;">&times;</button>
        </div>

        <div style="height:1px;background:var(--border);margin:16px 0 20px;"></div>

        <?php if ($reopen_cancel_modal): ?>
        <div style="background:#FEE2E2;color:#991B1B;padding:10px 14px;border-radius:8px;
                    font-size:13px;margin-bottom:16px;">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Warning notice -->
        <div style="background:#FEF3C7;border:1.5px solid #F59E0B;border-radius:8px;
                    padding:12px 14px;font-size:13px;color:#92400E;margin-bottom:20px;">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>This will cancel the appointment</strong> and notify the client if they have an account.
        </div>

        <form method="POST" action="appointments.php">
            <?= csrf_field() ?>
            <input type="hidden" name="cancel_appointment" value="1">
            <input type="hidden" name="appt_id" id="cancel_modal_appt_id"
                   value="<?= $reopen_cancel_appt_id ?>">

            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">
                    Reason for Cancellation <span style="font-weight:400;color:#888;">(optional — included in client notification)</span>
                </label>
                <textarea name="cancel_reason" id="cancel_modal_reason" rows="3"
                          placeholder="e.g. Scheduling conflict, shop closed, client request…"
                          style="width:100%;padding:10px 14px;border:1.5px solid var(--border);
                                 border-radius:8px;font-size:14px;box-sizing:border-box;
                                 resize:vertical;font-family:inherit;"></textarea>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit"
                        style="flex:1;padding:12px;background:#DC2626;color:white;border:none;
                               border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">
                    <i class="fas fa-ban"></i> Confirm Cancellation
                </button>
                <button type="button" id="btn-dismiss-cancel-modal"
                        style="padding:12px 18px;background:#F3F4F6;color:#374151;border:none;
                               border-radius:8px;font-size:14px;cursor:pointer;font-weight:500;">
                    Dismiss
                </button>
            </div>
        </form>
    </div>
    </div>
</div>

<!-- Single nonce'd script — no inline onclick anywhere -->
<script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>">
(function () {
    // ── Reschedule modal ────────────────────────────────────────────────────
    var modal    = document.getElementById('rescheduleModal');
    var modalId  = document.getElementById('modal_appt_id');
    var modalDate= document.getElementById('modal_new_date');
    var modalTime= document.getElementById('modal_new_time');
    var modalName= document.getElementById('modal_client_name');

    function openModal(id, date, time, name) {
        modalId.value   = id;
        modalDate.value = date;
        modalTime.value = time;
        modalName.textContent = name ? 'Client: ' + name : '';
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // ── Cancel modal ────────────────────────────────────────────────────────
    var cancelModal       = document.getElementById('cancelModal');
    var cancelModalId     = document.getElementById('cancel_modal_appt_id');
    var cancelModalMeta   = document.getElementById('cancel_modal_meta');
    var cancelModalReason = document.getElementById('cancel_modal_reason');

    function openCancelModal(id, name, date, time) {
        cancelModalId.value       = id;
        cancelModalReason.value   = '';
        cancelModalMeta.textContent = name + ' · ' + date + ' at ' + time;
        cancelModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeCancelModal() {
        cancelModal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // ── Event delegation ────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        // Reschedule button
        var rescheduleBtn = e.target.closest('.js-reschedule');
        if (rescheduleBtn) {
            openModal(
                rescheduleBtn.dataset.id,
                rescheduleBtn.dataset.date,
                rescheduleBtn.dataset.time,
                rescheduleBtn.dataset.name
            );
            return;
        }

        // Cancel button
        var cancelBtn = e.target.closest('.js-cancel');
        if (cancelBtn) {
            openCancelModal(
                cancelBtn.dataset.id,
                cancelBtn.dataset.name,
                cancelBtn.dataset.date,
                cancelBtn.dataset.time
            );
            return;
        }

        // Reschedule modal close / dismiss
        if (e.target.closest('#btn-close-modal') || e.target.closest('#btn-dismiss-modal')) {
            closeModal();
            return;
        }
        if (e.target === modal) { closeModal(); return; }

        // Cancel modal close / dismiss
        if (e.target.closest('#btn-close-cancel-modal') || e.target.closest('#btn-dismiss-cancel-modal')) {
            closeCancelModal();
            return;
        }
        if (e.target === cancelModal) { closeCancelModal(); return; }

        // Delete confirmation
        var del = e.target.closest('.js-confirm-delete');
        if (del) {
            if (!confirm('Permanently delete this appointment? This cannot be undone.')) {
                e.preventDefault();
            }
        }
    });

    // Re-open reschedule modal on server validation error
    <?php if ($reopen_modal): ?>
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    <?php endif; ?>

    // Re-open cancel modal on server validation error
    <?php if ($reopen_cancel_modal): ?>
    cancelModal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    <?php endif; ?>
})();
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>