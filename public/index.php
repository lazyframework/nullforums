<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

// ---------- Bootstrap ----------
require __DIR__ . '/../vendor/autoload.php';

// Load .env
$envFile = __DIR__ . '/../.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, " \t\"'");
    }
}

$required = ['DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS'];
$missing  = array_filter($required, fn($k) => empty($_ENV[$k]));
if ($missing) {
    http_response_code(500);
    error_log('ENV hilang: ' . implode(', ', $missing));
    exit('Konfigurasi server tidak lengkap.');
}

use NF\{Security, Csrf, Auth, Category, Database, Validator, Thread, Throttle, DbSessionHandler, Turnstile};

Security::headers();

// ---------- KONFIGURASI ----------
const ALLOWED_CREATE_CATS = ['cracking','leaks','marketplace','tutorials','tech','other'];

// Session via DB
$handler = new DbSessionHandler();
session_set_save_handler($handler, true);

session_name($_ENV['SESSION_NAME'] ?? 'NF_SESS');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => (bool)($_ENV['SESSION_SECURE'] ?? 0),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Idle timeout
if (!empty($_SESSION['uid'])) {
    if (time() - ($_SESSION['last_act'] ?? 0) > (int)($_ENV['SESSION_LIFETIME'] ?? 1800)) {
        Auth::logout();
    } else {
        $_SESSION['last_act'] = time();
    }
}

// ---------- Render helper ----------
function render(string $tpl, array $data = []): void
{
    $tplFile = __DIR__ . '/../templates/' . $tpl . '.php';
    if (!is_file($tplFile)) {
        http_response_code(500);
        error_log('Template hilang: ' . $tpl);
        exit('Template tidak ditemukan: ' . htmlspecialchars($tpl));
    }

    extract($data, EXTR_SKIP);
    $user   = Auth::user();
    $tabs   = Category::all();
    $active = $data['active_slug'] ?? 'general';
    $csrf   = Csrf::token();
    $range  = $data['range'] ?? 'all';

    require __DIR__ . '/../templates/layout.php';
}

// Helper: ambil forum DEFAULT dari sebuah kategori
function defaultForumForCategory(int $categoryId): ?array
{
    $row = Database::q(
        'SELECT id, slug, name FROM forums
          WHERE category_id = ? AND is_active = 1
       ORDER BY sort_order ASC
          LIMIT 1',
        [$categoryId]
    )->fetch();
    return $row ?: null;
}

// ---------- Routing ----------
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri  = '/' . trim($uri, '/');
$user = Auth::user();

// User yang di-ban langsung dikeluarkan dari sesi
if ($user) {
    $isActive = (int)Database::q('SELECT is_active FROM users WHERE id = ?', [(int)$user['id']])->fetchColumn();
    if ($isActive !== 1) {
        Auth::logout();
        $user = null;
    }
}

// ---- Beranda ----
if ($uri === '/' || $uri === '/index.php') {
    render('home', ['active_slug' => 'general']);
    exit;
}

// ---- GET /login ----
if ($uri === '/login' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($user) { header('Location: /'); exit; }
    render('login', ['error' => null, 'hide_nav' => true]);
    exit;
}

