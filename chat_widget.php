<?php
// ─────────────────────────────────────────────────────────
//  includes/chat_widget.php
//  Floating chat widget — include at bottom of footer.php
//  (before </body>). Excluded from admin pages automatically
//  if you wrap the include with: if (!isset($_SESSION['admin_id']))
// ─────────────────────────────────────────────────────────

// Guard: don't show to admins
if (isset($_SESSION['admin_id'])) return;

// Make sure we have a DB connection (safe to call even if already included)
if (!isset($conn)) {
    require_once __DIR__ . '/db.php';
}

$_wcid      = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : 0;
$_wLoggedIn = $_wcid > 0;

// Fetch FAQs from DB for the bot
$_wFaqs = [];
$_wFaqRes = $conn->query("SELECT question, answer FROM faqs ORDER BY display_order ASC, id ASC");
if ($_wFaqRes) {
    while ($_wF = $_wFaqRes->fetch_assoc()) {
        $_wFaqs[] = ['q' => $_wF['question'], 'a' => $_wF['answer']];
    }
}
?>

<!-- ══════════════════════════════════════════════════════
     FLOATING CHAT WIDGET
     ══════════════════════════════════════════════════════ -->

<style nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>">
/* ── Widget Variables ── */
:root {
    --cw-gold:      #C9A84C;
    --cw-gold-pale: #fdf6e3;
    --cw-dark:      #1a1a1a;
    --cw-gray:      #888;
    --cw-white:     #ffffff;
    --cw-border:    #e8e0d0;
    --cw-cream:     #fafaf7;
    --cw-shadow:    0 8px 32px rgba(0,0,0,0.18);
    --cw-radius:    16px;
    --cw-w:         360px;
    --cw-h:         500px;
}

/* ── Launcher button ── */
#cw-launcher {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--cw-gold);
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 18px rgba(201,168,76,.55);
    z-index: 9998;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform .22s ease, box-shadow .22s ease;
    outline: none;
}
#cw-launcher:hover {
    transform: scale(1.08);
    box-shadow: 0 6px 24px rgba(201,168,76,.70);
}
#cw-launcher svg { transition: opacity .18s, transform .18s; }
#cw-launcher .cw-icon-chat { position: absolute; }
#cw-launcher .cw-icon-close { position: absolute; opacity: 0; transform: rotate(-90deg); }
#cw-launcher.open .cw-icon-chat  { opacity: 0; transform: rotate(90deg); }
#cw-launcher.open .cw-icon-close { opacity: 1; transform: rotate(0deg); }

/* ── Unread badge ── */
#cw-badge {
    position: absolute;
    top: -3px;
    right: -3px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #E74C3C;
    color: white;
    font-size: 11px;
    font-weight: 700;
    display: none;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
    pointer-events: none;
    font-family: sans-serif;
}

/* ── Chat popup panel ── */
#cw-panel {
    position: fixed;
    bottom: 100px;
    right: 28px;
    width: var(--cw-w);
    height: var(--cw-h);
    background: var(--cw-white);
    border-radius: var(--cw-radius);
    box-shadow: var(--cw-shadow);
    z-index: 9997;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--cw-border);

    /* Hidden by default */
    opacity: 0;
    transform: translateY(16px) scale(.97);
    pointer-events: none;
    transition: opacity .22s ease, transform .22s ease;
}
#cw-panel.open {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
}

/* ── Panel header ── */
#cw-header {
    background: var(--cw-gold);
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 11px;
    flex-shrink: 0;
}
.cw-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255,255,255,.22);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.cw-header-info { flex: 1; min-width: 0; }
.cw-header-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 17px;
    color: white;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cw-header-sub {
    font-size: 11px;
    color: rgba(255,255,255,.80);
    margin-top: 1px;
}

/* ── Mode toggle (only shown when logged in) ── */
#cw-tabs {
    display: flex;
    border-bottom: 1px solid var(--cw-border);
    background: var(--cw-white);
    flex-shrink: 0;
}
#cw-tabs button {
    flex: 1;
    padding: 9px 6px;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    color: var(--cw-gray);
    border-bottom: 2px solid transparent;
    transition: color .18s, border-color .18s;
    font-family: inherit;
}
#cw-tabs button.active {
    color: var(--cw-gold);
    border-bottom-color: var(--cw-gold);
}

