<?php
    ini_set('display_errors', 1);
error_reporting(E_ALL);
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';

// Fetch a few approved bride photos for preview
$brides_result = $conn->query("SELECT * FROM bride_submissions WHERE approved=1 ORDER BY submitted_at DESC LIMIT 3");
?>

<!-- HERO -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Serving brides and formal shoppers for <em>over 9 years.</em></h1>
        <p>A private, personalized experience to help you find the gown that makes your moment unforgettable.</p>
        <div class="hero-btns">
            <a href="/appointment.php" class="btn btn-gold">Book an Appointment</a>
            <a href="/gowns.php" class="btn btn-outline">View Gown Catalog</a>
        </div>
    </div>
</section>

<!-- LOCATION / INFO -->
<section class="location-section">
    <div class="container">
        <div class="location-grid">
            <div class="location-info">
                <span class="gold-line"></span>
                <h2 class="section-title">Find Us</h2>
                <div class="info-row">
                    <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <strong>Address</strong>
                        <p>Pasilio 2k-7, 2nd Floor, Dragon 8 Mall<br>C.M. Recto St., Tondo, Manila</p>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon"><i class="fas fa-phone"></i></div>
                    <div>
                        <strong>Phone</strong>
                        <p><a href="tel:09173872470" style="color:var(--gold);text-decoration:none;">0917 387 2470</a></p>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon"><i class="fab fa-facebook"></i></div>
                    <div>
                        <strong>Facebook</strong>
                        <p><a href="https://www.facebook.com/AlennAndRejBridalShop" target="_blank" style="color:var(--gold);text-decoration:none;">Facebook: Allen and Rej Bridal</a></p>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <strong>Hours</strong>
                        <table class="hours-table">
                            <tr><td>Monday</td><td>8:00 AM – 6:00 PM</td></tr>
                            <tr><td>Tuesday</td><td>8:00 AM – 6:00 PM</td></tr>
                            <tr><td>Wednesday</td><td>8:00 AM – 6:00 PM</td></tr>
                            <tr><td>Thursday</td><td>8:00 AM – 6:00 PM</td></tr>
                            <tr><td>Friday</td><td>8:00 AM – 6:00 PM</td></tr>
                            <tr><td>Saturday</td><td>8:00 AM – 6:00 PM</td></tr>
                            <tr><td>Sunday</td><td>8:00 AM – 6:00 PM</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="map-placeholder">
                <iframe
                    src="https://www.google.com/maps?q=Dragon+8+Shopping+Mall,C.M.+Recto+Ave,+Tondo,+Manila&output=embed"
                    allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>
</section>

<!-- QUICK LINKS -->
<section class="catalog-section">
    <div class="container">
        <span class="gold-line"></span>
        <h2 class="section-title">Explore Our World</h2>
        <p class="section-subtitle">From your dream gown to capturing your perfect moment</p>
        <div class="quick-links">
           <a href="/gowns.php" class="quick-card">
                <div class="quick-card-img">
                    <img src="/uploads/appointment.jpg" alt="Gown">
                </div>
                <div class="quick-card-body">
                    <h3>Gown Catalog</h3>
                    <p>Browse our curated collection of bridal, bridesmaids, mothers, and prom gowns from top designers.</p>
                    <span class="quick-card-arrow">Browse Collection →</span>
                </div>
            </a>

            <a href="/appointment.php" class="quick-card">
                <div class="quick-card-img">
                    <img src="/uploads/book.jpg" alt="Book Appointment">
                </div>
                <div class="quick-card-body">
                    <h3>Book Appointment</h3>
                    <p>Schedule your private, one-on-one session with one of our expert stylists at a time that suits you.</p>
                    <span class="quick-card-arrow">Book Now →</span>
                </div>
            </a>

            <a href="/our-brides.php" class="quick-card">
                <div class="quick-card-img">
                        <img src="/uploads/last.jpg" alt="Gown">
                </div>
                <div class="quick-card-body">
                    <h3>Our Brides</h3>
                    <p>See real brides wearing their Allen and Rej gowns on their special day — and share your own photo!</p>
                    <span class="quick-card-arrow">View Gallery →</span>
                </div>
            </a>

    </div>
</section>

<!-- ABOUT SNIPPET -->
<section style="background:var(--dark);padding:80px 0;">
    <div class="container" style="text-align:center;">
        <span style="font-family:'Cormorant Garamond',serif;font-size:clamp(28px,4vw,52px);font-weight:300;color:var(--white);display:block;margin-bottom:20px;">
            A Gown As Unique As You Are
        </span>
        <p style="color:#aaa;max-width:580px;margin:0 auto 36px;font-size:16px;line-height:1.8;">
            For over three decades, Allen and Rej Bridal has been the trusted destination for brides and formal shoppers throughout Central New York. Our expert stylists are dedicated to making every visit memorable.
        </p>
        <a href="/about.php" class="btn btn-outline">Our Story</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
