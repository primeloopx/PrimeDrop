<?php
// ╔══════════════════════════════════════════════════════════╗
// ║              PrimeDrop — config/app.php                  ║
// ║         One file controls everything. Edit here.         ║
// ╚══════════════════════════════════════════════════════════╝

// ─────────────────────────────────────────────
//  APP IDENTITY
// ─────────────────────────────────────────────
define('APP_NAME',           'PrimeDrop');               // Project name
define('APP_DEV_NAME',       'PrimeTriple4');            // Your personal developer name
define('APP_COPYRIGHT_NAME', 'PrimeLoopX');      // Brand / studio name
define('APP_VERSION',        '1.0.0');                   // Version

// ─────────────────────────────────────────────
//  ADMIN KEY  (offline password — change this!)
// ─────────────────────────────────────────────
define('ADMIN_KEY', 'primesecure');

// ─────────────────────────────────────────────
//  STORAGE MODE
//  'json' → flat file, no DB needed (default)
//  'mysql' → PDO MySQL (fill DB config below)
// ─────────────────────────────────────────────
define('STORAGE_MODE', 'json');  // 'json' or 'mysql'

// ─────────────────────────────────────────────
//  DATABASE (only used when STORAGE_MODE = 'mysql')
// ─────────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_PORT',     '3306');
define('DB_NAME',     'primedrop');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_CHARSET',  'utf8mb4');

// ─────────────────────────────────────────────
//  PATHS
// ─────────────────────────────────────────────
define('BASE_DIR',     dirname(__DIR__));
define('DATA_FILE',    BASE_DIR . '/data/files.json');
define('UPLOADS_DIR',  BASE_DIR . '/uploads/');
define('UPLOADS_URL',  '../uploads/');

// ─────────────────────────────────────────────
//  UPLOAD LIMITS
// ─────────────────────────────────────────────
define('ALLOWED_EXTS',  ['zip','rar','exe','msi','apk','pdf','dmg','tar','gz','7z','jar','bin']);
define('MAX_FILE_SIZE', 500 * 1024 * 1024); // 500 MB

// ═══════════════════════════════════════════════
//  INTERNAL BOOTSTRAP — do not edit below
// ═══════════════════════════════════════════════

// ── Helpers ────────────────────────────────────
function formatBytes(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return round($bytes / 1048576, 2)    . ' MB';
    if ($bytes >= 1024)       return round($bytes / 1024, 2)       . ' KB';
    return $bytes . ' B';
}

function generateId(): string {
    return bin2hex(random_bytes(8));
}

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] === true;
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) { header('Location: index.php'); exit; }
}

// ── DB Singleton ───────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset='.DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function ensureTable(): void {
    getDB()->exec("
        CREATE TABLE IF NOT EXISTS `pd_files` (
            `id`            VARCHAR(16)  NOT NULL PRIMARY KEY,
            `name`          VARCHAR(255) NOT NULL,
            `version`       VARCHAR(50)  NOT NULL DEFAULT '1.0.0',
            `icon`          VARCHAR(10)  NOT NULL DEFAULT '📦',
            `description`   TEXT         DEFAULT NULL,
            `filename`      VARCHAR(255) NOT NULL,
            `original_name` VARCHAR(255) DEFAULT NULL,
            `size`          BIGINT       NOT NULL DEFAULT 0,
            `size_formatted`VARCHAR(30)  NOT NULL DEFAULT '',
            `downloads`     INT          NOT NULL DEFAULT 0,
            `uploaded_at`   INT          NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

// ── Unified CRUD ───────────────────────────────
function loadFiles(): array {
    if (STORAGE_MODE === 'mysql') {
        try {
            ensureTable();
            return getDB()->query("SELECT * FROM pd_files ORDER BY uploaded_at DESC")->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    // JSON
    if (!file_exists(DATA_FILE)) return [];
    return json_decode(file_get_contents(DATA_FILE), true) ?? [];
}

function saveFiles(array $files): void {
    // JSON mode only (MySQL writes are done per-record)
    if (STORAGE_MODE !== 'mysql') {
        file_put_contents(DATA_FILE, json_encode(array_values($files), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

function insertFile(array $f): void {
    if (STORAGE_MODE === 'mysql') {
        ensureTable();
        $s = getDB()->prepare("
            INSERT INTO pd_files
              (id,name,version,icon,description,filename,original_name,size,size_formatted,downloads,uploaded_at)
            VALUES
              (:id,:name,:version,:icon,:description,:filename,:original_name,:size,:size_formatted,0,:uploaded_at)
        ");
        $s->execute([
            ':id'            => $f['id'],
            ':name'          => $f['name'],
            ':version'       => $f['version'],
            ':icon'          => $f['icon'],
            ':description'   => $f['description'],
            ':filename'      => $f['filename'],
            ':original_name' => $f['original_name'],
            ':size'          => $f['size'],
            ':size_formatted'=> $f['size_formatted'],
            ':uploaded_at'   => $f['uploaded_at'],
        ]);
    } else {
        $files = loadFiles();
        $files[] = $f;
        saveFiles($files);
    }
}

function deleteFile(string $id): ?string {
    if (STORAGE_MODE === 'mysql') {
        ensureTable();
        $row = getDB()->prepare("SELECT filename FROM pd_files WHERE id = ?");
        $row->execute([$id]);
        $file = $row->fetch();
        if ($file) {
            $fp = UPLOADS_DIR . $file['filename'];
            if (file_exists($fp)) @unlink($fp);
            getDB()->prepare("DELETE FROM pd_files WHERE id = ?")->execute([$id]);
        }
        return $file['filename'] ?? null;
    } else {
        $files = loadFiles();
        $keep  = [];
        foreach ($files as $f) {
            if ($f['id'] === $id) {
                $fp = UPLOADS_DIR . ($f['filename'] ?? '');
                if (file_exists($fp)) @unlink($fp);
            } else {
                $keep[] = $f;
            }
        }
        saveFiles($keep);
        return null;
    }
}

function incrementDownload(string $id): void {
    if (STORAGE_MODE === 'mysql') {
        ensureTable();
        getDB()->prepare("UPDATE pd_files SET downloads = downloads + 1 WHERE id = ?")->execute([$id]);
    } else {
        $files = loadFiles();
        foreach ($files as &$f) {
            if ($f['id'] === $id) { $f['downloads'] = ($f['downloads'] ?? 0) + 1; break; }
        }
        saveFiles($files);
    }
}

function getFileById(string $id): ?array {
    if (STORAGE_MODE === 'mysql') {
        ensureTable();
        $s = getDB()->prepare("SELECT * FROM pd_files WHERE id = ?");
        $s->execute([$id]);
        $r = $s->fetch();
        return $r ?: null;
    } else {
        foreach (loadFiles() as $f) {
            if ($f['id'] === $id) return $f;
        }
        return null;
    }
}
