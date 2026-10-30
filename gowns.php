<?php
require_once __DIR__ . '/includes/admin_header_pre.php';

$success = $error = '';

// Delete - redirect BEFORE output
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $g = $conn->query("SELECT image_path FROM gowns WHERE id=$id")->fetch_assoc();
    if ($g && $g['image_path']) {
        $fp = dirname(__DIR__) . DIRECTORY_SEPARATOR . $g['image_path'];
        if (file_exists($fp)) unlink($fp);
    }
    $conn->query("DELETE FROM gowns WHERE id=$id");
    header('Location: gowns.php'); exit;
}

// Edit gown (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_gown'])) {
    if (!csrf_verify()) { $error = 'Invalid request.'; } else {
    $id         = (int)($_POST['edit_id'] ?? 0);
    $name       = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $designer   = $conn->real_escape_string(trim($_POST['designer'] ?? ''));
    $category   = $conn->real_escape_string($_POST['category'] ?? '');
    $silhouette = $conn->real_escape_string(trim($_POST['silhouette'] ?? ''));
    $sleeve     = $conn->real_escape_string(trim($_POST['sleeve_type'] ?? ''));
    $sizes      = $conn->real_escape_string(trim($_POST['available_sizes'] ?? ''));
    $color      = $conn->real_escape_string(trim($_POST['color'] ?? ''));
    $price      = (float)($_POST['price'] ?? 0);
    $in_store   = isset($_POST['in_store']) ? 1 : 0;
    $desc       = $conn->real_escape_string(trim($_POST['description'] ?? ''));

    $existing   = $conn->query("SELECT image_path FROM gowns WHERE id=$id")->fetch_assoc();
    $image_path = $existing['image_path'] ?? '';

    if (!empty($_FILES['edit_gown_image']['name'])) {
        $allowed = ['image/jpeg','image/png','image/webp'];
        $ftype   = mime_content_type($_FILES['edit_gown_image']['tmp_name']);
        if (!in_array($ftype, $allowed)) {
            $error = 'Image must be JPG, PNG or WEBP.';
        } elseif ($_FILES['edit_gown_image']['size'] > 5*1024*1024) {
            $error = 'Image must be under 5MB.';
        } else {
            $ext      = pathinfo($_FILES['edit_gown_image']['name'], PATHINFO_EXTENSION);
            $filename = 'gown_' . time() . '_' . rand(100,999) . '.' . $ext;
            $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'gowns' . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($_FILES['edit_gown_image']['tmp_name'], $uploadDir . $filename)) {
                if ($image_path) {
                    $old_fp = dirname(__DIR__) . DIRECTORY_SEPARATOR . $image_path;
                    if (file_exists($old_fp)) unlink($old_fp);
                }
                $image_path = $conn->real_escape_string('uploads/gowns/' . $filename);
            } else {
                $error = 'Failed to upload image.';
            }
        }
    }

    if (!$error && $id) {
        $stmt = $conn->prepare("UPDATE gowns SET name=?,designer=?,category=?,silhouette=?,sleeve_type=?,available_sizes=?,color=?,price=?,in_store=?,description=?,image_path=? WHERE id=?");
        $stmt->bind_param("sssssssdissi", $name, $designer, $category, $silhouette, $sleeve, $sizes, $color, $price, $in_store, $desc, $image_path, $id);
        $stmt->execute();
        $success = 'Gown updated successfully.';
    }
    } // end csrf check
}