// ---- POST /login ----
if ($uri === '/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_csrf'] ?? null)) { http_response_code(419); exit('CSRF'); }

    if (!Turnstile::verify($_POST['cf-turnstile-response'] ?? null)) {
        $error = 'Verifikasi keamanan gagal. Silakan centang ulang atau coba lagi.';
    } else {
        $login = trim((string)($_POST['login'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');
        if ($login === '' || strlen($pass) < 8) {
            $error = 'Input tidak valid.';
        } else {
            [$ok, $msg] = Auth::login($login, $pass);
            if ($ok) {
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Selamat datang kembali! Anda berhasil masuk.'];
                header('Location: /');
                exit;
            }
            $error = $msg;
        }
    }
    render('login', ['error' => $error, 'hide_nav' => true]);
    exit;
}

// ---- GET /register ----
if ($uri === '/register' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($user) { header('Location: /'); exit; }
    render('register', ['errors' => [], 'hide_nav' => true]);
    exit;
}

// ---- POST /register ----
if ($uri === '/register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_csrf'] ?? null)) { http_response_code(419); exit('CSRF'); }

    if (!Turnstile::verify($_POST['cf-turnstile-response'] ?? null)) {
        render('register', ['errors' => ['Verifikasi keamanan gagal. Silakan coba lagi.'], 'hide_nav' => true]);
        exit;
    }

    $errors   = [];
    $username = trim((string)($_POST['username'] ?? ''));
    $email    = trim((string)($_POST['email']    ?? ''));
    $pass     = (string)($_POST['password']      ?? '');
    $pass2    = (string)($_POST['password2']     ?? '');

    if ($m = Validator::username($username)) $errors[] = $m;
    if ($m = Validator::email($email))       $errors[] = $m;
    if ($m = Validator::password($pass))     $errors[] = $m;
    if ($pass !== $pass2)                    $errors[] = 'Konfirmasi kata sandi tidak cocok.';

    if (!$errors) {
        $dup = Database::q('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1',
                           [$username, $email])->fetch();
        if ($dup) $errors[] = 'Username atau email sudah dipakai.';
    }
    if ($errors) { render('register', ['errors' => $errors, 'hide_nav' => true]); exit; }

    $avatar = strtoupper(mb_substr($username, 0, 1));
    Database::q(
        'INSERT INTO users (username, email, password_hash, avatar_initial)
         VALUES (?,?,?,?)',
        [$username, $email, Auth::hash($pass), $avatar]
    );
    $newId = (int)Database::conn()->lastInsertId();

    session_regenerate_id(true);
    $_SESSION['uid']      = $newId;
    $_SESSION['role']     = 'user';
    $_SESSION['last_act'] = time();

    NF\Audit::log('user.register', 'user', $newId);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Pendaftaran berhasil! Selamat bergabung di NullForums.'];
    header('Location: /');
    exit;
}

// ---- Logout ----
if ($uri === '/logout') {
    Auth::logout();
    header('Location: /');
    exit;
}

// ---- GET /{cat}/f/{forum}/new : form buat thread di forum tertentu ----
if (preg_match('#^/([a-z0-9\-]+)/f/([a-z0-9\-]+)/new$#', $uri, $m)
    && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$user) { header('Location: /login'); exit; }
    $forum = Database::q(
        'SELECT f.id, f.slug, f.name, c.name AS category_name, c.slug AS cat_slug
           FROM forums f JOIN categories c ON c.id = f.category_id
          WHERE f.slug = ? AND c.slug = ? LIMIT 1',
        [$m[2], $m[1]]
    )->fetch();
    if (!$forum) { http_response_code(404); render('404', []); exit; }
    render('new_thread', [
        'forum'       => $forum,
        'error'       => null,
        'active_slug' => $m[1],
        'hide_nav'    => true,
    ]);
    exit;
}

// ---- POST /f/{forum}/new ----
if (preg_match('#^/f/([a-z0-9\-]+)/new$#', $uri, $m)
    && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user) { http_response_code(403); exit('Harus login.'); }
    if (!Csrf::check($_POST['_csrf'] ?? null)) { http_response_code(419); exit('CSRF'); }

    $forum = Database::q(
        'SELECT f.id, f.slug, f.name, c.name AS category_name, c.slug AS cat_slug
           FROM forums f JOIN categories c ON c.id = f.category_id
          WHERE f.slug = ? LIMIT 1',
        [$m[1]]
    )->fetch();
    if (!$forum) { http_response_code(404); render('404', []); exit; }

    $title = trim((string)($_POST['title'] ?? ''));
    $body  = trim((string)($_POST['body']  ?? ''));
    $error = Validator::title($title) ?? Validator::body($body);

    if (!$error && !Throttle::allow((int)$user['id'], 'thread.create', 5, 60)) {
        $error = 'Terlalu sering membuat thread. Coba lagi nanti.';
    }
    if ($error) {
        render('new_thread', [
            'forum'       => $forum,
            'error'       => $error,
            'active_slug' => $forum['cat_slug'],
            'hide_nav'    => true,
        ]);
        exit;
    }

    $slug = Thread::create((int)$forum['id'], (int)$user['id'], $title, $body);
    header('Location: /t/' . $slug);
    exit;
}

