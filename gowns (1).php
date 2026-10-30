<?php
$pageTitle = 'Gown Catalog';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';

$cat = trim($_GET['cat'] ?? '');

// Fetch gowns — use LIKE with % on both sides as fallback for any charset edge case
if ($cat !== '') {
    $stmt = $conn->prepare("SELECT * FROM gowns WHERE TRIM(category) = TRIM(?) ORDER BY designer, name");
    $stmt->bind_param("s", $cat);
    $stmt->execute();
    $gowns = $stmt->get_result();
} else {
    $gowns = $conn->query("SELECT * FROM gowns ORDER BY category, designer, name");
}

$categories = ['Wedding / Bridesmaid','Kids / Flower Girl','JS Prom','Debut / Birthday','Barong / Coat'];
?>

<div class="page-hero">
    <h1><?= $cat ?: 'All Gowns' ?></h1>
    <p>Browse our curated collection from top designers</p>
</div>

<section style="background:var(--cream);padding:48px 0;">
    <div class="container">
        <div class="catalog-layout">

            <!-- Sidebar Filters -->
            <aside>
                <div class="catalog-filters">
                    <h3 style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:400;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--border);">
                        Filter Gowns
                    </h3>

                    <div class="filter-section">
                            <h4>Category</h4>
                            <div>
                                <?php foreach ($categories as $c): ?>
                                <a href="gowns.php?cat=<?= rawurlencode($c) ?>"
                                   class="filter-option<?= ($cat === $c) ? ' active' : '' ?>"
                                   style="display:block;text-decoration:none;padding:6px 0;color:<?= ($cat === $c) ? 'var(--gold)' : 'inherit' ?>;font-weight:<?= ($cat === $c) ? '600' : '400' ?>;">
                                    <?= ($cat === $c) ? '● ' : '○ ' ?><?= htmlspecialchars($c) ?>
                                </a>
                                <?php endforeach; ?>
                                <a href="gowns.php"
                                   class="filter-option<?= ($cat === '') ? ' active' : '' ?>"
                                   style="display:block;text-decoration:none;padding:6px 0;color:<?= ($cat === '') ? 'var(--gold)' : 'inherit' ?>;font-weight:<?= ($cat === '') ? '600' : '400' ?>;">
                                    <?= ($cat === '') ? '● ' : '○ ' ?>All Categories
                                </a>
                            </div>
                        </div>
                        <a href="gowns.php" style="display:block;text-align:center;color:var(--gray);font-size:13px;margin-top:16px;text-decoration:none;">Clear Filter</a>
                </div>
            </aside>

            <!-- Gown Grid -->
            <main>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;">
                    <h2 style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:300;">
                        <?= $cat ?: 'Gowns' ?>
                    </h2>
                    <span style="color:var(--gray);font-size:14px;"><?= $gowns->num_rows ?> gowns found</span>
                </div>

                <?php if ($gowns->num_rows === 0): ?>
                    <div style="text-align:center;padding:60px;color:var(--gray);">
                        <div style="font-size:56px;margin-bottom:16px;">👗</div>
                        <p>No gowns found with current filters.</p>
                        <a href="gowns.php" class="btn btn-outline" style="margin-top:20px;">Clear Filters</a>
                    </div>
                <?php else: ?>
                <div class="gown-grid">
                    <?php while ($gown = $gowns->fetch_assoc()): ?>
                    <div class="gown-card">
                        <div class="gown-img">
                            <?php if ($gown['image_path']): ?>
                                <img src="/<?= htmlspecialchars($gown['image_path']) ?>" alt="<?= htmlspecialchars($gown['name']) ?>">
                            <?php else: ?>
                                <span>👗</span>
                            <?php endif; ?>
                        </div>
                        <div class="gown-info">
                            <div class="gown-designer"><?= htmlspecialchars($gown['designer']) ?></div>
                            <div class="gown-name"><?= htmlspecialchars($gown['name']) ?></div>
                            <div class="gown-tags">
                                <?php if ($gown['silhouette']): ?><span class="tag"><?= htmlspecialchars($gown['silhouette']) ?></span><?php endif; ?>
                                <?php if ($gown['sleeve_type']): ?><span class="tag"><?= htmlspecialchars($gown['sleeve_type']) ?></span><?php endif; ?>
                                <?php if (!empty($gown['color'])): ?><span class="tag"><?= htmlspecialchars($gown['color']) ?></span><?php endif; ?>
                                <?php if ($gown['in_store']): ?><span class="in-store-badge">✓ In Store</span><?php endif; ?>
                            </div>
                            <?php if (!empty($gown['available_sizes'])): ?>
                            <div style="font-size:12px;color:var(--gray);margin-top:6px;"><strong style="color:var(--dark);">Sizes:</strong> <?= htmlspecialchars($gown['available_sizes']) ?></div>
                            <?php endif; ?>
                            <?php if ($gown['price']): ?>
							<div class="gown-price" style="margin-top:8px;">₱<?= number_format($gown['price'], 0) ?></div>
                            <?php endif; ?>
                            <?php if ($gown['description']): ?>
                            <p style="font-size:13px;color:var(--gray);margin-top:8px;line-height:1.6;"><?= htmlspecialchars($gown['description']) ?></p>
                            <?php endif; ?>
                            <a href="appointment.php?gown_id=<?= $gown['id'] ?>&gown_name=<?= urlencode($gown['name'].' by '.$gown['designer']) ?>" class="btn btn-gold btn-sm" style="margin-top:14px;display:block;text-align:center;">👗 Book a Try-On</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>



<?php require_once __DIR__ . '/includes/footer.php'; ?>