// Add gown (POST - no redirect, show success inline)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_gown'])) {
    if (!csrf_verify()) { $error = 'Invalid request.'; } else {
    $name       = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $designer   = $conn->real_escape_string(trim($_POST['designer'] ?? ''));
    $category   = $conn->real_escape_string($_POST['category'] ?? '');
    $silhouette = $conn->real_escape_string(trim($_POST['silhouette'] ?? ''));
    $sleeve     = $conn->real_escape_string(trim($_POST['sleeve_type'] ?? ''));
    $sizes      = $conn->real_escape_string(trim($_POST['available_sizes'] ?? ''));
    $color      = $conn->real_escape_string(trim($_POST['color'] ?? ''));
    $price      = (float)($_POST['price'] ?? 0);
    $in_store   = isset($_POST['in_store']) ? 1 : 0;
    $desc       = $conn->real_escape_string(trim($_POST['description'] ?? ''));
    $image_path = '';

    if (!empty($_FILES['gown_image']['name'])) {
        $allowed = ['image/jpeg','image/png','image/webp'];
        $ftype   = mime_content_type($_FILES['gown_image']['tmp_name']);
        if (!in_array($ftype, $allowed)) {
            $error = 'Image must be JPG, PNG or WEBP.';
        } elseif ($_FILES['gown_image']['size'] > 5*1024*1024) {
            $error = 'Image must be under 5MB.';
        } else {
            $ext      = pathinfo($_FILES['gown_image']['name'], PATHINFO_EXTENSION);
            $filename = 'gown_' . time() . '_' . rand(100,999) . '.' . $ext;
            $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'gowns' . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($_FILES['gown_image']['tmp_name'], $uploadDir . $filename)) {
                $image_path = $conn->real_escape_string('uploads/gowns/' . $filename);
            } else {
                $error = 'Failed to upload image.';
            }
        }
    }

    if (!$error) {
        // SECURITY: Prepared statement — no SQL injection possible
        $stmt = $conn->prepare("INSERT INTO gowns (name,designer,category,silhouette,sleeve_type,available_sizes,color,price,in_store,description,image_path) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssdis", $name, $designer, $category, $silhouette, $sleeve, $sizes, $color, $price, $in_store, $desc, $image_path);
        $stmt->execute();
        $success = 'Gown added successfully.';
    }
    } // end csrf check
}
$adminPageTitle = 'Gown Catalog';
require_once __DIR__ . '/includes/admin_header_output.php';

$gowns = $conn->query("SELECT * FROM gowns ORDER BY category, name");
?>
<div class="admin-header">
    <h1>Gown Catalog</h1>
    <button class="btn btn-gold btn-sm" id="toggleAddGownBtn">+ Add Gown</button>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div id="addGownForm" style="display:<?= ($success||$error) ? 'block' : 'none' ?>;margin-bottom:28px;">
    <div class="admin-table-wrap" style="padding:28px;">
        <h3 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:400;margin-bottom:20px;">Add New Gown</h3>
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:18px;">
                <div class="form-group"><label>Gown Name *</label><input type="text" name="name" required></div>
                <div class="form-group"><label>Designer *</label><input type="text" name="designer" required></div>
                <div class="form-group"><label>Category *</label>
                    <select name="category" required>
                        <option value="Wedding / Bridesmaid">Wedding / Bridesmaid</option>
                        <option value="Kids / Flower Girl">Kids / Flower Girl</option>
                        <option value="JS Prom">JS Prom</option>
                        <option value="Debut / Birthday">Debut / Birthday</option>
                        <option value="Barong / Coat">Barong / Coat</option>
                    </select>
                </div>
                <div class="form-group"><label>Silhouette</label><input type="text" name="silhouette" placeholder="A-Line, Ball Gown, Mermaid…"></div>
                <div class="form-group"><label>Sleeve Type</label><input type="text" name="sleeve_type" placeholder="Sleeveless, Long Sleeve…"></div>
                <div class="form-group"><label>Color</label><input type="text" name="color" placeholder="Ivory, White, Blush…"></div>
                <div class="form-group"><label>Available Sizes</label><input type="text" name="available_sizes" placeholder="e.g. 2, 4, 6, 8, 10, 12"></div>
                <div class="form-group"><label>Price (₱)</label><input type="number" name="price" step="0.01" min="0" placeholder="0.00"></div>
                <div class="form-group" style="align-self:end;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:14px;"><input type="checkbox" name="in_store" value="1" checked> In Store</label>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:18px;">
                <label>Description</label>
                <textarea name="description" style="border:1px solid var(--border);border-radius:4px;padding:10px;width:100%;font-family:var(--font-body);font-size:14px;min-height:80px;" placeholder="Describe the gown — neckline, fabric, details…"></textarea>
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <label>Gown Photo</label>
                <div style="border:2px dashed var(--border);border-radius:8px;padding:20px;text-align:center;background:#fafafa;cursor:pointer;" id="uploadDropZone">
                    <img id="imgPreview" src="" style="display:none;width:180px;height:240px;object-fit:cover;border-radius:6px;margin:0 auto 12px;">
                    <div id="uploadPlaceholder"><div style="font-size:32px;margin-bottom:8px;">📷</div><div style="font-size:14px;color:var(--gray);">Click to upload gown photo</div><div style="font-size:12px;color:var(--gray);margin-top:4px;">JPG, PNG or WEBP · Max 5MB · Displays at 3:4 ratio</div></div>
                </div>
                <input type="file" id="gownImageInput" name="gown_image" accept="image/jpeg,image/png,image/webp" style="display:none;">
            </div>
            <button type="submit" name="add_gown" class="btn btn-gold">Add to Catalog</button>
        </form>
    </div>
