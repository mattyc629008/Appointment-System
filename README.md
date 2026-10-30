# Allen and Rej Bridal — Complete Web System
## Setup Guide for XAMPP

---

## STEP 1 — Copy Files
Place the entire `spybaby/` folder inside:
```
C:\xampp\htdocs\spybaby\
```

---

## STEP 2 — Create Database
1. Start Apache and MySQL in XAMPP Control Panel
2. Open http://localhost/phpmyadmin
3. Click **New** (left sidebar)
4. Name it: `allen_rej_bridal`
5. Collation: `utf8mb4_unicode_ci`
6. Click **Create**

---

## STEP 3 — Import Database
1. Click `allen_rej_bridal` in the left sidebar
2. Click **Import** tab
3. Choose file: `spybaby/database.sql`
4. Click **Go**

---

## STEP 4 — Verify DB Connection
Open `includes/db.php` — it should say:
```php
define('DB_NAME', 'allen_rej_bridal');
```
If your MySQL has a password, add it to `DB_PASS`.

---

## STEP 5 — Check Uploads Folders
Make sure these folders exist and are writable:
```
spybaby/uploads/brides/
spybaby/uploads/gowns/
```
Both are included in the zip. If missing, create them manually.

---

## STEP 6 — Access the Site
| Page | URL |
|------|-----|
| Website | http://localhost/spybaby/ |
| Admin Panel | http://localhost/spybaby/admin/ |

---

## DEFAULT ADMIN LOGIN
| Field | Value |
|-------|-------|
| Username | `admin` |
| Password | `password` |

**Change this immediately** — go to Admin → Settings after first login.

---

## COMPLETE FILE MAP

```
spybaby/
├── index.php               Homepage
├── appointment.php         Book appointment (interactive calendar)
├── gowns.php               Gown catalog (filter by category)
├── our-brides.php          Gallery + photo submission
├── about.php               About Us + FAQ accordion
├── contact.php             Contact form (saves to DB)
├── alterations.php         Alterations services
├── login.php               Customer login
├── register.php            Customer register
├── logout.php              Customer logout
├── dashboard.php           Customer dashboard
│                           (Appointments / Notifications / Chat / Profile)
├── chat_api.php            Live chat API endpoint
│
├── database.sql            FRESH install — import this
├── database_migrate.sql    EXISTING install — import this instead
├── fix_chat_table.sql      Emergency fix — chat table only
│
├── css/style.css           All styles (desktop + mobile responsive)
├── js/main.js              Calendar, FAQ accordion, filters
│
├── uploads/
│   ├── brides/             Bride photo uploads
│   ├── gowns/              Gown photo uploads
│   └── .htaccess           Blocks PHP execution in uploads
│
├── includes/
│   ├── db.php              DB connection config
│   ├── auth.php            Session helpers
│   ├── header.php          Nav + mobile hamburger menu
│   └── footer.php          Hours, links, social
│
└── admin/
    ├── index.php           Dashboard (stats, appointments, quick links)
    ├── login.php           Admin login
    ├── logout.php          Admin logout
    ├── appointments.php    Confirm / cancel / complete / delete
    ├── customers.php       View all registered customers
    ├── gowns.php           Add gowns with photo upload
    ├── brides.php          Approve / reject bride photos
    ├── faqs.php            Add / delete FAQ entries
    ├── messages.php        View contact form messages + send to customer
    ├── chat.php            Live chat with customers (real-time)
    ├── settings.php        Change admin username / password
    └── includes/
        ├── admin_header_pre.php     PHP-only (no output) — session + DB
        ├── admin_header_output.php  HTML output — sidebar + layout
        └── admin_footer.php         Closes layout
```

---

## FEATURES

### Customer Side
- Browse gown catalog (filter by Bridal / Bridesmaids / Mothers)
- Book appointment — picks from interactive calendar, blocks taken slots
- Select a specific gown → carries to appointment booking
- Register / login / logout
- Dashboard: view appointments with status badges
- Dashboard: notifications from admin
- Dashboard: live chat with admin (polls every 4 seconds)
- Dashboard: edit profile + change password
- Submit "Our Brides" photo (pending admin approval)
- Contact form (message saved to DB)

### Admin Side
- Dashboard: stat cards (total appointments, pending, customers, gowns)
- Appointments: confirm / cancel / complete / delete, sends customer notification
- Customers: view all registered accounts
- Gown Catalog: add with photo upload, sizes, color, description / delete
- Our Brides: approve / reject / delete photo submissions
- FAQs: add / delete entries
- Messages: view contact form inbox, mark read/delete, send message to any customer
- Live Chat: real-time two-way chat with any customer
- Settings: change username and password
- Online status: `last_seen` updated every 55 seconds, shown to customers in chat

---

## SECURITY CHECKLIST
- [ ] Change default admin password (Admin → Settings)
- [ ] Set a MySQL password and update `includes/db.php`
- [ ] Keep XAMPP behind a firewall (not exposed to internet without HTTPS)
- [ ] uploads/.htaccess blocks PHP execution in upload folders

---

## TROUBLESHOOTING

| Problem | Fix |
|---------|-----|
| Blank page / DB error | Check `DB_NAME` in `includes/db.php` matches your database name |
| Can't upload photos | Check `uploads/brides/` and `uploads/gowns/` exist and are writable |
| Headers already sent | All PHP logic runs before `require_once header.php` — already fixed |
| Chat not working | Run `fix_chat_table.sql` in phpMyAdmin |
| Admin login redirect loop | Clear browser cookies / session |
