<?php
// ─────────────────────────────────────────────────────────
//  msg.php  (replaces chat_api.php)
//  Safe name — no "chat", "api", "fetch" in filename.
//  Params use "mode=" instead of "action=" (InfinityFree safe).
// ─────────────────────────────────────────────────────────

ob_start();

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
$conn->query("SET time_zone = '+08:00'"); // Asia/Manila
require_once __DIR__ . '/includes/pusher_cfg.php';

ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function cust_logged_in()  { return isset($_SESSION['customer_id']); }
function admin_logged_in() { return isset($_SESSION['admin_id']); }

// Safe param: mode= instead of action=
$mode = $_GET['mode'] ?? $_POST['mode'] ?? '';


// ══════════════════════════════════════════════════════════
//  CUSTOMER SIDE
// ══════════════════════════════════════════════════════════

// ── mode=status  (replaces fetch_customer — online check only) ──
// Polling kept but ONLY for admin online status, not messages.
// Interval: 30s (very relaxed).
if ($mode === 'status') {
    if (!cust_logged_in()) {
        echo json_encode(['error' => 'session_expired', 'admin_online' => false]);
        exit;
    }
    $online = false;
    $r = $conn->query("SELECT last_seen FROM admins ORDER BY last_seen DESC LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) {
        // Threshold 180s: admin heartbeat every 30s, so 180s = 5 missed beats before offline
        $online = !empty($row['last_seen']) && (time() - strtotime($row['last_seen'])) < 180;
    }
    echo json_encode(['admin_online' => $online]);
    exit;
}

// ── mode=msgs  (initial load — fetch existing messages on page open) ──
if ($mode === 'msgs') {
    if (!cust_logged_in()) {
        echo json_encode(['error' => 'session_expired', 'messages' => []]);
        exit;
    }
    $cid   = (int)$_SESSION['customer_id'];
    $since = (int)($_POST['since'] ?? $_GET['since'] ?? 0);

    // Mark admin messages as read
    $conn->query("UPDATE chat_messages SET is_read=1 WHERE customer_id=$cid AND sender='admin' AND is_read=0");

    $stmt = $conn->prepare("SELECT id, sender, message, created_at FROM chat_messages WHERE customer_id=? AND id > ? ORDER BY created_at ASC LIMIT 60");
    $stmt->bind_param("ii", $cid, $since);
    $stmt->execute();
    $msgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['messages' => $msgs]);
    exit;
}

// ── mode=send  (customer sends a message) ──
if ($mode === 'send') {
    if (!cust_logged_in()) {
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }
    $cid = (int)$_SESSION['customer_id'];
    $msg = trim($_POST['message'] ?? '');
    if ($msg === '') {
        echo json_encode(['error' => 'Empty message']);
        exit;
    }

    // 1. Save to database
    $stmt = $conn->prepare("INSERT INTO chat_messages (customer_id, sender, message) VALUES (?, 'customer', ?)");
    $stmt->bind_param("is", $cid, $msg);
    $stmt->execute();
    $new_id = $conn->insert_id;
    // Read back the exact created_at MySQL stored (avoids PHP vs DB timezone mismatch)
    $ts_row = $conn->query("SELECT created_at FROM chat_messages WHERE id=$new_id")->fetch_assoc();
    $created_at = $ts_row['created_at'] ?? date('Y-m-d H:i:s');

    // 2. Get customer name for admin notification
    $stmt2 = $conn->prepare("SELECT first_name, last_name FROM customers WHERE id=?");
    $stmt2->bind_param("i", $cid);
    $stmt2->execute();
    $cust = $stmt2->get_result()->fetch_assoc();

    // 3. Trigger Pusher: notify admin panel of new message
    //    Admin subscribes to channel 'inbox-admin'
    pusher_trigger('inbox-admin', 'message', [
        'customer_id'   => $cid,
        'customer_name' => trim(($cust['first_name'] ?? '') . ' ' . ($cust['last_name'] ?? '')),
        'message'       => $msg,
        'id'            => $new_id,
        'created_at'    => $created_at,
    ]);

    echo json_encode(['ok' => true, 'id' => $new_id, 'created_at' => $created_at]);
    exit;
}

// ── mode=badge  (unread count — for notification dot) ──
if ($mode === 'badge') {
    if (!cust_logged_in()) {
        echo json_encode(['count' => 0]);
        exit;
    }
    $cid  = (int)$_SESSION['customer_id'];
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM chat_messages WHERE customer_id=? AND sender='admin' AND is_read=0");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['c'];
    echo json_encode(['count' => $count]);
    exit;
}


// ══════════════════════════════════════════════════════════
//  ADMIN SIDE
// ══════════════════════════════════════════════════════════

