# Deploying AskBox to `askbox.yourdomain.dev`

These steps are for standard shared hosting with cPanel or hPanel (Namecheap, Hostinger and similar). Allow about 10 minutes.

## 1. Create the subdomain
1. In cPanel, open **Domains → Subdomains** (in Hostinger's hPanel: **Domains → Subdomains**).
2. Subdomain: `askbox`. Domain: `ejikepius.dev`.
3. **Set the document root to the `public` folder**, for example `askbox.ejikepius.dev/public`.
   This matters: it keeps `src/`, `views/` and `data/` out of public reach.

## 2. Upload the files
1. Open **File Manager** and go to the folder created for the subdomain (e.g. `askbox.ejikepius.dev/`).
2. Upload `askbox.zip` and **Extract** it, so that `public/`, `src/`, `views/` and `data/` sit directly inside that folder.
3. Make sure `data/` is writable (permissions `755`, or `775` if writes fail).

## 3. Send all requests to index.php
`public/.htaccess` is already included. It contains:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

## 4. PHP settings
In **Select PHP Version** (cPanel) or **PHP Configuration** (hPanel):
- PHP **8.1 or newer**
- Extensions enabled: `pdo_sqlite`, `gd`, `mbstring`

## 5. Configure
In `src/config.php`:
- `'app_url' => 'https://askbox.ejikepius.dev'` so share cards and links always show your real domain
- `'github_url'` → your repo URL

## 6. HTTPS
`.dev` domains **require** HTTPS. Turn on the free SSL certificate (cPanel: **SSL/TLS Status → Run AutoSSL**; hPanel: **Security → SSL**) for the subdomain.

## 7. Test it
- Open https://askbox.ejikepius.dev, sign up and claim `@ejike_pius`
- Ask yourself a question from a private window, then answer it
- Paste an answer link into a draft X post and check that the preview card appears

The next projects follow the same steps with a new subdomain (`linksnip.ejikepius.dev`, and so on).
