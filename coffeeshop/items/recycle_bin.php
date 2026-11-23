<?php
$page = 'item'; $title = 'Recycle bin';
require_once '../dbconfig.php';
require_login();

$stmt = $pdo->query("
    SELECT m.*, c.category_name, du.username AS deleted_by_name
    FROM menu_items m
    JOIN categories c ON c.category_id = m.category_id
    LEFT JOIN users du ON du.user_id = m.deleted_by
    WHERE m.deleted_at IS NOT NULL
    ORDER BY m.deleted_at DESC
");
$items = $stmt->fetchAll();
require '../includes/header.php';
?>
<div class="head">
  <div><h1>Recycle bin</h1><p>Deleted menu items. Each row shows who deleted it and when.</p></div>
  <div><a class="btn ghost" href="list.php">Back to menu items</a></div>
</div>
<div class="panel">
<?php if (!$items): ?>
  <div class="empty">The recycle bin is empty.</div>
<?php else: ?>
<table>
  <tr><th></th><th>Item</th><th>Category</th><th class="num">Price</th><th>Deleted by</th><th>Deleted on</th><th></th></tr>
  <?php foreach ($items as $i): ?>
  <tr>
    <td><?php if ($i['image_path']): ?><img class="thumb" src="<?= UPLOAD_URL . e($i['image_path']) ?>" alt=""><?php endif; ?></td>
    <td><strong><?= e($i['item_name']) ?></strong></td>
    <td><span class="badge"><?= e($i['category_name']) ?></span></td>
    <td class="num price">&#8369;<?= number_format($i['price'],2) ?></td>
    <td class="meta"><?= e($i['deleted_by_name'] ?? 'Unknown user') ?></td>
    <td class="meta"><?= e(date('M j, Y g:i A', strtotime($i['deleted_at']))) ?></td>
    <td class="act">
      <form method="post" action="restore.php" style="display:inline">
        <input type="hidden" name="item_id" value="<?= $i['item_id'] ?>">
        <button class="btn sm" type="submit">Restore</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>
</div>
<?php require '../includes/footer.php'; ?>
