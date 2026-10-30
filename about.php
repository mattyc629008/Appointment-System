<?php
$pageTitle = 'About Us';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
$faqs = $conn->query("SELECT * FROM faqs ORDER BY display_order");
?>

<div class="page-hero">
    <h1>About Allen and Rej Bridal</h1>
    <p>Over 30 years of helping you find your dream gown</p>
</div>

<!-- About Section -->
<section style="background:var(--white);padding:80px 0;">
    <div class="container">
        <div class="about-grid">
              <div class="about-img">
                <img src="/uploads/story.jpg" alt="Gown">
            </div>
            <div class="about-text">
                <span class="gold-line"></span>
                <h2>Our Story</h2>
                <P>The journey of Allen and Rej Bridal Boutique Shop began with the passion of our founder, Annaliza Venturina Siblag. After years of refining her skills in tailoring and design, Annaliza transitioned her expertise into a personal mission: creating a boutique where every client feels truly special. Through careful planning and a deep commitment to her craft, she turned her dream of business ownership into a reality.</P>          
                <p>Our doors first opened on September 1, 2016, at Dragon 8 Mall in Tondo, Manila. We quickly became a trusted name for those seeking quality and elegance, built on a foundation of exceptional customer service and technical skill. This success allowed us to grow alongside our community, ensuring that every visitor finds the perfect fit for their most important celebrations.</p>
                <p>In January 2023, we proudly expanded with a second branch, Rej and Allen Bridal Boutique Shop, in the same location. Today, we remain dedicated to the values that started it all—hard work, craftsmanship, and a love for helping you celebrate life’s beautiful milestones. We are honored to be a part of your story.</p>  

                    
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Allen and Rej -->
<section class="why-section">
    <div class="container">
        <div class="why-header">
            <span class="gold-line why-gold-line"></span>
            <h2 class="section-title">Why Choose Allen and Rej?</h2>
            <p class="section-subtitle">Everything we do is designed around you</p>
        </div>

        <div class="why-grid">
            <?php 
            $features = [
                ['','Private Appointments','Every session is one-on-one with a dedicated stylist — no crowds, no rushing.'],
                ['','9+ Years Experience','Decades of expertise in bridal fashion means you\'re in the most capable hands.'],
                ['','Flexible Scheduling','Book 1-hour or 2-hour appointments at times that work for you and your party.'],
            ];

            foreach ($features as $f): ?>
                <div class="why-card">
                    <div class="why-icon"><?= $f[0] ?></div>
                    <h4 class="why-title"><?= $f[1] ?></h4>
                    <p class="why-desc"><?= $f[2] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FAQ -->
<section id="faq" style="background:var(--white);padding:80px 0;">
    <div class="container" style="max-width:820px;">
        <span class="gold-line"></span>
        <h2 class="section-title">Frequently Asked Questions</h2>
        <p class="section-subtitle">Everything you need to know before your visit</p>

        <?php while ($faq = $faqs->fetch_assoc()): ?>
            <div class="faq-item">
                <div class="faq-question">
                    <?= htmlspecialchars($faq['question']) ?>
                    <span>+</span>
                </div>
                <div class="faq-answer">
                    <?= htmlspecialchars($faq['answer']) ?>
                </div>
            </div>
        <?php endwhile; ?>

        <div style="text-align:center;margin-top:48px;">
            <p style="color:var(--gray);margin-bottom:20px;">Have more questions? We'd love to hear from you.</p>
            <a href="/contact.php" class="btn btn-outline">Contact Us</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>