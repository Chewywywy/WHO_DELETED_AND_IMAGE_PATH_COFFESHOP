<?php
$page = 'item'; $title = 'Menu items';
require '../includes/header.php';
require_login();

$filter = (int)($_GET['category'] ?? 0);
$q = trim($_GET['q'] ?? '');   // SEARCH keyword
$cats = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name")->fetchAll();

$sql = "SELECT m.*, c.category_name, au.username AS added_by_name, uu.username AS updated_by_name
        FROM menu_items m
        JOIN categories c ON c.category_id = m.category_id
        LEFT JOIN users au ON au.user_id = m.added_by
        LEFT JOIN users uu ON uu.user_id = m.last_updated_by";
$params = [];
$where = ["m.deleted_at IS NULL"]; // hide items that are in the recycle bin
if ($filter) { $where[] = "m.category_id = :c"; $params[':c'] = $filter; }
if ($q !== '') {
    // SEARCH: match the keyword against the item name, size or category name
    $where[] = "(m.item_name LIKE :q OR m.size LIKE :q2 OR c.category_name LIKE :q3)";
    $params[':q']  = '%' . $q . '%';
    $params[':q2'] = '%' . $q . '%';
    $params[':q3'] = '%' . $q . '%';
}
if ($where) { $sql .= " WHERE " . implode(" AND ", $where); }
$sql .= " ORDER BY c.category_name, m.item_name";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$items = $stmt->fetchAll();

$trash_count = (int)$pdo->query("SELECT COUNT(*) FROM menu_items WHERE deleted_at IS NOT NULL")->fetchColumn();
?>
<div class="head">
  <div><h1>Menu items</h1><p>The child records. Each item belongs to exactly one category.</p></div>
  <div class="filter">
    <form method="get" class="filter">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search menu items..." maxlength="100" aria-label="Search menu items">
      <select name="category" aria-label="Filter by category" data-autosubmit>
        <option value="0">All categories</option>
        <?php foreach ($cats as $c): ?>
        <option value="<?= $c['category_id'] ?>" <?= $filter==$c['category_id']?'selected':'' ?>><?= e($c['category_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn" type="submit">Search</button>
      <?php if ($q !== '' || $filter): ?><a class="btn ghost" href="list.php">Clear</a><?php endif; ?>
    </form>
    <a class="btn ghost" href="recycle_bin.php">Recycle bin<?php if ($trash_count): ?> (<?= $trash_count ?>)<?php endif; ?></a>
    <a class="btn" href="add.php">Add menu item</a>
  </div>
</div>
<div class="panel">
<?php if (!$items): ?><div class="empty"><?= $q !== '' ? 'No menu items match "' . e($q) . '".' : 'No menu items found. Add one to fill the menu.' ?></div><?php else: ?>
<table>
  <tr><th></th><th>ID</th><th>Item</th><th>Category</th><th>Size</th><th class="num">Price</th><th>Date added</th><th>Added by</th><th>Last updated</th><th></th></tr>
  <?php foreach ($items as $i): ?>
  <tr>
    <td><?php if ($i['image_path']): ?><img class="thumb" src="<?= UPLOAD_URL . e($i['image_path']) ?>" alt=""><?php endif; ?></td>
    <td><?= $i['item_id'] ?></td>
    <td><strong><?= e($i['item_name']) ?></strong></td>
    <td><span class="badge"><?= e($i['category_name']) ?></span></td>
    <td><?= e($i['size']) ?></td>
    <td class="num price">&#8369;<?= number_format($i['price'],2) ?></td>
    <td class="meta"><?= $i['date_added'] ? e(date('M j, Y g:i A', strtotime($i['date_added']))) : '—' ?></td>
    <td class="meta"><?= e($i['added_by_name'] ?? '—') ?></td>
    <td class="meta">
      <?= $i['updated_by_name'] ? e($i['updated_by_name']) . ' · ' : '' ?>
      <?= $i['last_updated'] ? e(date('M j, Y g:i A', strtotime($i['last_updated']))) : '—' ?>
    </td>
    <td class="act">
      <a class="btn ghost sm" href="edit.php?id=<?= $i['item_id'] ?>">Edit</a>
      <a class="btn danger sm" href="delete.php?id=<?= $i['item_id'] ?>">Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>
</div>
<?php require '../includes/footer.php'; ?>
