<?php
require_once __DIR__ . '/includes/admin_header_pre.php';
$conn->query("UPDATE admins SET last_seen=NOW() WHERE id=".(int)$_SESSION['admin_id']);
$adminPageTitle = 'Live Chat';
require_once __DIR__ . '/includes/admin_header_output.php';
?>

<style>
/* ─────────────────────────────────────────
   ADMIN LIVE CHAT — scoped styles
   No inline styles. All layout here.
───────────────────────────────────────── */

/* Page header bar */
.chat-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}
.chat-page-header h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 32px;
    font-weight: 400;
    display: flex;
    align-items: center;
    gap: 10px;
}
.chat-page-header h1 i { color: var(--gold); }
.chat-online-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--gray);
}
.chat-online-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #27AE60;
    flex-shrink: 0;
}

/* Outer container */
.chat-container {
    display: grid;
    grid-template-columns: 280px 1fr;
    background: var(--white);
    border-radius: 10px;
    box-shadow: var(--shadow);
    overflow: hidden;
    height: 600px;
}

/* ── SIDEBAR (thread list) ── */
.chat-sidebar {
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.chat-sidebar-header {
    padding: 16px 18px;
    border-bottom: 1px solid var(--border);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--gray);
    flex-shrink: 0;
}
#thread-list {
    overflow-y: auto;
    flex: 1;
}
.thread-item {
    padding: 14px 18px;
    cursor: pointer;
    border-bottom: 1px solid var(--border);
    transition: background 0.15s;
    display: flex;
    gap: 10px;
    align-items: center;
}
.thread-item:hover      { background: var(--cream); }
.thread-item.unread     { background: var(--gold-pale); }
.thread-item.active     { background: #EEF6FF; }
.thread-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--gold-pale);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.thread-avatar i { color: var(--gold); font-size: 14px; }
.thread-info { min-width: 0; flex: 1; }
.thread-name {
    font-size: 14px;
    color: var(--dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.thread-name.bold { font-weight: 600; }
.thread-preview {
    font-size: 12px;
    color: var(--gray);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 2px;
}
.thread-unread-badge {
    background: #E74C3C;
    color: white;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    flex-shrink: 0;
}
.thread-empty {
    padding: 32px 20px;
    text-align: center;
    color: var(--gray);
    font-size: 13px;
    line-height: 1.7;
}

/* ── CHAT PANEL ── */
.chat-panel {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.chat-placeholder {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--gray);
    padding: 40px;
    text-align: center;
}
.chat-placeholder i {
    font-size: 48px;
    color: var(--border);
    margin-bottom: 16px;
}
.chat-placeholder p { font-size: 15px; }
.chat-placeholder small { font-size: 13px; margin-top: 6px; display: block; }

/* Active chat */
.chat-active {
    display: none;       /* shown by JS */
    flex-direction: column;
    height: 100%;
    overflow: hidden;
}
.chat-active-header {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}
/* Back button — hidden on desktop, shown on mobile */
.chat-back-btn {
    display: none;
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: var(--gold);
    padding: 4px 6px;
    line-height: 1;
    flex-shrink: 0;
}
.chat-active-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--gold-pale);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.chat-active-avatar i { color: var(--gold); font-size: 15px; }
.chat-customer-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px;
    font-weight: 400;
}
.chat-customer-sub {
    font-size: 12px;
    color: var(--gray);
}

/* Message area */
#chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #F9F9F7;
    overscroll-behavior: contain;   /* stop scroll leaking to page */
    -webkit-overflow-scrolling: touch;
}

