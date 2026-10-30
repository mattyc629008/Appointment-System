<?php
// ALL PHP logic BEFORE any HTML output
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireCustomerLogin();
$pageTitle = 'My Dashboard';

$customer = getCustomer($conn);
$cid = (int)$_SESSION['customer_id'];
$tab = $_GET['tab'] ?? 'appointments';

// ── Notification AJAX handlers (JSON responses, no HTML output) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notif_action'])) {
    header('Content-Type: application/json');
    if ($_POST['notif_action'] === 'delete') {
        $nid = (int)($_POST['notif_id'] ?? 0);
        $conn->query("DELETE FROM notifications WHERE id=$nid AND customer_id=$cid");
        echo json_encode(['ok' => true]);
    } elseif ($_POST['notif_action'] === 'clear_all') {
        $conn->query("DELETE FROM notifications WHERE customer_id=$cid");
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

// Handle profile update
$success_p = $error_p = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!csrf_verify()) {
        $error_p = 'Invalid request. Please try again.';
    } else {
        $fn = $conn->real_escape_string(trim($_POST['first_name'] ?? ''));
        $ln = $conn->real_escape_string(trim($_POST['last_name'] ?? ''));
        $ph = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
        $pw = $_POST['new_password'] ?? '';
        if ($pw) {
            if (strlen($pw) < 6) {
                $error_p = 'Password must be at least 6 characters.';
            } else {
                $hashed = password_hash($pw, PASSWORD_DEFAULT);
                $conn->query("UPDATE customers SET first_name='$fn',last_name='$ln',phone='$ph',password='$hashed' WHERE id=$cid");
                $success_p = 'Profile updated successfully.';
            }
        } else {
            $conn->query("UPDATE customers SET first_name='$fn',last_name='$ln',phone='$ph' WHERE id=$cid");
            $success_p = 'Profile updated successfully.';
        }
        $customer = getCustomer($conn);
    }
}

// Mark notifications read
if ($tab === 'notifications') {
    $conn->query("UPDATE notifications SET is_read=1 WHERE customer_id=$cid");
}

// Fetch data
$appointments = $conn->query("SELECT * FROM appointments WHERE customer_id=$cid ORDER BY appointment_date DESC");
$notifs_res   = $conn->query("SELECT COUNT(*) as cnt FROM notifications WHERE customer_id=$cid AND is_read=0");
$notifs_unread = $notifs_res ? $notifs_res->fetch_assoc()['cnt'] : 0;
$notifications = $conn->query("SELECT * FROM notifications WHERE customer_id=$cid ORDER BY created_at DESC LIMIT 20");
$chat_unread_res = $conn->query("SELECT COUNT(*) as cnt FROM chat_messages WHERE customer_id=$cid AND sender='admin' AND is_read=0");
$chat_unread = $chat_unread_res ? $chat_unread_res->fetch_assoc()['cnt'] : 0;
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>
<div class="page-hero" style="padding:40px 0 32px;">
    <h1 style="font-size:clamp(28px,3vw,44px);">My Dashboard</h1>
    <p>Welcome back, <?= htmlspecialchars($customer['first_name']) ?>!</p>
</div>

