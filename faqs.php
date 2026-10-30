<?php
require_once __DIR__ . '/includes/admin_header_pre.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_faq'])) {
        $q = $conn->real_escape_string(trim($_POST['question'] ?? ''));
        $a = $conn->real_escape_string(trim($_POST['answer'] ?? ''));
        $o = (int)($_POST['display_order'] ?? 0);
        if ($q && $a) $conn->query("INSERT INTO faqs (question,answer,display_order) VALUES ('$q','$a',$o)");
    }
    if (isset($_POST['delete_faq'])) {
        $id = (int)$_POST['faq_id'];
        $conn->query("DELETE FROM faqs WHERE id=$id");
    }
    header('Location: faqs.php'); exit;
}

$adminPageTitle = 'FAQs';
require_once __DIR__ . '/includes/admin_header_output.php';

$faqs = $conn->query("SELECT * FROM faqs ORDER BY display_order, id");
?>
<div class="admin-header"><h1>FAQ Management</h1></div>
<div class="admin-table-wrap" style="padding:28px;margin-bottom:24px;">
    <h3 style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:400;margin-bottom:20px;">Add New FAQ</h3>
    <form method="POST">
        <div class="form-group" style="margin-bottom:14px;"><label>Question</label><input type="text" name="question" required style="border:1px solid var(--border);border-radius:4px;padding:8px 12px;width:100%;font-family:var(--font-body);"></div>
        <div class="form-group" style="margin-bottom:14px;"><label>Answer</label><textarea name="answer" required style="border:1px solid var(--border);border-radius:4px;padding:8px 12px;width:100%;font-family:var(--font-body);min-height:80px;"></textarea></div>
        <div class="form-group" style="margin-bottom:20px;"><label>Display Order</label><input type="number" name="display_order" value="0" style="border:1px solid var(--border);border-radius:4px;padding:8px 12px;width:80px;font-family:var(--font-body);"></div>
        <button type="submit" name="add_faq" class="btn btn-gold btn-sm">Add FAQ</button>
    </form>
</div>
<div class="admin-table-wrap">
    <div class="admin-table-head"><h3>All FAQs</h3></div>
    <table>
        <thead><tr><th>Order</th><th>Question</th><th>Answer</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($f = $faqs->fetch_assoc()): ?>
        <tr>
            <td><?= $f['display_order'] ?></td>
            <td><strong><?= htmlspecialchars($f['question']) ?></strong></td>
            <td style="font-size:13px;color:var(--gray);max-width:300px;"><?= htmlspecialchars(substr($f['answer'],0,100)) ?>…</td>
            <td>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this FAQ?')">
                    <input type="hidden" name="faq_id" value="<?= $f['id'] ?>">
                    <button type="submit" name="delete_faq" class="btn-xs btn-delete">Delete</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