/* Message bubbles */
.msg-row {
    display: flex;
    flex-direction: column;
}
.msg-row.admin   { align-items: flex-end; }
.msg-row.customer { align-items: flex-start; }
.msg-bubble {
    max-width: 72%;
    padding: 10px 14px;
    font-size: 14px;
    line-height: 1.55;
    word-break: break-word;     /* prevent long words overflowing */
    white-space: pre-wrap;
    box-shadow: 0 1px 4px rgba(0,0,0,0.07);
}
.msg-row.admin   .msg-bubble { background: var(--gold); color: white;        border-radius: 18px 18px 4px 18px; }
.msg-row.customer .msg-bubble { background: var(--white); color: var(--dark); border-radius: 18px 18px 18px 4px; }
.msg-meta {
    font-size: 11px;
    color: var(--gray);
    margin-top: 4px;
    padding: 0 4px;
}

/* Input bar */
.chat-input-bar {
    padding: 12px 16px;
    border-top: 1px solid var(--border);
    display: flex;
    gap: 10px;
    align-items: center;
    background: var(--white);
    flex-shrink: 0;
}
#chat-input {
    flex: 1;
    border: 1.5px solid var(--border);
    border-radius: 24px;
    padding: 10px 16px;
    font-family: var(--font-body);
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
    min-width: 0;       /* prevent flex overflow */
}
#chat-input:focus { border-color: var(--gold); }
.chat-send-btn {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: var(--gold);
    border: none;
    cursor: pointer;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s, transform 0.1s;
}
.chat-send-btn:hover  { background: #c9a030; }
.chat-send-btn:active { transform: scale(0.94); }
.chat-send-btn i { color: white; font-size: 14px; }

/* ─────────────────────────────────────────
   MOBILE  ≤768px
   Single-view: list OR chat, never both
───────────────────────────────────────── */
@media (max-width: 768px) {
    .chat-container {
        grid-template-columns: 1fr;   /* stack */
        height: auto;
        min-height: 85vh;
        position: relative;
        overflow: visible;
    }

    /* Sidebar fills full width, visible by default */
    .chat-sidebar {
        border-right: none;
        border-bottom: 1px solid var(--border);
        height: 100%;
        min-height: 85vh;
    }

    /* Chat panel: hidden by default on mobile */
    .chat-panel {
        display: none;
        position: absolute;
        inset: 0;
        z-index: 10;
        background: var(--white);
        border-radius: 10px;
        height: 85vh;
    }

    /* When a thread is opened, show panel and hide sidebar */
    .chat-container.chat-open .chat-sidebar { display: none; }
    .chat-container.chat-open .chat-panel   { display: flex; }

    /* Show back button on mobile */
    .chat-back-btn { display: flex; }

    /* Bigger touch targets for threads */
    .thread-item {
        padding: 16px 18px;
        min-height: 64px;
    }

    /* Wider bubbles on narrow screens */
    .msg-bubble { max-width: 86%; }
}
</style>

<!-- Page header -->
<div class="chat-page-header">
    <h1><i class="fas fa-comments"></i>Live Chat</h1>
    <div class="chat-online-badge">
        <span class="chat-online-dot"></span>
        <span>You are online</span>
    </div>
</div>

<!-- Main layout -->
<div class="chat-container" id="chatContainer">

    <!-- ── Thread list (sidebar) ── -->
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">Conversations</div>
        <div id="thread-list">
            <div class="thread-empty">Loading…</div>
        </div>
    </div>

    <!-- ── Chat panel ── -->
    <div class="chat-panel" id="chatPanel">

        <!-- Placeholder -->
        <div class="chat-placeholder" id="chat-placeholder">
            <i class="fas fa-comments"></i>
            <p>Select a conversation to open it</p>
            <small>New customer messages appear here automatically</small>
        </div>

        <!-- Active chat -->
        <div class="chat-active" id="chat-active">

            <!-- Header with back button -->
            <div class="chat-active-header">
                <button class="chat-back-btn" id="chatBackBtn" aria-label="Back to conversations">&#8592;</button>
                <div class="chat-active-avatar"><i class="fas fa-user"></i></div>
                <div>
                    <div class="chat-customer-name" id="chat-customer-name"></div>
                    <div class="chat-customer-sub">Customer</div>
                </div>
            </div>

            <!-- Messages -->
            <div id="chat-messages"></div>

            <!-- Input -->
            <div class="chat-input-bar">
                <input type="text" id="chat-input"
                    placeholder="Reply to customer…"
                    maxlength="500">
                <button class="chat-send-btn" id="admin-send-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>

        </div><!-- /chat-active -->
    </div><!-- /chat-panel -->

</div><!-- /chat-container -->

<script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>" src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>">
var activeCid    = null;
var chatLastId   = 0;
var chatPollTimer;

// ── Pusher setup (KEY only — secret is in PHP) ──
var pusher = new Pusher('637dc49b31b7fb57d8dc', { cluster: 'ap1' });
// Subscribe to admin inbox — receives new customer messages in real time
var adminChan = pusher.subscribe('inbox-admin');
adminChan.bind('message', function(data) {
    // A customer sent a new message — refresh thread list immediately
    loadThreads();
    // If we're currently viewing that customer's thread, refresh messages too
    if (activeCid && parseInt(data.customer_id) === activeCid) {
        loadChatMsgs();
    }
});

/* ── Utility ── */
function escHtml(s) {
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Mobile: open/close panel ── */
function openMobilePanel() {
    document.getElementById('chatContainer').classList.add('chat-open');
}
function closeMobilePanel() {
    document.getElementById('chatContainer').classList.remove('chat-open');
    // Stop per-thread polling when going back
    if (chatPollTimer) { clearInterval(chatPollTimer); chatPollTimer = null; }
    activeCid  = null;
    chatLastId = 0;
}

document.getElementById('chatBackBtn').addEventListener('click', closeMobilePanel);

/* ── Render a thread row ── */
function renderThread(t) {
    var unread = parseInt(t.unread) || 0;
    var el     = document.createElement('div');
    el.className  = 'thread-item' + (unread ? ' unread' : '');
    el.dataset.cid = t.id;

    el.innerHTML =
        '<div class="thread-avatar"><i class="fas fa-user"></i></div>' +
        '<div class="thread-info">' +
            '<div class="thread-name' + (unread ? ' bold' : '') + '">' + escHtml(t.first_name + ' ' + t.last_name) + '</div>' +
            '<div class="thread-preview">' + escHtml((t.preview || '').substring(0, 44)) + '</div>' +
        '</div>' +
        (unread ? '<span class="thread-unread-badge">' + unread + '</span>' : '');

    el.addEventListener('click', function() {
        openThread(t.id, t.first_name + ' ' + t.last_name);
    });
    return el;
}

/* ── Load thread list ── */
function loadThreads() {
    fetch('../svc.php?mode=threads', {
            credentials: 'same-origin',
            referrerPolicy: 'same-origin'
        })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (!data.threads) return;
            var list = document.getElementById('thread-list');
            list.innerHTML = '';

            if (!data.threads.length) {
                list.innerHTML = '<div class="thread-empty">No conversations yet.<br>Customers will appear here once they send a message.</div>';
                return;
            }

            data.threads.forEach(function(t) {
                var el = renderThread(t);
                if (parseInt(t.id) === activeCid) {
                    el.classList.remove('unread');
                    el.classList.add('active');
                }
                list.appendChild(el);
            });
        })
        .catch(function(){});
}


    // Parse "YYYY-MM-DD HH:MM:SS" directly — no Date() object, no timezone conversion
    function parseTime(ts) {
        var m = (ts || '').replace('T', ' ').match(/(\d{2}):(\d{2})/);
        if (!m) return '';
        var h = parseInt(m[1], 10), mn = parseInt(m[2], 10);
        return (h % 12 || 12) + ':' + String(mn).padStart(2, '0') + ' ' + (h >= 12 ? 'PM' : 'AM');
    }

