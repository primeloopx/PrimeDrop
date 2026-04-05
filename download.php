<?php
require_once __DIR__ . '/config/app.php';

$id = trim($_GET['id'] ?? '');
if (!$id) { header('Location: index.php'); exit; }

$file = getFileById($id);

if (!$file) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Not Found</title>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@600;800&display=swap" rel="stylesheet">
    </head><body style="font-family:Figtree,sans-serif;background:#f7f7f5;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:20px">
    <div><div style="font-size:2.5rem;margin-bottom:12px">📭</div>
    <h2 style="font-size:1.2rem;font-weight:800;color:#111110;margin-bottom:8px">File Not Found</h2>
    <p style="color:#7c7c72;font-size:.9rem;margin-bottom:20px">This file may have been removed.</p>
    <a href="index.php" style="background:#84cc16;color:#fff;font-weight:800;padding:10px 20px;border-radius:8px;text-decoration:none;font-size:.88rem">← Go Back</a>
    </div></body></html>';
    exit;
}

$filepath = UPLOADS_DIR . $file['filename'];

if (!file_exists($filepath)) {
    http_response_code(410);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>File Missing</title>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@600;800&display=swap" rel="stylesheet">
    </head><body style="font-family:Figtree,sans-serif;background:#f7f7f5;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:20px">
    <div><div style="font-size:2.5rem;margin-bottom:12px">⚠️</div>
    <h2 style="font-size:1.2rem;font-weight:800;color:#111110;margin-bottom:8px">File Missing</h2>
    <p style="color:#7c7c72;font-size:.9rem;margin-bottom:20px">File was registered but the upload is gone from the server.</p>
    <a href="index.php" style="background:#84cc16;color:#fff;font-weight:800;padding:10px 20px;border-radius:8px;text-decoration:none;font-size:.88rem">← Go Back</a>
    </div></body></html>';
    exit;
}

// Increment counter
incrementDownload($id);

// Serve file
$ext      = pathinfo($file['filename'], PATHINFO_EXTENSION);
$safeName = preg_replace('/[^a-zA-Z0-9._\- ]/', '_', $file['name']) . '.' . $ext;
$mime     = mime_content_type($filepath) ?: 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $safeName . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, no-store');
header('Pragma: no-cache');
readfile($filepath);
exit;
