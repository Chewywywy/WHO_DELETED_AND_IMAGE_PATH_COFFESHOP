# Bean Counter — Coffee Shop Menu Management System

A simple PHP + MySQL (PDO) CRUD app for managing a coffee shop's menu: **categories** (parent) and **menu items** (child), with user accounts, search/filter, a recycle bin, and per-item photos.

## Features

- **Authentication** — register, login, logout (password hashing with `password_hash`, session regeneration on login to prevent fixation).
- **Categories (parent entity)** — add, edit, delete, search.
- **Menu items (child entity)** — add, edit, delete, search, filter by category.
- **Audit trail** — every category/item records who added it and who last updated it (`added_by`, `last_updated_by`, `last_updated`, `date_added`).
- **Recycle bin (soft delete)** — deleting a menu item no longer removes the row. It's stamped with `deleted_at` and `deleted_by`, hidden from the normal list, and can be restored from **Items → Recycle bin**, which shows who deleted it and when.
- **Photo per menu item** — each menu item can have one uploaded photo (JPG/PNG/GIF/WEBP, 5MB max), shown as a thumbnail in the menu overview, the items list, and the recycle bin.
- **XSS demo switch** — `SECURE_MODE` in `dbconfig.php` toggles between a deliberately vulnerable mode (for demonstrating the attack) and a secured mode (output escaping, input checks, strong password rules, security headers, HTTP-only session cookie).

## Setup

1. Copy this folder into your web server's root as `coffeeshop/` (the `BASE_URL` constant in `dbconfig.php` expects this folder name — update it if you use a different one).
2. Create the database:
   - **Fresh install:** import `database.sql` in phpMyAdmin — this creates `coffeeshop` with all tables, including the recycle bin and image columns, plus sample categories/items.
   - **Existing database:** if you already have a `coffeeshop` database from before the recycle bin feature, instead run `migration_recycle_bin.sql` and `migration_date_added.sql` — these only *add* the missing columns and keep your existing data.
3. Make sure PHP can write to `uploads/items/` (it's created automatically on first photo upload, but the parent `uploads/` folder needs to be writable by the web server).
4. Visit `http://localhost/coffeeshop/register.php` to create an account, then log in.

## Database schema

| Table | Purpose |
|---|---|
| `users` | Accounts; stores hashed passwords. |
| `categories` | Parent entity — one category has many menu items. |
| `menu_items` | Child entity — belongs to one category (`ON DELETE CASCADE`). Includes `image_path`, `deleted_at`, `deleted_by` for the photo and recycle bin features. |

## Project structure

```
coffeeshop/
├── index.php              # Menu overview (categories + items, photos shown)
├── login.php / register.php / logout.php
├── dbconfig.php           # DB connection, auth helpers, image upload helper
├── database.sql           # Fresh-install schema + seed data
├── migration_recycle_bin.sql   # Adds image_path/deleted_at/deleted_by to an existing DB
├── migration_date_added.sql    # Adds date_added to categories/menu_items
├── categories/            # Category CRUD (add, edit, delete, list)
├── items/                 # Menu item CRUD + recycle bin
│   ├── add.php / edit.php / delete.php / list.php / _form.php
│   ├── recycle_bin.php    # View deleted items (who deleted, when)
│   └── restore.php        # Restore a deleted item
├── includes/              # header.php / footer.php (shared layout)
├── assets/                # style.css, app.js
└── uploads/items/         # Uploaded menu item photos (auto-created)
```

## How the recycle bin works

- `items/delete.php` runs `UPDATE menu_items SET deleted_at = NOW(), deleted_by = <user>` instead of `DELETE`.
- `items/list.php` and `index.php` only show rows where `deleted_at IS NULL`.
- `items/recycle_bin.php` lists rows where `deleted_at IS NOT NULL`, joined to `users` to show who deleted each one.
- `items/restore.php` clears `deleted_at`/`deleted_by`, making the item visible again everywhere.

## How the image upload works

- `dbconfig.php` → `handle_image_upload()` validates the file's real MIME type (not just its extension), enforces a 5MB limit, and saves it into `uploads/items/` with a unique filename.
- The filename is stored in `menu_items.image_path`; the actual file lives on disk, not in the database.
- Editing an item with a new photo deletes the old file (`delete_image_file()`) so uploads don't pile up.

## Known limitations

- Deleting a **category** still hard-deletes its menu items via `ON DELETE CASCADE` — the recycle bin only protects against deleting an item directly, not against a parent category deletion taking its children with it.
- `SECURE_MODE = false` intentionally disables escaping for the XSS demo; never leave it off outside that demonstration.