// ---- GET /new : form global dengan dropdown kategori ----
if ($uri === '/new' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$user) { header('Location: /login'); exit; }

    $cats = array_values(array_filter(
        Category::all(),
        fn($c) => in_array($c['slug'], ALLOWED_CREATE_CATS, true)
    ));

    render('new_thread', [
        'forum'       => null,
        'cats'        => $cats,
        'error'       => null,
        'active_slug' => 'general',
        'hide_nav'    => true,
    ]);
    exit;
}

// ---- POST /new : proses form global ----
if ($uri === '/new' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user) { http_response_code(403); exit('Harus login.'); }
    if (!Csrf::check($_POST['_csrf'] ?? null)) { http_response_code(419); exit('CSRF'); }

    $catSlug = trim((string)($_POST['category'] ?? ''));
    $title   = trim((string)($_POST['title'] ?? ''));
    $body    = trim((string)($_POST['body']  ?? ''));

    $error = null;
    $cat   = null;

    if ($catSlug === '') {
        $error = 'Kategori wajib dipilih.';
    } elseif (!in_array($catSlug, ALLOWED_CREATE_CATS, true)) {
        $error = 'Kategori tidak tersedia.';
    } else {
        $cat = Category::bySlug($catSlug);
        if (!$cat) $error = 'Kategori tidak ditemukan.';
    }

    $forum = null;
    if (!$error && $cat) {
        $forum = defaultForumForCategory((int)$cat['id']);
        if (!$forum) {
            $error = 'Kategori "' . $cat['name'] . '" belum punya forum aktif. Silakan hubungi Admin.';
        }
    }

    if (!$error) $error = Validator::title($title);
    if (!$error) $error = Validator::body($body);
    if (!$error && !Throttle::allow((int)$user['id'], 'thread.create', 5, 60)) {
        $error = 'Terlalu sering membuat thread. Coba lagi nanti.';
    }

    if ($error) {
        $cats = array_values(array_filter(
            Category::all(),
            fn($c) => in_array($c['slug'], ALLOWED_CREATE_CATS, true)
        ));
        render('new_thread', [
            'forum'       => null,
            'cats'        => $cats,
            'error'       => $error,
            'active_slug' => 'general',
            'hide_nav'    => true,
        ]);
        exit;
    }

    $slug = Thread::create((int)$forum['id'], (int)$user['id'], $title, $body);
    header('Location: /t/' . $slug);
    exit;
}

// ---- GET /api/forums?cat=SLUG ----
if ($uri === '/api/forums' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    $cat = Category::bySlug((string)($_GET['cat'] ?? ''));

    if (!$cat || !in_array($cat['slug'], ALLOWED_CREATE_CATS, true)) {
        echo json_encode(['ok' => false, 'forums' => []]);
        exit;
    }

    $rows = Database::q(
        'SELECT slug, name FROM forums
          WHERE category_id = ? AND is_active = 1
       ORDER BY sort_order ASC',
        [(int)$cat['id']]
    )->fetchAll();
    echo json_encode(['ok' => true, 'forums' => $rows]);
    exit;
}

