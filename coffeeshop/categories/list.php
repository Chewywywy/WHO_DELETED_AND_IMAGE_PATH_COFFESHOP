<?php
$page = 'cat'; $title = 'Categories';
require '../includes/header.php';
require_login();

// SEARCH: match the keyword against the category name or description
$q = trim($_GET['q'] ?? '');
$sql = "
    SELECT c.*, COUNT(m.item_id) AS item_count,
           au.username AS added_by_name, uu.username AS updated_by_name
    FROM categories c
    LEFT JOIN menu_items m ON m.category_id = c.category_id
    LEFT JOIN users au ON au.user_id = c.added_by
    LEFT JOIN users uu ON uu.user_id = c.last_updated_by";
$params = [];
if ($q !== '') {
    $sql .= " WHERE c.category_name LIKE :q OR c.description LIKE :q2";
    $params[':q']  = '%' . $q . '%';
    $params[':q2'] = '%' . $q . '%';
}
$sql .= " GROUP BY c.category_id ORDER BY c.category_id";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cats = $stmt->fetchAll();
?>
<div class="head">
  <div><h1>Categories</h1><p>The parent records. Each one can have many menu items.</p></div>
  <div class="filter">
    <form method="get" class="filter">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search categories..." maxlength="100" aria-label="Search categories">
      <button class="btn" type="submit">Search</button>
      <?php if ($q !== ''): ?><a class="btn ghost" href="list.php">Clear</a><?php endif; ?>
    </form>
    <a class="btn" href="add.php">Add category</a>
  </div>
</div>
<div class="panel">
<?php if (!$cats): ?><div class="empty"><?= $q !== '' ? 'No categories match "' . e($q) . '".' : 'No categories yet. Add one to get started.' ?></div><?php else: ?>
<table>
  <tr><th>ID</th><th>Category</th><th>Description</th><th class="num">Items</th><th>Date added</th><th>Added by</th><th>Last updated</th><th></th></tr>
  <?php foreach ($cats as $c): ?>
  <tr>
    <td><?= $c['category_id'] ?></td>
    <td><strong><?= e($c['category_name']) ?></strong></td>
    <td><?= e($c['description']) ?></td>
    <td class="num"><span class="badge"><?= $c['item_count'] ?></span></td>
    <td class="meta"><?= $c['date_added'] ? e(date('M j, Y g:i A', strtotime($c['date_added']))) : '—' ?></td>
    <td class="meta"><?= e($c['added_by_name'] ?? '—') ?></td>
    <td class="meta">
      <?= $c['updated_by_name'] ? e($c['updated_by_name']) . ' · ' : '' ?>
      <?= $c['last_updated'] ? e(date('M j, Y g:i A', strtotime($c['last_updated']))) : '—' ?>
    </td>
    <td class="act">
      <a class="btn ghost sm" href="edit.php?id=<?= $c['category_id'] ?>">Edit</a>
      <a class="btn danger sm" href="delete.php?id=<?= $c['category_id'] ?>">Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>
</div>
<?php require '../includes/footer.php'; ?>
