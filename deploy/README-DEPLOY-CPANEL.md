Deployment package for cPanel

Contents:
- public_html/ (web root)
- natstock/ (Laravel app)

Post-upload steps on server:
1) Create database and user, update natstock/.env with DB credentials.
2) Run installer once: https://YOUR_DOMAIN/install.php (creates APP_KEY, runs migrate and seeds, caches config/routes).
3) Remove install.php from public_html for security.