// ---- GET /api/home_threads?cat=SLUG&page=N&limit=N : pagination beranda ----
if ($uri === '/api/home_threads' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');

    $cat = Category::bySlug((string)($_GET['cat'] ?? ''));
    if (!$cat || $cat['slug'] === 'general') {
        echo json_encode(['ok' => false]);
        exit;
    }

    $limit = min(10, max(1, (int)($_GET['limit'] ?? 3)));
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $catId = (int)$cat['id'];

    $total = (int)Database::q(
        'SELECT COUNT(*) FROM threads t
           JOIN forums f ON f.id = t.forum_id
          WHERE f.category_id = ? AND t.is_deleted = 0',
        [$catId]
    )->fetchColumn();

    $totalPages = max(1, (int)ceil($total / $limit));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $limit;

    $rows = Database::q(
        'SELECT t.id, t.user_id, t.title, t.slug, t.reply_count, t.views, t.last_post_at,
                f.name AS forum_name, f.icon, u.username
           FROM threads t
           JOIN forums f ON f.id = t.forum_id
           JOIN users  u ON u.id = t.user_id
          WHERE f.category_id = ? AND t.is_deleted = 0
       ORDER BY t.last_post_at DESC
          LIMIT ' . $limit . ' OFFSET ' . $offset,
        [$catId]
    )->fetchAll();

    $isStaff = !empty($user) && in_array($user['role'], ['admin', 'mod'], true);

    $threads = array_map(fn($t) => [
        'id'          => (int)$t['id'],
        'title'       => $t['title'],
        'slug'        => $t['slug'],
        'icon'        => $t['icon'],
        'forum_name'  => $t['forum_name'],
        'username'    => $t['username'],
        'reply_count' => (int)$t['reply_count'],
        'views'       => (int)$t['views'],
        'time'        => relative_time($t['last_post_at']),
        'can_delete'  => $isStaff || (!empty($user) && (int)$user['id'] === (int)$t['user_id']),
    ], $rows);

    echo json_encode([
        'ok'         => true,
        'page'       => $page,
        'totalPages' => $totalPages,
        'total'      => $total,
        'threads'    => $threads,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- GET /u/{username} : profil user ----
if (preg_match('#^/u/([a-zA-Z0-9_.]{3,32})$#', $uri, $m)
    && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $profileUser = Auth::findByUsername($m[1]);
    if (!$profileUser) { http_response_code(404); render('404', []); exit; }

    $page    = max(1, (int)($_GET['page'] ?? 1));
    $threads = Thread::byUser((int)$profileUser['id'], $page);
    $total   = Thread::countByUser((int)$profileUser['id']);

    render('profile', [
        'active_slug'  => 'general',
        'profileUser'  => $profileUser,
        'threads'      => $threads,
        'totalThreads' => $total,
        'page'         => $page,
        'hide_nav'     => true,
    ]);
    exit;
}

// ---- POST /u/avatar : upload foto profil ----
if ($uri === '/u/avatar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user) { http_response_code(403); exit('Unauthorized.'); }
    if (!Csrf::check($_POST['_csrf'] ?? null)) { http_response_code(419); exit('CSRF'); }

    $back = '/u/' . $user['username'];

    if (empty($_FILES['avatar']['name'])) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Please select an image file first.'];
        header('Location: ' . $back); exit;
    }

    $file    = $_FILES['avatar'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($file['type'], $allowed, true)) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Format must be JPG, PNG, or WEBP.'];
        header('Location: ' . $back); exit;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Image size must not exceed 2MB.'];
        header('Location: ' . $back); exit;
    }

    $uploadDir = __DIR__ . '/uploads/avatars/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        $oldPath = (string)($user['avatar_path'] ?? '');
        if ($oldPath !== '' && is_file($uploadDir . basename($oldPath))) {
            unlink($uploadDir . basename($oldPath));
        }

        Database::q('UPDATE users SET avatar_path = ? WHERE id = ?',
                    ['/uploads/avatars/' . $filename, (int)$user['id']]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Profile picture updated successfully!'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Failed to upload image.'];
    }
    header('Location: ' . $back); exit;
}

// ---- POST /api/thread/delete : hapus thread (pemilik, admin, mod) ----
if ($uri === '/api/thread/delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    if (!$user) { echo json_encode(['ok' => false, 'msg' => 'Unauthorized']); exit; }
    if (!Csrf::check($_POST['_csrf'] ?? null)) { echo json_encode(['ok' => false, 'msg' => 'CSRF']); exit; }

    $id     = (int)($_POST['thread_id'] ?? 0);
    $thread = Database::q('SELECT id, user_id FROM threads WHERE id = ? AND is_deleted = 0', [$id])->fetch();
    if (!$thread) { echo json_encode(['ok' => false, 'msg' => 'Thread not found']); exit; }

    $isOwner = ((int)$thread['user_id'] === (int)$user['id']);
    $isStaff = in_array($user['role'], ['admin', 'mod'], true);
    if (!$isOwner && !$isStaff) { echo json_encode(['ok' => false, 'msg' => 'Permission denied']); exit; }

    Database::q('UPDATE threads SET is_deleted = 1 WHERE id = ?', [$id]);
    NF\Audit::log('thread.delete', 'thread', $id);

    echo json_encode(['ok' => true]);
    exit;
}