/* ── Status bar ── */
#cw-status {
    font-size: 11.5px;
    color: var(--cw-gray);
    padding: 5px 14px;
    background: var(--cw-cream);
    border-bottom: 1px solid var(--cw-border);
    flex-shrink: 0;
    display: none;
}

/* ── Messages area ── */
#cw-messages {
    flex: 1;
    overflow-y: auto;
    padding: 14px 14px 8px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: var(--cw-cream);
    -webkit-overflow-scrolling: touch;
}
#cw-messages::-webkit-scrollbar { width: 4px; }
#cw-messages::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

/* ── Input area ── */
#cw-input-row {
    display: flex;
    gap: 8px;
    align-items: center;
    padding: 10px 12px;
    border-top: 1px solid var(--cw-border);
    background: var(--cw-white);
    flex-shrink: 0;
}
#cw-input {
    flex: 1;
    border: 1.5px solid var(--cw-border);
    border-radius: 22px;
    padding: 8px 15px;
    font-family: inherit;
    font-size: 13.5px;
    outline: none;
    transition: border-color .18s;
    background: var(--cw-white);
    color: var(--cw-dark);
}
#cw-input:focus { border-color: var(--cw-gold); }
#cw-send {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--cw-gold);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background .18s;
}
#cw-send:hover { background: #b8963e; }

/* ── Login nudge (shown to guests) ── */
#cw-login-nudge {
    font-size: 12px;
    text-align: center;
    padding: 7px 10px;
    color: var(--cw-gray);
    border-top: 1px solid var(--cw-border);
    background: var(--cw-white);
    flex-shrink: 0;
}
#cw-login-nudge a { color: var(--cw-gold); font-weight: 600; text-decoration: none; }

/* ── Mobile responsive ── */
@media (max-width: 420px) {
    :root { --cw-w: calc(100vw - 20px); }
    #cw-panel { right: 10px; bottom: 90px; }
    #cw-launcher { right: 16px; bottom: 16px; }
}
</style>

<!-- Pusher JS (only loaded once, even if dashboard.php also loads it) -->
<?php if ($wLoggedIn ?? $_wLoggedIn): ?>
<script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>">
if (!window.Pusher) {
    var _ps = document.createElement('script');
    _ps.src = 'https://js.pusher.com/8.2.0/pusher.min.js';
    document.head.appendChild(_ps);
}
</script>
<?php endif; ?>

<!-- ── Launcher button ── -->
<button id="cw-launcher" aria-label="Chat with us">
    <span id="cw-badge"></span>
    <!-- Chat icon -->
    <svg class="cw-icon-chat" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
    </svg>
    <!-- Close icon -->
    <svg class="cw-icon-close" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
    </svg>
</button>

<!-- ── Chat panel ── -->
<div id="cw-panel" role="dialog" aria-label="Chat">

    <!-- Header -->
    <div id="cw-header">
        <div class="cw-avatar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </div>
        <div class="cw-header-info">
            <div class="cw-header-name">Allen &amp; Rej Bridal</div>
            <div class="cw-header-sub" id="cw-mode-label">Assistant</div>
        </div>
    </div>

    <?php if ($_wLoggedIn): ?>
    <!-- Mode tabs (logged-in users only) -->
    <div id="cw-tabs">
        <button id="cw-tab-bot" class="active" onclick="cwSwitchMode('bot')">🤖 Chatbot</button>
        <button id="cw-tab-admin" onclick="cwSwitchMode('admin')">💬 Chat with Admin</button>
    </div>
    <?php endif; ?>

    <!-- Status bar (admin mode) -->
    <div id="cw-status"></div>

    <!-- Messages -->
    <div id="cw-messages">
        <div style="text-align:center;padding:30px 0;color:var(--cw-gray);font-size:13px;">
            <div style="font-size:36px;margin-bottom:8px;">💬</div>
            <p>Loading…</p>
        </div>
    </div>

    <!-- Input -->
    <div id="cw-input-row">
        <input type="text" id="cw-input" placeholder="Ask a question…" maxlength="500" autocomplete="off">
        <button id="cw-send" aria-label="Send">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
        </button>
    </div>

    <?php if (!$_wLoggedIn): ?>
    <!-- Login nudge for guests -->
    <div id="cw-login-nudge">
        Want to chat with our admin? <a href="/login.php">Log in</a> or <a href="/register.php">Sign up</a>
    </div>
    <?php endif; ?>

</div>

