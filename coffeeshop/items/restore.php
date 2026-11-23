<?php
require_once '../dbconfig.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/items/recycle_bin.php'); }

$id = (int)($_POST['item_id'] ?? 0);

$stmt = $pdo->prepare("SELECT item_name FROM menu_items WHERE item_id = :id AND deleted_at IS NOT NULL");
$stmt->execute([':id' => $id]);
$item = $stmt->fetch();

if ($item) {
    $up = $pdo->prepare("UPDATE menu_items SET deleted_at = NULL, deleted_by = NULL WHERE item_id = :id");
    $up->execute([':id' => $id]);
    flash("Menu item \"{$item['item_name']}\" restored.");
} else {
    flash('That item could not be found in the recycle bin.', 'err');
}

redirect('/items/recycle_bin.php');
