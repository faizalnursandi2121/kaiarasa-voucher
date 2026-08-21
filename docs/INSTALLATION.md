# Kaiarasa Installation Guide

This guide covers installation on various platforms. Kaiarasa is designed to be lightweight and runs on almost any PHP-capable server.

## General Requirements
*   **PHP**: 8.0 or higher
*   **Extensions**: `sqlite3`, `openssl`, `mbstring`, `json`
*   **Database**: SQLite (File based, no server needed)

---

## Docker (Recommended)
The easiest way to run Kaiarasa.

1.  **Build & Run**
    ```bash
    docker-compose up -d --build
    ```
    Go to `http://localhost:8080`
    
2.  **Manual Pull (Alternative)**
    If you prefer to pull the image manually:
    ```bash
    docker pull ghcr.io/kaiarasa/kaiarasa:latest  # Stable
    docker pull ghcr.io/kaiarasa/kaiarasa:v1.0.0  # Specific Version
    docker pull ghcr.io/kaiarasa/kaiarasa:edge    # Bleeding Edge
    ```

*Note: The database is persisted in `app/Database` via volumes.*

---

## Apache / OpenLiteSpeed
1.  **Document Root**: Set your web server's document root to the `public/` folder.
2.  **Rewrite Rules**: Ensure `mod_rewrite` is enabled. Kaiarasa includes a `.htaccess` file in `public/` that handles URL routing automatically.
3.  **Permissions**: Ensure the web server user (e.g., `www-data`) has **write** access to:
    *   `app/Database/` (directory and file)
    *   `app/Config/` (if using installer)
    *   `.env` file

---

## Nginx
Nginx does not read `.htaccess`. Use this configuration block in your `server` block:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/kaiarasa/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock; # Adjust version
    }

    location ~ /\.ht {
        deny all;
    }
}
```

---

## IIS (Windows)
1.  **Document Root**: Point the site to the `public/` folder.
2.  **Web Config**: A `web.config` file has been provided in `public/` to handle URL Rewriting.
3.  **Requirements**: Ensure **URL Rewrite Module 2.0** is installed on IIS.

---

## STB / Android (Awebserver / Termux)

### Awebserver
1.  Copy the Kaiarasa files to `/htdocs`.
2.  Point the document root to `public` if supported, or access via `http://localhost:8080/public`.
3.  Ensure PHP version is compatible.

### Termux
1.  Install PHP: `pkg install php`
2.  Navigate to Kaiarasa directory: `cd kaiarasa`
3.  Use the built-in server:
    ```bash
    php kaiarasa serve --host=0.0.0.0 --port=8080
    ```
4.  Access via browser.

---


---

## Shared Hosting (cPanel / DirectAdmin)
Most shared hosting uses Apache or OpenLiteSpeed, which is fully compatible.

1.  **Upload Files**: Upload the Kaiarasa files to `public_html/kaiarasa` (or a subdomain folder).
2.  **Point Domain**:
    *   **Recommended**: Go to "Domains" or "Subdomains" in cPanel and set the **Document Root** to point strictly to the `public/` folder (e.g., `public_html/kaiarasa/public`).
    *   **Alternative**: If you cannot change Document Root, you can move contents of `public/` to the root `public_html` and move `app/`, `routes/`, etc. one level up (not recommended for security).
3.  **PHP Version**: Select PHP 8.0+ in "Select PHP Version" menu.
4.  **Extensions**: Ensure `sqlite3` and `fileinfo` are checked.

---

## aaPanel (VPS)
1.  **Create Website**: Add site -> PHP-8.x.
2.  **Site Directory**:
    *   Set **Running Directory** (bukan Site Directory) to `/public`.
    *   Uncheck "Anti-XSS" (sometimes blocks config saving).
3.  **URL Rewrite**: Select `thinkphp` or `laravel` template (compatible) OR just use the Nginx config provided above.
4.  **Permissions**: Chown `www` user to the site directory.

---

## PaaS Cloud (Railway / Render / Heroku)
**WARNING**: Kaiarasa uses SQLite (File Database). Most PaaS cloud have **Ephemeral Filesytem** (Reset on restart).

*   **Requirement**: You MUST mount a **Persistent Volume/Disk**.
*   **Mount Path**: Mount your volume to `/var/www/html/app/Database` (or wherever you put Kaiarasa).
*   **Docker**: Use the Docker deployment method, it works natively on these platforms.

---

## Post-Installation
After setting up the server:
1.  Copy `.env.example` to `.env` (if not already done).
2.  **Install Application**
    *   **Option A: CLI**
        Run `php kaiarasa install` in your terminal.
    *   **Option B: Web Installer**
        Open `http://your-domain.com/install` in your browser.
