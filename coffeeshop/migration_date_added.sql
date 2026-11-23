-- Adds the date_added column that items/list.php and categories/list.php
-- already expect but which was never created in the original schema.
-- Existing rows get 'now' as a default added date (reasonable since we don't
-- know their real creation time); new rows get it automatically going forward.

ALTER TABLE menu_items
  ADD COLUMN date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE categories
  ADD COLUMN date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