// ---- POST /api/mod/ban : ban/unban user + sembunyikan thread (admin & mod) ----
if ($uri === '/api/mod/ban' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user || !in_array($user['role'], ['admin', 'mod'], true)) { http_response_code(403); exit('Permission denied.'); }
    if (!Csrf::check($_POST['_csrf'] ?? null)) { http_response_code(419); exit('CSRF'); }

    $targetId   = (int)($_POST['user_id'] ?? 0);
    $action     = ($_POST['action'] ?? 'ban') === 'unban' ? 'unban' : 'ban';
    $threadSlug = trim((string)($_POST['thread_slug'] ?? ''));

    $target = Database::q('SELECT id, role FROM users WHERE id = ?', [$targetId])->fetch();
    if (!$target) { http_response_code(404); exit('User not found.'); }

    // Mod tidak boleh mengganggu admin, mod lain, atau dirinya sendiri
    if ((int)$target['id'] === (int)$user['id']
        || $target['role'] === 'admin'
        || ($target['role'] === 'mod' && $user['role'] !== 'admin')) {
        http_response_code(403); exit('You cannot moderate this account.');
    }

    if ($action === 'ban') {
        Database::q('UPDATE users SET is_active = 0 WHERE id = ?', [$targetId]);
        Database::q('DELETE FROM sessions WHERE user_id = ?', [$targetId]);
        NF\Audit::log('user.ban', 'user', $targetId);
    } else {
        Database::q('UPDATE users SET is_active = 1 WHERE id = ?', [$targetId]);
        NF\Audit::log('user.unban', 'user', $targetId);
    }

    if ($threadSlug !== '') {
        $tid = Database::q('SELECT id FROM threads WHERE slug = ?', [$threadSlug])->fetchColumn();
        Database::q('UPDATE threads SET is_deleted = 1 WHERE slug = ?', [$threadSlug]);
        NF\Audit::log('thread.hide', 'thread', $tid ? (int)$tid : null);
    }

    $_SESSION['flash'] = ['type' => 'success', 'msg' => $action === 'ban'
        ? 'User di-ban' . ($threadSlug !== '' ? ' dan thread disembunyikan.' : '.')
        : 'User di-unban.'];
    header('Location: /');
    exit;
}

// ---- GET /members : daftar anggota dengan pagination ----
if ($uri === '/members' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $perPage = 20;
    $page    = max(1, (int)($_GET['page'] ?? 1));

    $total      = (int)Database::q('SELECT COUNT(*) FROM users')->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    $users = Database::q(
        "SELECT u.id, u.username, u.role, u.avatar_initial, u.avatar_path, u.created_at,
                (SELECT COUNT(*) FROM threads t
                  WHERE t.user_id = u.id AND t.is_deleted = 0) AS thread_count,
                (SELECT COUNT(*) FROM posts p
                  WHERE p.user_id = u.id AND p.is_deleted = 0) AS post_count
           FROM users u
       ORDER BY u.created_at DESC
          LIMIT $perPage OFFSET $offset"
    )->fetchAll();

    render('members', [
        'active_slug' => 'general',
        'users'       => $users,
        'total'       => $total,
        'page'        => $page,
        'totalPages'  => $totalPages,
    ]);
    exit;
}

// ---- GET /t/{slug} ----
if (preg_match('#^/t/([a-z0-9\-]+)$#', $uri, $m)
    && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $thread = Thread::bySlug($m[1]);
    if (!$thread) { http_response_code(404); render('404', []); exit; }
    Thread::incrementViews((int)$thread['id']);

    $page = max(1, (int)($_GET['page'] ?? 1));

    render('thread', [
        'active_slug' => $thread['cat_slug'],
        'thread'      => $thread,
        'replies'     => Thread::replies((int)$thread['id'], $page),
        'page'        => $page,
    ]);
    exit;
}

