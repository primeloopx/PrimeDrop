# PrimeDrop — Mini Download Hub

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://www.php.net/)  
[![License](https://img.shields.io/badge/License-Free-orange)](https://github.com/PrimeTriple4/PrimeDrop/blob/main/LICENSE)

---

## Overview
**PrimeDrop** is a mini link-in-bio download platform by **PrimeTriple4** under **PrimeLoopX Studios**.  
Users can tag this web link in their social media bio, and visitors can directly access multiple downloadable files like apps, games, and tools — **no signup, no payment required**.  

It is flexible:  
- Works **without any database** using JSON (`data/files.json`)  
- Can optionally use a **database for hybrid control** (MySQL, MariaDB, or SQLite) if you need advanced management  

Perfect for modders, content creators, or anyone sharing files online.

---

## Features
- Acts as a **mini personal download hub / link-in-bio platform**  
- Users can showcase multiple files like MSI, APKs, PC tools, or mods  
- **Direct downloads with no login or payment**  
- Works **without database** (JSON-based storage)  
- Optionally supports **hybrid database control** for advanced file management  
- Easy admin panel to upload/manage files  
- Supports large files (default 500MB)  
- Session-based admin authentication with offline key  
- File downloads served via PHP (keeps URLs hidden from direct access)  

---

## Setup (cPanel / Shared Hosting)

1. Upload **all files** to your `public_html` (or a subdirectory)  
2. Set folder permissions:
   - `uploads/` → `755` (or `775`)  
   - `data/` → `755`  
   - `data/files.json` → `644`  

3. Open `data/config.php` and configure:
```php
define('ADMIN_KEY', 'your-secret-key');       // Admin offline password
define('APP_NAME', 'PrimeDrop');              // App name shown on pages
define('APP_DEV_NAME', 'PrimeTriple4');       // Developer name in footer
define('APP_COPYRIGHT_NAME', 'PrimeLoopX');   // Copyright name