/* ── Render a single message bubble ── */
function renderMsg(m) {
    var isAdmin = m.sender === 'admin';
    var time = parseTime(m.created_at);
    var row     = document.createElement('div');
    row.className = 'msg-row ' + (isAdmin ? 'admin' : 'customer');
    row.innerHTML =
        '<div class="msg-bubble">' + escHtml(m.message) + '</div>' +
        '<span class="msg-meta">' + (isAdmin ? 'You' : 'Customer') + ' · ' + time + '</span>';
    return row;
}

/* ── Open a conversation ── */
function openThread(cid, name) {
    activeCid  = parseInt(cid);
    chatLastId = 0;

    // Switch to active view
    document.getElementById('chat-placeholder').style.display = 'none';
    var active = document.getElementById('chat-active');
    active.style.display = 'flex';
    document.getElementById('chat-customer-name').textContent = name;
    document.getElementById('chat-messages').innerHTML = '';
    document.getElementById('chat-input').focus();

    // Highlight selected thread
    document.querySelectorAll('.thread-item').forEach(function(el) {
        el.classList.remove('active');
        if (parseInt(el.dataset.cid) === activeCid) el.classList.add('active');
    });

    // Load messages + start per-thread polling
    // Load existing messages once when thread is opened
    // Pusher delivers new messages in real time via adminChan.bind('message')
    loadChatMsgs();
    // Clear any previous timer
    if (chatPollTimer) { clearInterval(chatPollTimer); chatPollTimer = null; }

    // On mobile: slide to chat panel
    openMobilePanel();
}

