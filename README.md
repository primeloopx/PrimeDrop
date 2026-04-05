# VaultDrop — File Sharing Platform
## by PrimeTriple4 | © PrimeTriple4 Studios

---

## SETUP (cPanel Shared Hosting)

1. Upload ALL files to your public_html (or subdirectory)
2. Set folder permissions:
   - uploads/   → 755 (or 775)
   - data/      → 755
   - data/files.json → 644

3. Open `data/config.php` and:
   - Change `ADMIN_KEY` to your secret key
   - Change `APP_NAME`, `APP_DEV_NAME`, `APP_COPYRIGHT_NAME` as needed

4. Visit your site → Admin at `/admin/`

---

## FILE STRUCTURE

```
/
├── index.php           ← Public landing page
├── download.php        ← File download handler
├── .htaccess
│
├── admin/
│   └── index.php       ← Admin panel (login + upload + manage)
│
├── data/
│   ├── config.php      ← App config + helper functions
│   ├── files.json      ← JSON "database"
│   └── .htaccess       ← Blocks direct access
│
└── uploads/            ← Uploaded files stored here
    └── .htaccess       ← Blocks PHP execution
```

---

## CONFIG VARIABLES (data/config.php)

| Variable             | Description                        |
|----------------------|------------------------------------|
| `APP_NAME`           | Platform name shown on all pages   |
| `APP_DEV_NAME`       | Developer name shown in footer     |
| `APP_COPYRIGHT_NAME` | Copyright holder name              |
| `ADMIN_KEY`          | Offline password for admin access  |
| `ALLOWED_EXTS`       | Permitted file extensions          |
| `MAX_FILE_SIZE`      | Max upload size (default 500MB)    |

---

## ALLOWED FILE TYPES (default)
zip, rar, exe, msi, apk, pdf, dmg, tar, gz, 7z, jar

---

## SECURITY NOTES
- `data/` is fully blocked from web access
- `uploads/` has PHP execution disabled
- Admin uses session-based auth after offline key check
- No database required — pure JSON storage
- File downloads served via PHP (no direct uploads URL exposed)

⚠️ Note: This project requires PHP and cannot run directly on GitHub Pages.