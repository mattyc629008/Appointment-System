<?php // includes/footer.php ?>
<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="footer-logo">Alenn and Rej</div>
                <p>A gown as unique as you are. Serving brides and formal shoppers in Tondo, Manila.</p>
                <div class="footer-contact">
                    <i class="fas fa-phone"></i>
                    <a href="tel:09173872470">0917 387 2470</a>
                </div>
                <div class="footer-contact">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Dragon 8 Mall, Tondo, Manila</span>
                </div>
                <div class="social-links" style="margin-top:20px;">
                    <a href="https://www.facebook.com/AlennAndRejBridalShop" target="_blank" rel="noopener" title="Follow us on Facebook">
                        <i class="fab fa-facebook" style="font-size:28px;"></i>
                    </a>
                </div>
            </div>
            <div class="footer-col">
                <h5>Shop</h5>
                <a href="/gowns.php?cat=Wedding+%2F+Bridesmaid">Wedding / Bridesmaid</a>
                <a href="/gowns.php?cat=Kids+%2F+Flower+Girl">Kids / Flower Girl</a>
                <a href="/gowns.php?cat=JS+Prom">JS Prom</a>
                <a href="/gowns.php?cat=Debut+%2F+Birthday">Debut / Birthday</a>
                <a href="/gowns.php?cat=Barong+%2F+Coat">Barong / Coat</a>
            </div>
            <div class="footer-col">
                <h5>Visit</h5>
                <a href="/about.php">About Us</a>
                <a href="/appointment.php">Book Appointment</a>
                <a href="/contact.php">Contact Us</a>
                <a href="/about.php#faq">FAQ</a>
            </div>
            <div class="footer-col">
                <h5>Hours</h5>
                <div class="footer-hours">
                    <div class="day"><span>Monday</span><span>8:00 AM – 6:00 PM</span></div>
                    <div class="day"><span>Tuesday</span><span>8:00 AM – 6:00 PM</span></div>
                    <div class="day"><span>Wednesday</span><span>8:00 AM – 6:00 PM</span></div>
                    <div class="day"><span>Thursday</span><span>8:00 AM – 6:00 PM</span></div>
                    <div class="day"><span>Friday</span><span>8:00 AM – 6:00 PM</span></div>
                    <div class="day"><span>Saturday</span><span>8:00 AM – 6:00 PM</span></div>
                    <div class="day"><span>Sunday</span><span>8:00 AM – 6:00 PM</span></div>


                </div>
                
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> Alenn and Rej Bridal. All rights reserved.</span>
            <div>
                <a href="/admin/admin-login.php" style="color:#555;font-size:12px;">Admin</a>
            </div>
        </div>
    </div>
</footer>
<script src="/js/main.js"></script>
<?php if (!isset($_SESSION['admin_id'])) require_once __DIR__ . '/chat_widget.php'; ?>

</body>
</html>