<section style="background:var(--cream);padding:40px 0;min-height:70vh;">
<div class="container">
<div class="dash-grid" style="align-items:start;">

    <!-- ── Sidebar ── -->
    <aside style="background:var(--white);border-radius:10px;box-shadow:var(--shadow);padding:24px 20px;position:sticky;top:100px;min-width:0;overflow:hidden;">
        <div style="text-align:center;padding-bottom:18px;margin-bottom:18px;border-bottom:1px solid var(--border);">
            <div style="width:60px;height:60px;border-radius:50%;background:var(--gold-pale);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                <i class="fas fa-user" style="font-size:22px;color:var(--gold);"></i>
            </div>
            <div style="font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:400;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></div>
            <div style="font-size:11px;color:var(--gray);margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($customer['email']) ?></div>
        </div>
        <nav style="display:flex;flex-direction:column;gap:2px;">
            <?php
            $tabs = [
                ['appointments','fa-calendar-alt','My Appointments'],
                ['notifications','fa-bell','Notifications'],
                ['chat','fa-comments','Chat with Us'],
                ['profile','fa-user-edit','My Profile'],
            ];
            foreach ($tabs as [$t,$icon,$label]):
                $active = $tab === $t;
            ?>
            <a href="?tab=<?= $t ?>" style="display:flex;align-items:center;gap:10px;padding:11px 13px;border-radius:6px;text-decoration:none;color:<?= $active ? 'var(--gold)' : 'var(--dark)' ?>;background:<?= $active ? 'var(--gold-pale)' : 'transparent' ?>;font-size:13px;font-weight:500;border-left:3px solid <?= $active ? 'var(--gold)' : 'transparent' ?>;white-space:nowrap;overflow:hidden;">
                <i class="fas <?= $icon ?>" style="font-size:14px;width:16px;text-align:center;flex-shrink:0;"></i>
                <span style="overflow:hidden;text-overflow:ellipsis;"><?= $label ?></span>
                <?php if ($t==='notifications' && $notifs_unread): ?>
                    <span style="margin-left:auto;background:var(--gold);color:white;border-radius:10px;font-size:10px;padding:1px 7px;flex-shrink:0;"><?= $notifs_unread ?></span>
                <?php endif; ?>
                <?php if ($t==='chat' && $chat_unread): ?>
                    <span style="margin-left:auto;background:#E74C3C;color:white;border-radius:10px;font-size:10px;padding:1px 7px;flex-shrink:0;"><?= $chat_unread ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
            <div style="height:1px;background:var(--border);margin:8px 0;"></div>
            <a href="/appointment.php" style="display:flex;align-items:center;gap:10px;padding:11px 13px;border-radius:6px;text-decoration:none;color:var(--dark);font-size:13px;font-weight:500;white-space:nowrap;border-left:3px solid transparent;">
                <i class="fas fa-plus" style="font-size:13px;width:16px;text-align:center;flex-shrink:0;"></i>
                <span>Book Appointment</span>
            </a>
            <a href="/logout.php" style="display:flex;align-items:center;gap:10px;padding:11px 13px;border-radius:6px;text-decoration:none;color:var(--error);font-size:13px;font-weight:500;white-space:nowrap;border-left:3px solid transparent;">
                <i class="fas fa-sign-out-alt" style="font-size:13px;width:16px;text-align:center;flex-shrink:0;"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- ── Main ── -->
    <main>

    <?php if ($tab === 'appointments'): ?>
    <div style="background:var(--white);border-radius:10px;box-shadow:var(--shadow);padding:28px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--border);">
            <h3 style="font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:400;">My Appointments</h3>
            <a href="/appointment.php" class="btn btn-gold btn-sm">+ Book New</a>
        </div>
        <?php if ($appointments->num_rows === 0): ?>
            <div style="text-align:center;padding:48px 20px;color:var(--gray);">
                <div style="font-size:52px;margin-bottom:14px;">📅</div>
                <p style="margin-bottom:20px;">You have no appointments yet.</p>
                <a href="/appointment.php" class="btn btn-gold btn-sm">Book Your First Appointment</a>
            </div>
        <?php else: ?>
            <?php while ($appt = $appointments->fetch_assoc()): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 0;border-bottom:1px solid var(--border);">
                <div>
                    <div style="font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:400;"><?= date('l, F j, Y', strtotime($appt['appointment_date'])) ?></div>
                    <div style="font-size:13px;color:var(--gray);margin-top:3px;">
                        <?= date('g:i A', strtotime($appt['appointment_time'])) ?> &nbsp;·&nbsp;
                        <?= htmlspecialchars($appt['appointment_type']) ?> &nbsp;·&nbsp;
                        <?= htmlspecialchars($appt['appointment_for']) ?>
                    </div>
                </div>
                <span class="status-badge status-<?= $appt['status'] ?>"><?= ucfirst($appt['status']) ?></span>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <?php elseif ($tab === 'notifications'): ?>
    <div style="background:var(--white);border-radius:10px;box-shadow:var(--shadow);padding:28px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--border);">
            <h3 style="font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:400;">Notifications</h3>
            <?php if ($notifications->num_rows > 0): ?>
            <button id="clearAllNotifsBtn" style="background:none;border:1px solid var(--border);border-radius:6px;padding:6px 14px;font-size:12px;color:var(--gray);cursor:pointer;transition:all .2s;">
                <i class="fas fa-trash" style="margin-right:4px;"></i>Clear All
            </button>
            <?php endif; ?>
        </div>
        <div id="notif-list">
        <?php if ($notifications->num_rows === 0): ?>
            <div style="text-align:center;padding:48px 20px;color:var(--gray);">
                <div style="font-size:52px;margin-bottom:14px;">🔔</div>
                <p>No notifications yet.</p>
            </div>
        <?php else: ?>
            <?php while ($n = $notifications->fetch_assoc()): ?>
            <div id="notif-<?= $n['id'] ?>" style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border-radius:8px;margin-bottom:10px;border-left:3px solid <?= $n['is_read'] ? 'var(--border)' : 'var(--gold)' ?>;background:<?= $n['is_read'] ? '#f9f9f9' : 'var(--gold-pale)' ?>;font-size:14px;">
                <div style="flex:1;">
                    <?= htmlspecialchars($n['message']) ?>
                    <div style="font-size:11px;color:var(--gray);margin-top:5px;"><?= date('M j, Y g:i A', strtotime($n['created_at'])) ?></div>
                </div>
                <button class="notif-delete-btn" data-id="<?= $n['id'] ?>" title="Delete" style="background:none;border:none;cursor:pointer;color:#ccc;font-size:16px;padding:0;flex-shrink:0;line-height:1;transition:color .2s;">&#x2715;</button>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
        </div>
    </div>
    <script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>">
    function deleteNotif(id) {
        fetch('dashboard.php?tab=notifications', {
            method: 'POST',
            credentials: 'same-origin',
            referrerPolicy: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'notif_action=delete&notif_id=' + id
        }).then(r => r.json()).then(d => {
            if (d.ok) {
                var el = document.getElementById('notif-' + id);
                if (el) { el.style.transition='opacity .3s'; el.style.opacity='0'; setTimeout(()=>el.remove(),300); }
            }
        });
    }
    function clearAllNotifs() {
        if (!confirm('Clear all notifications?')) return;
        fetch('dashboard.php?tab=notifications', {
            method: 'POST',
            credentials: 'same-origin',
            referrerPolicy: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'notif_action=clear_all'
        }).then(r => r.json()).then(d => {
            if (d.ok) document.getElementById('notif-list').innerHTML = '<div style="text-align:center;padding:48px 20px;color:var(--gray);"><div style="font-size:52px;margin-bottom:14px;">🔔</div><p>No notifications yet.</p></div>';
        });
    }
    document.addEventListener('DOMContentLoaded', function() {
        var clearBtn = document.getElementById('clearAllNotifsBtn');
        if (clearBtn) {
            clearBtn.addEventListener('mouseover', function() { this.style.borderColor='#E74C3C'; this.style.color='#E74C3C'; });
            clearBtn.addEventListener('mouseout',  function() { this.style.borderColor='var(--border)'; this.style.color='var(--gray)'; });
            clearBtn.addEventListener('click', clearAllNotifs);
        }
        var notifList = document.getElementById('notif-list');
        if (notifList) {
            notifList.addEventListener('click', function(e) {
                var btn = e.target.closest('.notif-delete-btn');
                if (btn) deleteNotif(parseInt(btn.dataset.id));
            });
            notifList.addEventListener('mouseover', function(e) {
                var btn = e.target.closest('.notif-delete-btn');
                if (btn) btn.style.color = '#E74C3C';
            });
            notifList.addEventListener('mouseout', function(e) {
                var btn = e.target.closest('.notif-delete-btn');
                if (btn) btn.style.color = '#ccc';
            });
        }
    });
    </script>

    <?php elseif ($tab === 'chat'): ?>

    <!-- Status bar -->
    <div id="chatStatus" style="font-size:13px;color:var(--gray);margin-bottom:10px;padding:8px 14px;background:var(--cream);border-radius:6px;"></div>

    <!-- Chat container -->
    <div style="background:var(--white);border-radius:10px;box-shadow:var(--shadow);overflow:hidden;">
        <!-- Chat header -->
        <div style="padding:16px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;">
            <div style="width:40px;height:40px;border-radius:50%;background:var(--gold-pale);display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-store" style="color:var(--gold);font-size:15px;"></i>
            </div>
            <div>
                <div style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:400;">Allen and Rej Bridal</div>
                <div id="chatStatusLabel" style="font-size:12px;color:var(--gray);margin-top:1px;">Chat with Admin</div>
            </div>
        </div>

        <!-- Messages area -->
        <div id="chat-messages" style="height:360px;overflow-y:auto;padding:20px 24px;display:flex;flex-direction:column;gap:10px;background:var(--cream);-webkit-overflow-scrolling:touch;">
            <div style="text-align:center;padding:20px 0;">
                <div style="font-size:32px;margin-bottom:8px;">💬</div>
                <p style="font-size:13px;color:var(--gray);">Loading messages...</p>
            </div>
        </div>

        <!-- Input -->
        <div style="padding:14px 18px;border-top:1px solid var(--border);display:flex;gap:10px;align-items:center;">
            <input type="text" id="chat-input" placeholder="Type a message..." maxlength="500"
                style="flex:1;border:1.5px solid var(--border);border-radius:24px;padding:10px 18px;font-family:var(--font-body);font-size:14px;outline:none;transition:border-color 0.2s;">
            <button id="chat-send-btn" style="width:42px;height:42px;border-radius:50%;background:var(--gold);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-paper-plane" style="color:white;font-size:15px;"></i>
            </button>
        </div>
    </div>

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>">
    // ══════════════════════════════════════════════════
    //  CHAT — Admin only (no bot)
    // ══════════════════════════════════════════════════

    var chatLastId = 0;
    var chatCid    = <?= $cid ?>;

    // ── Business hours label ──
    function businessHoursLabel() {
        var now  = new Date();
        var ph   = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
        var hour = ph.getHours();
        return (hour >= 8 && hour < 18)
            ? 'We are currently open (8:00 AM – 6:00 PM)'
            : 'We are currently closed. You can still leave a message.';
    }

    // ── Pusher: receive admin replies in real time ──
    var pusher = new Pusher('637dc49b31b7fb57d8dc', { cluster: 'ap1' });
    var pChan  = pusher.subscribe('user-' + chatCid);
    pChan.bind('reply', function(data) {
        var box = document.getElementById('chat-messages');
        if (!box) return;
        box.appendChild(renderMsg(data));
        chatLastId = Math.max(chatLastId, parseInt(data.id || 0));
        box.scrollTop = box.scrollHeight;
    });

    // ── Render helpers ──
    function esc(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str)));
        return d.innerHTML;
    }

    function parseTime(ts) {
        var m = (ts || '').replace('T', ' ').match(/(\d{2}):(\d{2})/);
        if (!m) return '';
        var h = parseInt(m[1], 10), mn = parseInt(m[2], 10);
        return (h % 12 || 12) + ':' + String(mn).padStart(2, '0') + ' ' + (h >= 12 ? 'PM' : 'AM');
    }

    function renderMsg(m) {
        var isMe = m.sender === 'customer';
        var time = parseTime(m.created_at);
        var d = document.createElement('div');
        d.style.cssText = 'display:flex;flex-direction:column;align-items:' + (isMe ? 'flex-end' : 'flex-start') + ';';
        d.innerHTML =
            '<div style="max-width:72%;padding:10px 14px;border-radius:' +
            (isMe ? '18px 18px 4px 18px' : '18px 18px 18px 4px') +
            ';background:' + (isMe ? 'var(--gold)' : 'var(--white)') +
            ';color:' + (isMe ? 'white' : 'var(--dark)') +
            ';font-size:14px;line-height:1.6;box-shadow:0 1px 4px rgba(0,0,0,.08);word-break:break-word;">' +
            esc(m.message) + '</div>' +
            '<span style="font-size:11px;color:var(--gray);margin-top:3px;padding:0 4px;">' + esc(time) + '</span>';
        return d;
    }

    // ── Load message history ──
    function loadHistory() {
        fetch('svc.php', {
            method: 'POST',
            credentials: 'same-origin',
            referrerPolicy: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'mode=msgs&since=' + encodeURIComponent(chatLastId)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) return;
            var box = document.getElementById('chat-messages');
            if (data.messages && data.messages.length) {
                box.innerHTML = '';
                data.messages.forEach(function(m) {
                    box.appendChild(renderMsg(m));
                    chatLastId = Math.max(chatLastId, parseInt(m.id));
                });
                box.scrollTop = box.scrollHeight;
            } else {
                box.innerHTML = '<div style="text-align:center;padding:40px 0;"><div style="font-size:40px;margin-bottom:10px;">&#128172;</div><p style="font-size:13px;color:var(--gray);">No messages yet. Say hello!</p></div>';
            }
        }).catch(function() {});
    }

    // ── Send message ──
    function sendChat() {
        var inp = document.getElementById('chat-input');
        var msg = inp.value.trim();
        if (!msg) return;
        inp.value = '';
        inp.disabled = true;
        fetch('svc.php', {
            method: 'POST',
            credentials: 'same-origin',
            referrerPolicy: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'mode=send&message=' + encodeURIComponent(msg)
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            inp.disabled = false;
            inp.focus();
            if (d.ok) {
                var box = document.getElementById('chat-messages');
                box.appendChild(renderMsg({ sender: 'customer', message: msg, id: d.id || 0, created_at: d.created_at || '' }));
                chatLastId = Math.max(chatLastId, parseInt(d.id || 0));
                box.scrollTop = box.scrollHeight;
            }
        })
        .catch(function() { inp.disabled = false; });
    }

    // ── Boot ──
    document.addEventListener('DOMContentLoaded', function() {
        // Set business hours status
        document.getElementById('chatStatus').textContent = businessHoursLabel();

        // Input events
        var chatInput = document.getElementById('chat-input');
        if (chatInput) {
            chatInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') sendChat(); });
            chatInput.addEventListener('focus',   function() { this.style.borderColor = 'var(--gold)'; });
            chatInput.addEventListener('blur',    function() { this.style.borderColor = 'var(--border)'; });
        }

        var sendBtn = document.getElementById('chat-send-btn');
        if (sendBtn) sendBtn.addEventListener('click', sendChat);

        loadHistory();
    });
    </script>

    <?php elseif ($tab === 'profile'): ?>
    <div style="background:var(--white);border-radius:10px;box-shadow:var(--shadow);padding:28px;">
        <h3 style="font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:400;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--border);">My Profile</h3>
        <?php if ($success_p): ?><div class="alert alert-success"><?= $success_p ?></div><?php endif; ?>
        <?php if ($error_p): ?><div class="alert alert-error"><?= $error_p ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            <div class="form-grid">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" required value="<?= htmlspecialchars($customer['first_name']) ?>">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required value="<?= htmlspecialchars($customer['last_name']) ?>">
                </div>
                <div class="form-group">
                    <label>Email (cannot change)</label>
                    <input type="email" value="<?= htmlspecialchars($customer['email']) ?>" disabled style="color:var(--gray);">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
                </div>
                <div class="form-group full">
                    <label>New Password (leave blank to keep current)</label>
                    <input type="password" name="new_password" minlength="6" placeholder="Minimum 6 characters">
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn btn-gold">Save Changes</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    </main>
</div>
</div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>