/* ── Load messages for active thread ── */
function loadChatMsgs() {
    if (!activeCid) return;
    fetch('../svc.php?mode=load&cid=' + activeCid + '&since=' + chatLastId, {
            credentials: 'same-origin',
            referrerPolicy: 'same-origin'
        })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (!data.messages || !data.messages.length) return;
            var box = document.getElementById('chat-messages');
            var atBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 80;
            data.messages.forEach(function(m) {
                box.appendChild(renderMsg(m));
                chatLastId = Math.max(chatLastId, parseInt(m.id));
            });
            if (atBottom) box.scrollTop = box.scrollHeight;
        })
        .catch(function(){});
}

/* ── Send a message ── */
function sendAdminMsg() {
    var inp = document.getElementById('chat-input');
    var msg = inp.value.trim();
    if (!msg || !activeCid) return;
    inp.value    = '';
    inp.disabled = true;
    fetch('../svc.php', {
        method: 'POST',
        credentials: 'same-origin',
        referrerPolicy: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'mode=reply&cid=' + activeCid + '&message=' + encodeURIComponent(msg)
    })
    .then(function(r){ return r.json(); })
    .then(function(d) {
        inp.disabled = false;
        inp.focus();
        if (d.ok) {
            // Show admin's own message immediately in their UI
            var box = document.getElementById('chat-messages');
            box.appendChild(renderMsg({ sender: 'admin', message: msg, id: d.id || 0, created_at: d.created_at || '' }));
            chatLastId = Math.max(chatLastId, parseInt(d.id || 0));
            if (box) box.scrollTop = box.scrollHeight;
            // Refresh thread list unread counts
            loadThreads();
        }
    })
    .catch(function(){ inp.disabled = false; });
}

/* ── Keep admin online ── */
function pingOnline() {
    fetch('../svc.php?mode=pulse', {
        credentials: 'same-origin',
        referrerPolicy: 'same-origin'
    }).catch(function(){});
}

/* ── Boot ── */
document.addEventListener('DOMContentLoaded', function() {
    var inp  = document.getElementById('chat-input');
    var sBtn = document.getElementById('admin-send-btn');
    if (inp)  inp.addEventListener('keydown', function(e) { if (e.key === 'Enter') sendAdminMsg(); });
    if (sBtn) sBtn.addEventListener('click', sendAdminMsg);
});
loadThreads();
pingOnline();
setInterval(pingOnline, 30000); // 30s heartbeat
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>