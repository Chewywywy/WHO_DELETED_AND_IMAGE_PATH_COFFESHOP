-- Run this once in phpMyAdmin (SQL tab) against your existing `coffeeshop` database.
-- It only ADDS columns, so your current categories/menu_items data is kept.

ALTER TABLE menu_items
  ADD COLUMN image_path VARCHAR(255) NULL AFTER size,
  ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN deleted_by INT NULL,
  ADD CONSTRAINT fk_menu_items_deleted_by
      FOREIGN KEY (deleted_by) REFERENCES users(user_id) ON DELETE SET NULL;