<script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>">
(function() {
'use strict';

// ── Config ──────────────────────────────────────────────
var CW = {
    loggedIn : <?= $_wLoggedIn ? 'true' : 'false' ?>,
    cid      : <?= $_wcid ?>,
    pusherKey: '637dc49b31b7fb57d8dc',
    cluster  : 'ap1',
    svcUrl   : '/svc.php',
    dbFaqs   : <?= json_encode($_wFaqs, JSON_UNESCAPED_UNICODE) ?>,
    mode     : 'bot',   // default mode
    lastId   : 0,
    botGreeted : false,
    botFaqShown: false,
    pusher   : null,
    pChan    : null,
    open     : false
};

// ── DOM refs ────────────────────────────────────────────
var launcher  = document.getElementById('cw-launcher');
var panel     = document.getElementById('cw-panel');
var badge     = document.getElementById('cw-badge');
var msgBox    = document.getElementById('cw-messages');
var input     = document.getElementById('cw-input');
var sendBtn   = document.getElementById('cw-send');
var statusBar = document.getElementById('cw-status');
var modeLabel = document.getElementById('cw-mode-label');
var tabBot    = document.getElementById('cw-tab-bot');
var tabAdmin  = document.getElementById('cw-tab-admin');

// ── Toggle panel open/close ─────────────────────────────
function cwToggle() {
    CW.open = !CW.open;
    launcher.classList.toggle('open', CW.open);
    panel.classList.toggle('open', CW.open);

    if (CW.open) {
        // First open: boot
        if (!CW.botGreeted) {
            cwSwitchMode('bot');
        }
        setTimeout(function() { input && input.focus(); }, 250);
        // Hide badge when opened
        cwSetBadge(0);
    }
}

launcher.addEventListener('click', function(e) {
    e.stopPropagation(); // prevent document click handler from immediately closing it
    cwToggle();
});

// Stop any click inside the panel from bubbling to the document close handler
panel.addEventListener('click', function(e) {
    e.stopPropagation();
});

// Close panel if clicking outside
document.addEventListener('click', function(e) {
    if (CW.open && !panel.contains(e.target) && e.target !== launcher && !launcher.contains(e.target)) {
        cwToggle();
    }
});

// ── Mode switch ─────────────────────────────────────────
function cwSwitchMode(mode) {
    CW.mode = mode;

    if (tabBot)   tabBot.classList.toggle('active',   mode === 'bot');
    if (tabAdmin) tabAdmin.classList.toggle('active', mode === 'admin');

    if (mode === 'bot') {
        modeLabel.textContent = 'AI Assistant';
        statusBar.style.display = 'none';
        input.placeholder = 'Ask a question…';
        msgBox.innerHTML = '';
        CW.botGreeted = false;
        CW.botFaqShown = false;
        cwShowBotGreeting();
    } else {
        modeLabel.textContent = 'Chat with Admin';
        statusBar.style.display = 'block';
        statusBar.textContent = cwBusinessHoursLabel();
        input.placeholder = 'Type a message…';
        msgBox.innerHTML = '<div style="text-align:center;padding:24px 0;color:var(--cw-gray);font-size:13px;"><div style="font-size:32px;margin-bottom:6px;">💬</div><p>Loading messages…</p></div>';
        CW.lastId = 0;
        cwLoadHistory();
        cwInitPusher();
    }
}

// ── Business hours ──────────────────────────────────────
function cwIsOpen() {
    var now = new Date();
    var ph  = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
    var h   = ph.getHours();
    return h >= 8 && h < 18;
}
function cwBusinessHoursLabel() {
    return cwIsOpen()
        ? '🟢 We are open (8:00 AM – 6:00 PM)'
        : '🔴 Currently closed — you can still leave a message.';
}

// ── Badge ───────────────────────────────────────────────
function cwSetBadge(n) {
    if (n > 0 && !CW.open) {
        badge.textContent = n > 9 ? '9+' : n;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

// Poll unread count every 60s (if logged in)
if (CW.loggedIn) {
    function cwPollBadge() {
        if (CW.open) return; // don't show badge if chat is open
        fetch(CW.svcUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'mode=badge'
        }).then(function(r){ return r.json(); }).then(function(d){
            cwSetBadge(d.count || 0);
        }).catch(function(){});
    }
    cwPollBadge();
    setInterval(cwPollBadge, 60000);
}

// ── Pusher init (admin mode, logged-in only) ────────────
function cwInitPusher() {
    if (!CW.loggedIn || CW.pusher) return;

    function _boot() {
        CW.pusher = new Pusher(CW.pusherKey, { cluster: CW.cluster });
        CW.pChan  = CW.pusher.subscribe('user-' + CW.cid);
        CW.pChan.bind('reply', function(data) {
            if (CW.mode !== 'admin') return;
            msgBox.appendChild(cwRenderMsg(data));
            CW.lastId = Math.max(CW.lastId, parseInt(data.id || 0));
            cwScrollBottom();
            if (!CW.open) cwSetBadge(1);
        });
    }

    if (window.Pusher) {
        _boot();
    } else {
        // Wait for Pusher script to load
        var t = setInterval(function() {
            if (window.Pusher) { clearInterval(t); _boot(); }
        }, 100);
    }
}

// ── Render helpers ──────────────────────────────────────
function cwEsc(str) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
}
function cwParseTime(ts) {
    var m = (ts || '').replace('T', ' ').match(/(\d{2}):(\d{2})/);
    if (!m) return new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    var h = parseInt(m[1], 10), mn = parseInt(m[2], 10);
    return (h % 12 || 12) + ':' + String(mn).padStart(2,'0') + ' ' + (h >= 12 ? 'PM' : 'AM');
}

function cwRenderMsg(m) {
    var isMe = m.sender === 'customer';
    var time = cwParseTime(m.created_at);
    var d = document.createElement('div');
    d.style.cssText = 'display:flex;flex-direction:column;align-items:' + (isMe ? 'flex-end' : 'flex-start') + ';';
    d.innerHTML =
        '<div style="max-width:78%;padding:9px 13px;border-radius:' +
        (isMe ? '16px 16px 3px 16px' : '16px 16px 16px 3px') +
        ';background:' + (isMe ? 'var(--cw-gold)' : 'var(--cw-white)') +
        ';color:' + (isMe ? 'white' : 'var(--cw-dark)') +
        ';font-size:13.5px;line-height:1.55;box-shadow:0 1px 4px rgba(0,0,0,.08);word-break:break-word;">' +
        cwEsc(m.message) + '</div>' +
        '<span style="font-size:10.5px;color:var(--cw-gray);margin-top:2px;padding:0 3px;">' + cwEsc(time) + '</span>';
    return d;
}

function cwRenderBotMsg(html) {
    var now = new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    var d = document.createElement('div');
    d.style.cssText = 'display:flex;flex-direction:column;align-items:flex-start;';
    d.innerHTML =
        '<div style="display:flex;align-items:center;gap:5px;margin-bottom:3px;">' +
        '<div style="width:20px;height:20px;border-radius:50%;background:var(--cw-gold-pale);display:flex;align-items:center;justify-content:center;font-size:10px;">🤖</div>' +
        '<span style="font-size:10px;font-weight:700;color:var(--cw-gray);text-transform:uppercase;letter-spacing:.05em;">Assistant</span>' +
        '</div>' +
        '<div style="max-width:82%;padding:10px 13px;border-radius:3px 16px 16px 16px;background:#EFF6FF;color:#1e3a5f;font-size:13.5px;line-height:1.6;border-left:3px solid #93C5FD;word-break:break-word;">' + html + '</div>' +
        '<span style="font-size:10.5px;color:var(--cw-gray);margin-top:2px;padding:0 3px;">' + now + '</span>';
    return d;
}

function cwScrollBottom() {
    msgBox.scrollTop = msgBox.scrollHeight;
}

// ── Bot logic ────────────────────────────────────────────
var cwBotFAQs = [
    { key: 'book',    label: '📅 Book an Appointment',       reply: 'To book an appointment, visit our booking page:<br><br><a href="/appointment.php" style="color:var(--cw-gold);font-weight:600;">Click here to Book Now →</a><br><br>Available <strong>Mon–Sun, 8:00 AM to 6:00 PM</strong>.' },
    { key: 'pricing', label: '💰 Pricing Inquiry',            reply: 'Our pricing depends on the gown type:<br><br>• Wedding / Bridesmaid gowns — Varies by designer<br>• JS Prom &amp; Debut gowns — Competitively priced<br>• Alterations — Priced per service<br>• Barong / Coat — Available for rental<br><br>For exact pricing, <a href="/appointment.php" style="color:var(--cw-gold);">book an appointment</a> with our stylists.' },
    { key: 'faqs',    label: '❓ FAQs',                        reply: null },
    { key: 'location',label: '📍 Location &amp; Hours',        reply: '<strong>Pasilio 2k-7, 2nd Floor</strong><br>Dragon 8 Mall, C.M. Recto St., Tondo, Manila<br><br>Open: <strong>Mon–Sun, 8:00 AM – 6:00 PM</strong><br>Call/Text: <a href="tel:09173872470" style="color:var(--cw-gold);">0917 387 2470</a>' },
    { key: 'other',   label: '📞 Other Concerns',              reply: 'For other concerns:<br><br><a href="https://www.facebook.com/AlennAndRejBridalShop" target="_blank" style="color:var(--cw-gold);">📘 Facebook: Allen and Rej Bridal</a><br>Call/Text: <a href="tel:09173872470" style="color:var(--cw-gold);">0917 387 2470</a>' + (CW.loggedIn ? '<br><br>Or switch to <strong>Chat with Admin</strong> above!' : '<br><br>Or <a href="/login.php" style="color:var(--cw-gold);">log in</a> to message us directly.') }
];

function cwShowBotGreeting() {
    if (CW.botGreeted) return;
    CW.botGreeted = true;
    msgBox.innerHTML = '';
    msgBox.appendChild(cwRenderBotMsg('Hello! 👋 I\'m the Allen &amp; Rej assistant. Choose a topic below or type your question.'));
    cwShowBotButtons();
    cwScrollBottom();
}

function cwShowBotButtons() {
    if (CW.botFaqShown) return;
    CW.botFaqShown = true;
    var wrap = document.createElement('div');
    wrap.id = 'cw-bot-wrap';
    wrap.style.cssText = 'display:flex;flex-direction:column;align-items:flex-start;margin-top:4px;';
    var lbl = document.createElement('div');
    lbl.style.cssText = 'font-size:11px;color:var(--cw-gray);margin-bottom:5px;padding-left:3px;';
    lbl.textContent = 'Choose a topic:';
    wrap.appendChild(lbl);
    var row = document.createElement('div');
    row.style.cssText = 'display:flex;flex-wrap:wrap;gap:6px;';
    cwBotFAQs.forEach(function(faq) {
        var btn = document.createElement('button');
        btn.textContent = faq.label;
        btn.style.cssText = 'background:var(--cw-white);border:1.5px solid var(--cw-gold);color:var(--cw-dark);border-radius:18px;padding:6px 12px;font-size:12px;cursor:pointer;font-family:inherit;line-height:1;transition:background .18s,color .18s;';
        btn.onmouseover = function(){ this.style.background='var(--cw-gold)'; this.style.color='white'; };
        btn.onmouseout  = function(){ this.style.background='var(--cw-white)'; this.style.color='var(--cw-dark)'; };
        btn.onclick = function(){ cwBotReply(faq); };
        row.appendChild(btn);
    });
    wrap.appendChild(row);
    msgBox.appendChild(wrap);
    cwScrollBottom();
}

function cwBotReply(faq) {
    var wrap = document.getElementById('cw-bot-wrap');
    if (wrap) wrap.remove();
    CW.botFaqShown = false;
    // User bubble
    var sel = document.createElement('div');
    sel.style.cssText = 'display:flex;flex-direction:column;align-items:flex-end;';
    var now = new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
    sel.innerHTML = '<div style="max-width:78%;padding:9px 13px;border-radius:16px 16px 3px 16px;background:var(--cw-gold);color:white;font-size:13.5px;word-break:break-word;">' + cwEsc(faq.label.replace(/^[^\s]+ /,'')) + '</div><span style="font-size:10.5px;color:var(--cw-gray);margin-top:2px;padding:0 3px;">' + now + '</span>';
    msgBox.appendChild(sel);

    if (faq.key === 'faqs') { cwShowDBFAQs(); return; }

    var typing = cwRenderBotMsg('<em style="color:#93a3b1;">Typing…</em>');
    msgBox.appendChild(typing);
    cwScrollBottom();
    setTimeout(function() {
        typing.remove();
        msgBox.appendChild(cwRenderBotMsg(faq.reply));
        var backDiv = document.createElement('div');
        backDiv.style.cssText = 'display:flex;flex-direction:column;align-items:flex-start;margin-top:2px;';
        var backBtn = document.createElement('button');
        backBtn.textContent = '← Back to topics';
        backBtn.style.cssText = 'background:transparent;border:1px solid var(--cw-border);color:var(--cw-gray);border-radius:18px;padding:5px 11px;font-size:11.5px;cursor:pointer;font-family:inherit;';
        backBtn.onclick = function(){ backDiv.remove(); cwShowBotButtons(); };
        backDiv.appendChild(backBtn);
        msgBox.appendChild(backDiv);
        cwScrollBottom();
    }, 750);
}

function cwShowDBFAQs() {
    if (!CW.dbFaqs || CW.dbFaqs.length === 0) {
        msgBox.appendChild(cwRenderBotMsg('No FAQs yet. ' + (CW.loggedIn ? 'Switch to Chat with Admin to ask us directly!' : '<a href="/login.php" style="color:var(--cw-gold);">Log in</a> to message us directly.')));
        setTimeout(function(){ cwShowBotButtons(); }, 400);
        cwScrollBottom(); return;
    }
    msgBox.appendChild(cwRenderBotMsg('Here are our frequently asked questions:'));
    var wrap = document.createElement('div');
    wrap.id = 'cw-db-faq-wrap';
    wrap.style.cssText = 'display:flex;flex-direction:column;align-items:flex-start;margin-top:4px;gap:5px;width:100%;';
    CW.dbFaqs.forEach(function(faq) {
        var btn = document.createElement('button');
        btn.textContent = faq.q;
        btn.style.cssText = 'background:var(--cw-white);border:1.5px solid var(--cw-border);color:var(--cw-dark);border-radius:9px;padding:8px 12px;font-size:12.5px;cursor:pointer;text-align:left;width:100%;max-width:100%;font-family:inherit;line-height:1.35;';
        btn.onclick = function(){ cwShowDBAnswer(faq); };
        wrap.appendChild(btn);
    });
    var backBtn = document.createElement('button');
    backBtn.textContent = '← Back to topics';
    backBtn.style.cssText = 'background:transparent;border:1px solid var(--cw-border);color:var(--cw-gray);border-radius:18px;padding:5px 11px;font-size:11.5px;cursor:pointer;margin-top:3px;font-family:inherit;';
    backBtn.onclick = function(){ var w=document.getElementById('cw-db-faq-wrap'); if(w)w.remove(); cwShowBotButtons(); };
    wrap.appendChild(backBtn);
    msgBox.appendChild(wrap);
    cwScrollBottom();
}

function cwShowDBAnswer(faq) {
    var wrap = document.getElementById('cw-db-faq-wrap');
    if (wrap) wrap.remove();
    var sel = document.createElement('div');
    sel.style.cssText = 'display:flex;flex-direction:column;align-items:flex-end;';
    var now = new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
    sel.innerHTML = '<div style="max-width:78%;padding:9px 13px;border-radius:16px 16px 3px 16px;background:var(--cw-gold);color:white;font-size:13.5px;word-break:break-word;">' + cwEsc(faq.q) + '</div><span style="font-size:10.5px;color:var(--cw-gray);margin-top:2px;padding:0 3px;">' + now + '</span>';
    msgBox.appendChild(sel);
    var typing = cwRenderBotMsg('<em style="color:#93a3b1;">Typing…</em>');
    msgBox.appendChild(typing);
    cwScrollBottom();
    setTimeout(function(){
        typing.remove();
        msgBox.appendChild(cwRenderBotMsg(cwEsc(faq.a)));
        var nav = document.createElement('div');
        nav.style.cssText = 'display:flex;gap:6px;flex-wrap:wrap;margin-top:2px;';
        var more = document.createElement('button');
        more.textContent = 'More FAQs';
        more.style.cssText = 'background:var(--cw-white);border:1.5px solid var(--cw-gold);color:var(--cw-dark);border-radius:18px;padding:5px 12px;font-size:12px;cursor:pointer;font-family:inherit;';
        more.onclick = function(){ nav.remove(); cwShowDBFAQs(); };
        var main = document.createElement('button');
        main.textContent = 'Main topics';
        main.style.cssText = 'background:transparent;border:1px solid var(--cw-border);color:var(--cw-gray);border-radius:18px;padding:5px 12px;font-size:12px;cursor:pointer;font-family:inherit;';
        main.onclick = function(){ nav.remove(); cwShowBotButtons(); };
        nav.appendChild(more); nav.appendChild(main);
        msgBox.appendChild(nav);
        cwScrollBottom();
    }, 750);
}

function cwHandleBotInput(msg) {
    var wrap = document.getElementById('cw-bot-wrap');
    if (wrap) wrap.remove();
    CW.botFaqShown = false;
    var sel = document.createElement('div');
    sel.style.cssText = 'display:flex;flex-direction:column;align-items:flex-end;';
    var now = new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
    sel.innerHTML = '<div style="max-width:78%;padding:9px 13px;border-radius:16px 16px 3px 16px;background:var(--cw-gold);color:white;font-size:13.5px;word-break:break-word;">' + cwEsc(msg) + '</div><span style="font-size:10.5px;color:var(--cw-gray);margin-top:2px;padding:0 3px;">' + now + '</span>';
    msgBox.appendChild(sel);
    var ml = msg.toLowerCase();
    var matched = null;
    if (ml.includes('book') || ml.includes('appoint'))              matched = cwBotFAQs[0];
    else if (ml.includes('price') || ml.includes('cost') || ml.includes('rate')) matched = cwBotFAQs[1];
    else if (ml.includes('faq')  || ml.includes('question'))        matched = cwBotFAQs[2];
    else if (ml.includes('location') || ml.includes('address') || ml.includes('where')) matched = cwBotFAQs[3];
    var typing = cwRenderBotMsg('<em style="color:#93a3b1;">Typing…</em>');
    msgBox.appendChild(typing);
    cwScrollBottom();
    setTimeout(function(){
        typing.remove();
        if (matched && matched.key === 'faqs') { cwShowDBFAQs(); return; }
        if (matched) {
            msgBox.appendChild(cwRenderBotMsg(matched.reply));
        } else {
            msgBox.appendChild(cwRenderBotMsg('I didn\'t quite catch that. Choose a topic below or ' + (CW.loggedIn ? 'switch to <strong>Chat with Admin</strong> for personal help.' : '<a href="/login.php" style="color:var(--cw-gold);">log in</a> to message us directly.')));
            cwShowBotButtons();
        }
        cwScrollBottom();
    }, 750);
}

// ── Admin chat (logged-in only) ──────────────────────────
function cwLoadHistory() {
    fetch(CW.svcUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'mode=msgs&since=' + encodeURIComponent(CW.lastId)
    }).then(function(r){ return r.json(); }).then(function(data){
        if (data.error) return;
        if (data.messages && data.messages.length) {
            msgBox.innerHTML = '';
            data.messages.forEach(function(m){
                msgBox.appendChild(cwRenderMsg(m));
                CW.lastId = Math.max(CW.lastId, parseInt(m.id));
            });
        } else {
            msgBox.innerHTML = '<div style="text-align:center;padding:32px 0;color:var(--cw-gray);font-size:13px;"><div style="font-size:36px;margin-bottom:8px;">💬</div><p>No messages yet. Say hello!</p></div>';
        }
        cwScrollBottom();
    }).catch(function(){});
}

function cwSendAdmin(msg) {
    input.disabled = true;
    fetch(CW.svcUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'mode=send&message=' + encodeURIComponent(msg)
    }).then(function(r){ return r.json(); }).then(function(d){
        input.disabled = false;
        input.focus();
        if (d.ok) {
            msgBox.appendChild(cwRenderMsg({ sender: 'customer', message: msg, id: d.id || 0, created_at: d.created_at || '' }));
            CW.lastId = Math.max(CW.lastId, parseInt(d.id || 0));
            cwScrollBottom();
        }
    }).catch(function(){ input.disabled = false; });
}

// ── Send handler ────────────────────────────────────────
function cwSend() {
    var msg = input.value.trim();
    if (!msg) return;
    input.value = '';
    if (CW.mode === 'bot') {
        cwHandleBotInput(msg);
    } else if (CW.loggedIn) {
        cwSendAdmin(msg);
    }
}

// ── Wire up events ───────────────────────────────────────
sendBtn.addEventListener('click', cwSend);
input.addEventListener('keydown', function(e){ if (e.key === 'Enter') cwSend(); });

// Tab events (logged-in only, but guard anyway)
if (tabBot)   tabBot.addEventListener('click',   function(){ cwSwitchMode('bot'); });
if (tabAdmin) tabAdmin.addEventListener('click',  function(){ cwSwitchMode('admin'); });

// Expose globally so inline onclick attributes also work
window.cwSwitchMode = cwSwitchMode;

})();
</script>