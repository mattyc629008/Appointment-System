<?php
$pageTitle = 'Contact Us';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-hero">
    <h1>Contact Us</h1>
    <p>We'd love to hear from you</p>
</div>

<section style="background:var(--cream);padding:60px 0;">
    <div class="container">
        <div style="max-width:760px;margin:0 auto;">
            <span class="gold-line"></span>
            <h2 style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:300;margin-bottom:32px;">Get In Touch</h2>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-bottom:24px;">
                <!-- Address -->
                <div style="background:var(--white);border-radius:10px;box-shadow:var(--shadow);padding:24px;">
                    <div style="width:44px;height:44px;background:var(--gold-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
                        <i class="fas fa-map-marker-alt" style="color:var(--gold);font-size:18px;"></i>
                    </div>
                    <h4 style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:400;margin-bottom:8px;">Visit Us</h4>
                    <p style="font-size:14px;color:var(--gray);line-height:1.7;">Pasilio 2k-7, 2nd Floor<br>Dragon 8 Mall, C.M. Recto St.<br>Tondo, Manila</p>
                </div>

                <!-- Phone -->
                <div style="background:var(--white);border-radius:10px;box-shadow:var(--shadow);padding:24px;">
                    <div style="width:44px;height:44px;background:var(--gold-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
                        <i class="fas fa-phone" style="color:var(--gold);font-size:18px;"></i>
                    </div>
                    <h4 style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:400;margin-bottom:8px;">Call / Text Us</h4>
                    <a href="tel:09173872470" style="font-size:20px;font-weight:600;color:var(--gold);text-decoration:none;">0917 387 2470</a>
                    <p style="font-size:13px;color:var(--gray);margin-top:4px;">Available during store hours</p>
                </div>

                <!-- Facebook -->
                <div style="background:var(--white);border-radius:10px;box-shadow:var(--shadow);padding:24px;">
                    <div style="width:44px;height:44px;background:var(--gold-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
                        <i class="fab fa-facebook" style="color:var(--gold);font-size:20px;"></i>
                    </div>
                    <h4 style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:400;margin-bottom:8px;">Facebook Page</h4>
                    <p style="font-size:14px;color:var(--gray);margin-bottom:12px;">Message us on Facebook for inquiries</p>
                    <a href="https://www.facebook.com/AlennAndRejBridalShop" target="_blank" rel="noopener"
                       class="btn btn-gold btn-sm" style="display:inline-flex;align-items:center;gap:8px;">
                        <i class="fab fa-facebook"></i> Visit our Page
                    </a>
                </div>

                <!-- Hours -->
                <div style="background:var(--white);border-radius:10px;box-shadow:var(--shadow);padding:24px;">
                    <div style="width:44px;height:44px;background:var(--gold-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
                        <i class="fas fa-clock" style="color:var(--gold);font-size:18px;"></i>
                    </div>
                    <h4 style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:400;margin-bottom:8px;">Store Hours</h4>
                    <p style="font-size:15px;color:var(--dark);font-weight:600;">Monday – Sunday</p>
                    <p style="font-size:15px;color:var(--gold);font-weight:600;">8:00 AM – 6:00 PM</p>
                    <p style="font-size:12px;color:var(--gray);margin-top:4px;">Open every day including holidays</p>
                </div>
            </div>

            <div style="text-align:center;margin-top:16px;">
                <a href="/appointment.php" class="btn btn-gold" style="padding:14px 48px;">
                    <i class="fas fa-calendar-alt" style="margin-right:8px;"></i>Book an Appointment
                </a>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