</div>
<script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>">
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById('imgPreview');
            img.src = e.target.result; img.style.display = 'block';
            document.getElementById('uploadPlaceholder').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Toggle Add Gown form
    var toggleBtn = document.getElementById('toggleAddGownBtn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            var f = document.getElementById('addGownForm');
            f.style.display = f.style.display === 'none' ? 'block' : 'none';
        });
    }

    // Upload drop zone click
    var dropZone = document.getElementById('uploadDropZone');
    if (dropZone) {
        dropZone.addEventListener('click', function() {
            document.getElementById('gownImageInput').click();
        });
    }

    // Image file input change
    var fileInput = document.getElementById('gownImageInput');
    if (fileInput) {
        fileInput.addEventListener('change', function() { previewImage(this); });
    }

    // Delete confirmation — event delegation
    document.querySelectorAll('.gown-delete-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (!confirm('Delete this gown?')) e.preventDefault();
        });
    });
});
</script>

<div class="admin-table-wrap">
    <div class="admin-table-head"><h3>All Gowns</h3><span style="color:var(--gray);font-size:14px;"><?= $gowns->num_rows ?> items</span></div>
    <table>
        <thead><tr><th>Photo</th><th>Name / Designer</th><th>Category</th><th>Silhouette</th><th>Sizes</th><th>Color</th><th>Price</th><th>In Store</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($g = $gowns->fetch_assoc()): ?>
        <tr>
            <td style="vertical-align:middle;"><?php if ($g['image_path']): ?><img src="/<?= htmlspecialchars($g['image_path']) ?>" style="width:52px;height:68px;object-fit:cover;border-radius:4px;display:block;"><?php else: ?><div style="width:52px;height:68px;background:var(--gray-light);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:20px;">👗</div><?php endif; ?></td>
            <td style="vertical-align:middle;"><strong><?= htmlspecialchars($g['name']) ?></strong><div style="font-size:12px;color:var(--gray);"><?= htmlspecialchars($g['designer']) ?></div></td>
            <td style="vertical-align:middle;"><?= htmlspecialchars($g['category']) ?></td>
            <td style="font-size:13px;vertical-align:middle;"><?= htmlspecialchars($g['silhouette'] ?? '—') ?></td>
            <td style="font-size:12px;color:var(--gray);vertical-align:middle;"><?= htmlspecialchars($g['available_sizes'] ?? '—') ?></td>
            <td style="font-size:13px;vertical-align:middle;"><?= htmlspecialchars($g['color'] ?? '—') ?></td>
            <td style="vertical-align:middle;"><?= $g['price'] ? '₱'.number_format($g['price'],0) : '—' ?></td>
            <td style="vertical-align:middle;"><?= $g['in_store'] ? '<span class="in-store-badge">✓ Yes</span>' : '<span style="color:var(--gray);">No</span>' ?></td>
            <td style="white-space:nowrap;vertical-align:middle;"><div style="display:inline-flex;gap:6px;align-items:center;">
                <button class="btn-xs btn-edit gown-edit-btn"
                    data-id="<?= $g['id'] ?>"
                    data-name="<?= htmlspecialchars($g['name'], ENT_QUOTES) ?>"
                    data-designer="<?= htmlspecialchars($g['designer'], ENT_QUOTES) ?>"
                    data-category="<?= htmlspecialchars($g['category'], ENT_QUOTES) ?>"
                    data-silhouette="<?= htmlspecialchars($g['silhouette'] ?? '', ENT_QUOTES) ?>"
                    data-sleeve="<?= htmlspecialchars($g['sleeve_type'] ?? '', ENT_QUOTES) ?>"
                    data-sizes="<?= htmlspecialchars($g['available_sizes'] ?? '', ENT_QUOTES) ?>"
                    data-color="<?= htmlspecialchars($g['color'] ?? '', ENT_QUOTES) ?>"
                    data-price="<?= $g['price'] ?>"
                    data-instore="<?= $g['in_store'] ?>"
                    data-desc="<?= htmlspecialchars($g['description'] ?? '', ENT_QUOTES) ?>"
                    data-img="<?= htmlspecialchars($g['image_path'] ?? '', ENT_QUOTES) ?>">Edit</button>
                <a href="?delete=<?= $g['id'] ?>" class="btn-xs btn-delete gown-delete-link">Delete</a>
            </div></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<style>.btn-edit{background:#4a7fa5;color:#fff;border:none;cursor:pointer;}.btn-edit:hover{background:#3a6f95;}</style>
<!-- ===== EDIT GOWN MODAL ===== -->
<div id="editGownOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;overflow-y:auto;">
    <div style="background:#fff;max-width:780px;margin:48px auto;border-radius:10px;padding:36px;position:relative;">
        <button id="editModalClose" type="button" style="position:absolute;top:14px;right:18px;background:none;border:none;font-size:22px;cursor:pointer;color:var(--gray);">&times;</button>
        <h3 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:400;margin-bottom:24px;">Edit Gown</h3>
        <form method="POST" enctype="multipart/form-data" id="editGownForm">
            <?= csrf_field() ?>
            <input type="hidden" name="edit_id" id="edit_id">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:18px;">
                <div class="form-group"><label>Gown Name *</label><input type="text" name="name" id="edit_name" required></div>
                <div class="form-group"><label>Designer *</label><input type="text" name="designer" id="edit_designer" required></div>
                <div class="form-group"><label>Category *</label>
                    <select name="category" id="edit_category" required>
                        <option value="Wedding / Bridesmaid">Wedding / Bridesmaid</option>
                        <option value="Kids / Flower Girl">Kids / Flower Girl</option>
                        <option value="JS Prom">JS Prom</option>
                        <option value="Debut / Birthday">Debut / Birthday</option>
                        <option value="Barong / Coat">Barong / Coat</option>
                    </select>
                </div>
                <div class="form-group"><label>Silhouette</label><input type="text" name="silhouette" id="edit_silhouette" placeholder="A-Line, Ball Gown, Mermaid..."></div>
                <div class="form-group"><label>Sleeve Type</label><input type="text" name="sleeve_type" id="edit_sleeve" placeholder="Sleeveless, Long Sleeve..."></div>
                <div class="form-group"><label>Color</label><input type="text" name="color" id="edit_color" placeholder="Ivory, White, Blush..."></div>
                <div class="form-group"><label>Available Sizes</label><input type="text" name="available_sizes" id="edit_sizes" placeholder="e.g. 2, 4, 6, 8, 10, 12"></div>
                <div class="form-group"><label>Price (₱)</label><input type="number" name="price" id="edit_price" step="0.01" min="0" placeholder="0.00"></div>
                <div class="form-group" style="align-self:end;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:14px;"><input type="checkbox" name="in_store" id="edit_instore" value="1"> In Store</label>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:18px;">
                <label>Description</label>
                <textarea name="description" id="edit_desc" style="border:1px solid var(--border);border-radius:4px;padding:10px;width:100%;font-family:var(--font-body);font-size:14px;min-height:80px;" placeholder="Describe the gown..."></textarea>
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <label>Gown Photo <span style="color:var(--gray);font-size:12px;">(leave empty to keep current)</span></label>
                <div style="display:flex;align-items:flex-start;gap:18px;">
                    <div id="editCurrentImg" style="display:none;flex-shrink:0;">
                        <img id="editCurrentImgEl" src="" style="width:80px;height:107px;object-fit:cover;border-radius:6px;border:1px solid var(--border);">
                        <div style="font-size:11px;color:var(--gray);text-align:center;margin-top:4px;">Current</div>
                    </div>
                    <div style="flex:1;">
                        <div style="border:2px dashed var(--border);border-radius:8px;padding:20px;text-align:center;background:#fafafa;cursor:pointer;" id="editUploadDropZone">
                            <img id="editImgPreview" src="" style="display:none;width:120px;height:160px;object-fit:cover;border-radius:6px;margin:0 auto 10px;">
                            <div id="editUploadPlaceholder"><div style="font-size:28px;margin-bottom:6px;">CAMERA</div><div style="font-size:13px;color:var(--gray);">Click to upload new photo</div><div style="font-size:11px;color:var(--gray);margin-top:3px;">JPG, PNG or WEBP - Max 5MB</div></div>
                        </div>
                        <input type="file" id="editGownImageInput" name="edit_gown_image" accept="image/jpeg,image/png,image/webp" style="display:none;">
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" name="edit_gown" class="btn btn-gold">Save Changes</button>
                <button type="button" id="editCancelBtn" class="btn" style="background:var(--gray-light);color:var(--text);">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script nonce="<?= defined('CSP_NONCE') ? CSP_NONCE : '' ?>">
(function () {
    var overlay   = document.getElementById('editGownOverlay');
    var closeBtn  = document.getElementById('editModalClose');
    var cancelBtn = document.getElementById('editCancelBtn');

    function closeModal() { overlay.style.display = 'none'; }

    if (closeBtn)  closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function(e) { if (e.target === overlay) closeModal(); });

    var editDropZone  = document.getElementById('editUploadDropZone');
    var editFileInput = document.getElementById('editGownImageInput');
    if (editDropZone) {
        editDropZone.addEventListener('click', function() { editFileInput.click(); });
    }
    if (editFileInput) {
        editFileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = document.getElementById('editImgPreview');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    document.getElementById('editUploadPlaceholder').style.display = 'none';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.gown-edit-btn');
        if (!btn) return;
        var d = btn.dataset;

        document.getElementById('edit_id').value         = d.id;
        document.getElementById('edit_name').value       = d.name;
        document.getElementById('edit_designer').value   = d.designer;
        document.getElementById('edit_silhouette').value = d.silhouette;
        document.getElementById('edit_sleeve').value     = d.sleeve;
        document.getElementById('edit_sizes').value      = d.sizes;
        document.getElementById('edit_color').value      = d.color;
        document.getElementById('edit_price').value      = d.price;
        document.getElementById('edit_instore').checked  = d.instore === '1';
        document.getElementById('edit_desc').value       = d.desc;

        var catSel = document.getElementById('edit_category');
        for (var i = 0; i < catSel.options.length; i++) {
            if (catSel.options[i].value === d.category) { catSel.selectedIndex = i; break; }
        }

        var curImgWrap = document.getElementById('editCurrentImg');
        var curImgEl   = document.getElementById('editCurrentImgEl');
        if (d.img) {
            curImgEl.src = '/' + d.img;
            curImgWrap.style.display = 'block';
        } else {
            curImgWrap.style.display = 'none';
        }

        var editPrev = document.getElementById('editImgPreview');
        editPrev.src = ''; editPrev.style.display = 'none';
        document.getElementById('editUploadPlaceholder').style.display = 'block';
        editFileInput.value = '';

        overlay.style.display = 'block';
        overlay.scrollTop = 0;
    });
}());
</script>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>