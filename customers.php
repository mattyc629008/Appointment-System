<?php
// admin/customers.php
require_once __DIR__ . '/includes/admin_header_pre.php';
$adminPageTitle = 'Customers';
require_once __DIR__ . '/includes/admin_header_output.php';
$customers = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM appointments WHERE customer_id=c.id) as appt_count FROM customers c ORDER BY c.created_at DESC");
?>
<div class="admin-header"><h1>Customers</h1></div>
<div class="admin-table-wrap">
    <div class="admin-table-head"><h3>All Registered Customers</h3><span style="color:var(--gray);font-size:14px;"><?= $customers->num_rows ?> records</span></div>
    <table>
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Appointments</th><th>Joined</th></tr></thead>
        <tbody>
        <?php while ($c = $customers->fetch_assoc()): ?>
        <tr>
            <td style="color:var(--gray);font-size:12px;">#<?= $c['id'] ?></td>
            <td><strong><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></strong></td>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
            <td style="text-align:center;"><strong><?= $c['appt_count'] ?></strong></td>
            <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
