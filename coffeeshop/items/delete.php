<?php
$page = 'item'; $title = 'Delete menu item';
require_once '../dbconfig.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['item_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT m.*, c.category_name
    FROM menu_items m JOIN categories c ON c.category_id = m.category_id
    WHERE m.item_id = :id AND m.deleted_at IS NULL");
$stmt->execute([':id' => $id]);
$item = $stmt->fetch();
if (!$item) { flash('That menu item does not exist.', 'err'); redirect('/items/list.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Soft delete: keep the row, just stamp who deleted it and when.
    // The item disappears from the normal list but can be restored from the recycle bin.
    $del = $pdo->prepare("UPDATE menu_items SET deleted_at = NOW(), deleted_by = :u WHERE item_id = :id");
    $del->execute([':u' => current_user_id(), ':id' => $id]);
    flash("Menu item \"{$item['item_name']}\" moved to the recycle bin.");
    redirect('/items/list.php');
}
require '../includes/header.php';
?>
<div class="head"><div><h1>Delete menu item</h1></div></div>
<form class="card" method="post">
  <input type="hidden" name="item_id" value="<?= $id ?>">
  <p>You are about to delete <strong><?= e($item['item_name']) ?></strong> (<?= e($item['size']) ?>, &#8369;<?= number_format($item['price'],2) ?>) from <strong><?= e($item['category_name']) ?></strong>.</p>
  <p class="meta">This moves the item to the recycle bin — it can be restored later from <a href="recycle_bin.php">Recycle bin</a>.</p>
  <div class="actions"><button class="btn danger" type="submit">Delete menu item</button><a class="btn ghost" href="list.php">Cancel</a></div>
</form>
<?php require '../includes/footer.php'; ?>
