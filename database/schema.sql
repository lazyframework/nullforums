-- ============================================================
-- NULLFORUMS — SCHEMA LENGKAP (sesuai kode saat ini)
--
-- PERINGATAN: file ini MENGHAPUS database nullforums beserta
-- seluruh datanya. Jangan jalankan di database yang sudah berisi
-- data penting. Untuk update tanpa hapus data, jalankan bagian
-- yang diperlukan saja.
--
-- Jalankan sebagai root:
--   mysql -u root -p < database/schema.sql
-- ============================================================

DROP DATABASE IF EXISTS nullforums;
CREATE DATABASE nullforums
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE nullforums;

-- ------------------------------------------------------------
-- 1. USERS
-- ------------------------------------------------------------
CREATE TABLE users (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username       VARCHAR(32)  NOT NULL,
  email          VARCHAR(190) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  role           ENUM('user','mod','admin') NOT NULL DEFAULT 'user',
  avatar_initial CHAR(1) NULL,
  avatar_path    VARCHAR(255) NULL,           -- foto profil, mis. /uploads/avatars/xxx.jpg
  is_active      TINYINT(1) NOT NULL DEFAULT 1,
  failed_logins  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until   DATETIME NULL,
  last_login_at  DATETIME NULL,
  last_login_ip  VARBINARY(16) NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email    (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. CATEGORIES (1 kategori = 1 tab di navbar)
--    icon: class Tabler OUTLINE (ti ti-...), bukan emoji
-- ------------------------------------------------------------
CREATE TABLE categories (
  id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(32)  NOT NULL,
  name        VARCHAR(64)  NOT NULL,
  icon        VARCHAR(32)  NOT NULL,
  description VARCHAR(255) NOT NULL,
  sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB;

INSERT INTO categories (id, slug, name, icon, description, sort_order) VALUES
  (1, 'general',     'General',     'ti ti-home',           'Diskusi umum komunitas',                 1),
  (2, 'cracking',    'Cracking',    'ti ti-lock',           'Reverse engineering & security research', 2),
  (3, 'leaks',       'Leaks',       'ti ti-droplet',        'Kebocoran data & analisis',              3),
  (4, 'marketplace', 'Marketplace', 'ti ti-shopping-cart',  'Jual beli & layanan',                    4),
  (5, 'tutorials',   'Tutorials',   'ti ti-book',           'Panduan & tutorial',                     5),
  (6, 'tech',        'Tech',        'ti ti-device-laptop',  'Teknologi & pemrograman',                6),
  (7, 'other',       'Other',       'ti ti-world',          'Topik lainnya',                          7);

-- ------------------------------------------------------------
-- 3. FORUMS (sub-forum di dalam kategori)
-- ------------------------------------------------------------
CREATE TABLE forums (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id TINYINT UNSIGNED NOT NULL,
  slug        VARCHAR(64)  NOT NULL,
  name        VARCHAR(120) NOT NULL,
  description VARCHAR(255) NOT NULL,
  icon        VARCHAR(32)  NOT NULL DEFAULT 'ti ti-message',
  sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_forums_slug (slug),
  KEY idx_forums_category (category_id, sort_order),
  CONSTRAINT fk_forums_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- General (category_id = 1)
INSERT INTO forums (category_id, slug, name, description, icon, sort_order) VALUES
  (1, 'pengumuman',    'Pengumuman & Berita',  'Informasi resmi forum.',  'ti ti-speakerphone', 1),
  (1, 'obrolan-bebas', 'Obrolan Bebas',        'Topik santai.',           'ti ti-message',      2),
  (1, 'bantuan',       'Bantuan & Pertanyaan', 'Tanya jawab.',            'ti ti-help',         3);

-- Cracking (category_id = 2)
INSERT INTO forums (category_id, slug, name, description, icon, sort_order) VALUES
  (2, 'hardening', 'Hardening Server', 'Checklist keamanan server.', 'ti ti-tools', 1);

-- Leaks (category_id = 3)
INSERT INTO forums (category_id, slug, name, description, icon, sort_order) VALUES
  (3, 'database-leaks', 'Database Leaks', 'Kebocoran data publik.', 'ti ti-droplet', 1);

-- Marketplace (category_id = 4)
INSERT INTO forums (category_id, slug, name, description, icon, sort_order) VALUES
  (4, 'jual-beli-akun', 'Jual Beli Akun', 'Marketplace akun.', 'ti ti-shopping-cart', 1);

-- Tutorials (category_id = 5)
INSERT INTO forums (category_id, slug, name, description, icon, sort_order) VALUES
  (5, 'panduan-pemula', 'Panduan Pemula', 'Tutorial dasar untuk pemula.', 'ti ti-book', 1);

-- Tech (category_id = 6)
INSERT INTO forums (category_id, slug, name, description, icon, sort_order) VALUES
  (6, 'php-backend', 'PHP & Backend',      'PHP 8.4, framework, database.', 'ti ti-brand-php',  1),
  (6, 'keamanan',    'Keamanan & Privasi', 'Praktik aman & privasi.',       'ti ti-shield-lock', 2);

-- Other (category_id = 7)
INSERT INTO forums (category_id, slug, name, description, icon, sort_order) VALUES
  (7, 'diskusi-santai', 'Diskusi Santai', 'Obrolan di luar topik utama.', 'ti ti-world', 1);

-- ------------------------------------------------------------
-- 4. THREADS
-- ------------------------------------------------------------
CREATE TABLE threads (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  forum_id     INT UNSIGNED NOT NULL,
  user_id      BIGINT UNSIGNED NOT NULL,
  title        VARCHAR(180) NOT NULL,
  slug         VARCHAR(200) NOT NULL,
  body         MEDIUMTEXT   NOT NULL,
  is_pinned    TINYINT(1) NOT NULL DEFAULT 0,
  is_locked    TINYINT(1) NOT NULL DEFAULT 0,
  is_deleted   TINYINT(1) NOT NULL DEFAULT 0,
  views        INT UNSIGNED NOT NULL DEFAULT 0,
  reply_count  INT UNSIGNED NOT NULL DEFAULT 0,
  last_post_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_post_by BIGINT UNSIGNED NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_threads_slug (slug),
  KEY idx_threads_forum   (forum_id, is_deleted, last_post_at),
  KEY idx_threads_popular (is_deleted, reply_count, views),
  KEY idx_threads_user    (user_id, is_deleted),
  FULLTEXT KEY ft_threads (title, body),
  CONSTRAINT fk_threads_forum FOREIGN KEY (forum_id) REFERENCES forums(id) ON DELETE CASCADE,
  CONSTRAINT fk_threads_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. POSTS (balasan)
-- ------------------------------------------------------------
CREATE TABLE posts (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  thread_id  BIGINT UNSIGNED NOT NULL,
  user_id    BIGINT UNSIGNED NOT NULL,
  body       MEDIUMTEXT NOT NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_posts_thread (thread_id, is_deleted, created_at),
  KEY idx_posts_user   (user_id, is_deleted),
  CONSTRAINT fk_posts_thread FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
  CONSTRAINT fk_posts_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. SESSIONS (dipakai DbSessionHandler)
-- ------------------------------------------------------------
CREATE TABLE sessions (
  id            VARBINARY(128) PRIMARY KEY,
  user_id       BIGINT UNSIGNED NULL,
  ip            VARBINARY(16) NOT NULL,
  user_agent    VARCHAR(255) NOT NULL,
  payload       MEDIUMBLOB NOT NULL,
  last_activity INT UNSIGNED NOT NULL,
  KEY idx_sessions_activity (last_activity),
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. LOGIN ATTEMPTS
-- ------------------------------------------------------------
CREATE TABLE login_attempts (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip         VARBINARY(16) NOT NULL,
  username   VARCHAR(32) NOT NULL,
  success    TINYINT(1) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_attempts_ip (ip, created_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. AUDIT LOG
-- ------------------------------------------------------------
CREATE TABLE audit_log (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    BIGINT UNSIGNED NULL,
  action     VARCHAR(64) NOT NULL,
  entity     VARCHAR(64) NULL,
  entity_id  BIGINT UNSIGNED NULL,
  ip         VARBINARY(16) NOT NULL,
  meta       JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_user (user_id, created_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 9. ACTION THROTTLE (rate limit buat thread & balasan)
-- ------------------------------------------------------------
CREATE TABLE action_throttle (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    BIGINT UNSIGNED NOT NULL,
  action     VARCHAR(32) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_throttle (user_id, action, created_at),
  CONSTRAINT fk_throttle_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 10. USER DATABASE UNTUK APLIKASI
--     GANTI 'GANTI_PASSWORD_INI' dengan password kuat, lalu
--     samakan DB_PASS di file .env.
-- ------------------------------------------------------------
CREATE USER IF NOT EXISTS 'nf'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_INI';
ALTER USER 'nf'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_INI';
GRANT SELECT, INSERT, UPDATE, DELETE ON nullforums.* TO 'nf'@'localhost';
FLUSH PRIVILEGES;

-- ------------------------------------------------------------
-- 11. VERIFIKASI
-- ------------------------------------------------------------
SELECT id, slug, name, icon FROM categories ORDER BY sort_order;
SELECT f.id, c.slug AS kategori, f.slug AS forum, f.name, f.icon
  FROM forums f JOIN categories c ON c.id = f.category_id
 ORDER BY c.sort_order, f.sort_order;