// ── mode=threads  (initial thread list load) ──
if ($mode === 'threads') {
    if (!admin_logged_in()) {
        echo json_encode(['error' => 'Not admin']);
        exit;
    }
    // Relaxed last_seen write
    $conn->query("UPDATE admins SET last_seen=NOW() WHERE id=" . (int)$_SESSION['admin_id'] . " AND (last_seen IS NULL OR last_seen < NOW() - INTERVAL 30 SECOND)");

    $res = $conn->query("
        SELECT c.id, c.first_name, c.last_name,
               MAX(cm.created_at) AS last_msg,
               SUM(CASE WHEN cm.sender='customer' AND cm.is_read=0 THEN 1 ELSE 0 END) AS unread,
               (SELECT message FROM chat_messages WHERE customer_id=c.id ORDER BY created_at DESC LIMIT 1) AS preview
        FROM customers c
        INNER JOIN chat_messages cm ON cm.customer_id = c.id
        GROUP BY c.id
        ORDER BY last_msg DESC
    ");
    $threads = [];
    while ($r = $res->fetch_assoc()) $threads[] = $r;
    echo json_encode(['threads' => $threads]);
    exit;
}

// ── mode=load  (load messages for a specific customer) ──
if ($mode === 'load') {
    if (!admin_logged_in()) {
        echo json_encode(['error' => 'Not admin']);
        exit;
    }
    $conn->query("UPDATE admins SET last_seen=NOW() WHERE id=" . (int)$_SESSION['admin_id'] . " AND (last_seen IS NULL OR last_seen < NOW() - INTERVAL 30 SECOND)");

    $cid   = (int)($_GET['cid'] ?? 0);  // safe param: cid instead of customer_id
    $since = (int)($_GET['since'] ?? 0);
    if (!$cid) {
        echo json_encode(['error' => 'No customer']);
        exit;
    }

    $conn->query("UPDATE chat_messages SET is_read=1 WHERE customer_id=$cid AND sender='customer' AND is_read=0");

    $stmt = $conn->prepare("SELECT id, sender, message, created_at FROM chat_messages WHERE customer_id=? AND id > ? ORDER BY created_at ASC LIMIT 100");
    $stmt->bind_param("ii", $cid, $since);
    $stmt->execute();
    $msgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt2 = $conn->prepare("SELECT first_name, last_name FROM customers WHERE id=?");
    $stmt2->bind_param("i", $cid);
    $stmt2->execute();
    $cr = $stmt2->get_result()->fetch_assoc();

    echo json_encode(['messages' => $msgs, 'customer' => $cr]);
    exit;
}

// ── mode=reply  (admin sends a message) ──
if ($mode === 'reply') {
    if (!admin_logged_in()) {
        echo json_encode(['error' => 'Not admin']);
        exit;
    }
    $conn->query("UPDATE admins SET last_seen=NOW() WHERE id=" . (int)$_SESSION['admin_id']);

    $cid = (int)($_POST['cid'] ?? 0);
    $msg = trim($_POST['message'] ?? '');
    if (!$cid || $msg === '') {
        echo json_encode(['error' => 'Missing data']);
        exit;
    }

    // 1. Save to database
    $stmt = $conn->prepare("INSERT INTO chat_messages (customer_id, sender, message) VALUES (?, 'admin', ?)");
    $stmt->bind_param("is", $cid, $msg);
    $stmt->execute();
    $new_id = $conn->insert_id;
    // Read back the exact created_at MySQL stored (avoids PHP vs DB timezone mismatch)
    $ts_row = $conn->query("SELECT created_at FROM chat_messages WHERE id=$new_id")->fetch_assoc();
    $created_at = $ts_row['created_at'] ?? date('Y-m-d H:i:s');

    // 2. Trigger Pusher: notify customer of new reply
    //    Customer subscribes to channel 'user-{cid}'
    pusher_trigger('user-' . $cid, 'reply', [
        'sender'     => 'admin',
        'message'    => $msg,
        'id'         => $new_id,
        'created_at' => $created_at,
    ]);

    echo json_encode(['ok' => true, 'id' => $new_id, 'created_at' => $created_at]);
    exit;
}

// ── mode=pulse  (admin heartbeat — keeps last_seen fresh) ──
if ($mode === 'pulse') {
    if (!admin_logged_in()) {
        echo json_encode(['ok' => false]);
        exit;
    }
    // Always write on pulse — this IS the heartbeat, no guard needed
    $conn->query("UPDATE admins SET last_seen=NOW() WHERE id=" . (int)$_SESSION['admin_id']);
    echo json_encode(['ok' => true]);
    exit;
}

// ── Unknown mode ───────────────────────────────────────────
echo json_encode(['error' => 'Unknown mode']);