// ---- POST /t/{slug}/reply ----
if (preg_match('#^/t/([a-z0-9\-]+)/reply$#', $uri, $m)
    && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user) { http_response_code(403); exit('Harus login.'); }
    if (!Csrf::check($_POST['_csrf'] ?? null)) { http_response_code(419); exit('CSRF'); }

    $thread = Thread::bySlug($m[1]);
    if (!$thread) { http_response_code(404); render('404', []); exit; }
    if ((int)$thread['is_locked']) { http_response_code(403); exit('Thread terkunci.'); }

    $body = trim((string)($_POST['body'] ?? ''));
    $err  = Validator::body($body);
    if (!$err && !Throttle::allow((int)$user['id'], 'post.create', 20, 60)) {
        $err = 'Terlalu banyak balasan. Coba lagi nanti.';
    }
    if ($err) {
        render('thread', [
            'active_slug' => $thread['cat_slug'],
            'thread'      => $thread,
            'replies'     => Thread::replies((int)$thread['id'], 1),
            'replyError'  => $err,
            'page'        => 1,
        ]);
        exit;
    }

    Thread::addReply((int)$thread['id'], (int)$user['id'], $body);

    $newTotal = (int)$thread['reply_count'] + 1;
    $lastPage = (int)ceil($newTotal / 20);
    header('Location: /t/' . $thread['slug'] . '?page=' . $lastPage . '#bottom');
    exit;
}

// ---- GET /search ----
if ($uri === '/search' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $q = trim((string)($_GET['q'] ?? ''));
    $results = [];
    if (mb_strlen($q) >= 3) {
        $results = Database::q(
            'SELECT t.id, t.title, t.slug, t.reply_count, t.views, t.last_post_at,
                    f.icon, c.slug AS cat_slug, c.name AS cat_name
               FROM threads t
               JOIN forums f     ON f.id = t.forum_id
               JOIN categories c ON c.id = f.category_id
              WHERE t.is_deleted = 0
                AND MATCH(t.title, t.body) AGAINST (? IN NATURAL LANGUAGE MODE)
           ORDER BY MATCH(t.title, t.body) AGAINST (? IN NATURAL LANGUAGE MODE) DESC
              LIMIT 50',
            [$q, $q]
        )->fetchAll();
    }
    render('search', ['q' => $q, 'results' => $results, 'active_slug' => 'general']);
    exit;
}

// ---- GET /{cat} : daftar thread per kategori (route kategori di root) ----
// Ditaruh setelah semua route tetap agar /new, /members, /search, dan lainnya tidak tertangkap di sini.
if (preg_match('#^/([a-z0-9\-]{1,32})$#', $uri, $m)
    && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $cat = Category::bySlug($m[1]);
    if (!$cat) { http_response_code(404); render('404', []); exit; }

    $range = $_GET['range'] ?? 'all';
    if (!in_array($range, ['all','today','week','month'], true)) $range = 'all';

    $rangeSql = match ($range) {
        'today' => 'AND t.last_post_at >= CURDATE()',
        'week'  => 'AND t.last_post_at >= (NOW() - INTERVAL 7 DAY)',
        'month' => 'AND t.last_post_at >= (NOW() - INTERVAL 30 DAY)',
        default => '',
    };

    $perPage = 10;
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $catId   = (int)$cat['id'];

    $total = (int)Database::q(
        "SELECT COUNT(*) FROM threads t
           JOIN forums f ON f.id = t.forum_id
          WHERE f.category_id = ? AND t.is_deleted = 0 $rangeSql",
        [$catId]
    )->fetchColumn();

    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    $threads = Database::q(
        "SELECT t.id, t.title, t.slug, t.reply_count, t.views, t.last_post_at,
                f.name AS forum_name, f.icon, u.username
           FROM threads t
           JOIN forums f ON f.id = t.forum_id
           JOIN users  u ON u.id = t.user_id
          WHERE f.category_id = ? AND t.is_deleted = 0 $rangeSql
       ORDER BY t.last_post_at DESC
          LIMIT " . $perPage . " OFFSET " . $offset,
        [$catId]
    )->fetchAll();

    render('category', [
        'active_slug' => $cat['slug'],
        'cat'         => $cat,
        'stats'       => Category::stats($catId),
        'forums'      => Category::forums($catId, $range),
        'popular'     => Category::popularThreads($catId, 5),
        'range'       => $range,
        'threads'     => $threads,
        'page'        => $page,
        'totalPages'  => $totalPages,
    ]);
    exit;
}

// ---- 404 ----
http_response_code(404);
render('404', []);
