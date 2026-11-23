<?php
// Database connection using PDO

// ---- XSS DEMO SWITCH ----
// false = VULNERABLE (for demonstrating the XSS attack only)
// true  = SECURE (escaping, input checks, safe cookie, security headers, strong passwords)
define('SECURE_MODE', true);
define('BASE_URL', '/coffeeshop');   // change if your folder name is different

// Where uploaded menu item photos live on disk, and the URL used to show them
define('UPLOAD_DIR', __DIR__ . '/uploads/items/');
define('UPLOAD_URL', BASE_URL . '/uploads/items/');

$host    = 'localhost';
$dbname  = 'coffeeshop';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die('Connection failed: ' . htmlspecialchars($e->getMessage()));
}

// ---- XSS protection: safer session cookie + security headers ----
if (SECURE_MODE) {
    ini_set('session.cookie_httponly', 1);   // JavaScript cannot read the session cookie
    ini_set('session.use_strict_mode', 1);
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Escape output so the browser shows it as text and never runs it as code
function e($v) {
    if (!SECURE_MODE) { return (string)$v; }   // vulnerable on purpose (demo only)
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Input check: true if the text contains < or > (possible HTML / script tags)
function has_html($text) {
    return (bool)preg_match('/[<>]/', (string)$text);
}

// Password rules: min 8 characters, with uppercase, lowercase and a number.
// Returns true when OK, or an error message string when not.
function is_strong_password($password) {
    if (strlen($password) < 8)              return 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Z]/', $password))  return 'Password must have at least one uppercase letter.';
    if (!preg_match('/[a-z]/', $password))  return 'Password must have at least one lowercase letter.';
    if (!preg_match('/[0-9]/', $password))  return 'Password must have at least one number.';
    return true;
}

function flash($msg = null, $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return; }
    if (isset($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}

function redirect($path) { header('Location: ' . BASE_URL . $path); exit; }

// ---- AUTH HELPERS ----

// Is anyone logged in right now?
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// The id of the logged-in user, or null
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// The username of the logged-in user
function current_username() {
    return $_SESSION['username'] ?? '';
}

// Call this at the top of any page that requires login.
// If nobody is logged in, bounce to the login page.
function require_login() {
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}

// ---- IMAGE UPLOAD HELPER ----
// Handles a single uploaded image for a form field.
// Returns:
//   - a new filename (string) if a valid image was uploaded
//   - null if the field was left empty (no new file chosen)
//   - false if something was uploaded but was invalid (check $error)
function handle_image_upload($field, &$error) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        $error = 'There was a problem uploading the image. Please try again.';
        return false;
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES[$field]['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        $error = 'Only JPG, PNG, GIF or WEBP images are allowed.';
        return false;
    }
    if ($_FILES[$field]['size'] > 5 * 1024 * 1024) {
        $error = 'Image must be smaller than 5MB.';
        return false;
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename = uniqid('item_', true) . '.' . $allowed[$mime];
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], UPLOAD_DIR . $filename)) {
        $error = 'Could not save the uploaded image. Check folder permissions.';
        return false;
    }

    return $filename;
}

// Deletes an item's image file from disk (used when replacing or permanently removing one)
function delete_image_file($filename) {
    if ($filename && is_file(UPLOAD_DIR . $filename)) {
        @unlink(UPLOAD_DIR . $filename);
    }
}
