# Deploy modul

## Stav: Připraveno

## Pipeline
1. Push na `main` → GitHub Actions
2. Build: Composer (--no-dev), Node.js 22 + Vite
3. Smart deploy: lftp, pouze změněné soubory
4. Post-deploy: deploy-hook.php (cache clear, migrace)

## Server
- **Hosting:** Webglobe (vas-hosting.cz)
- **Server:** gve08.vas-server.cz
- **Doména:** rajon.tuptudu.cz
- **DB:** MySQL, localhost

## GitHub Secrets (potřeba nastavit)
- `APP_KEY` — Laravel app key
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — MySQL credentials
- `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` — FTP přístup
- `MIGRATE_TOKEN` — token pro deploy-hook.php
- `MAIL_USERNAME`, `MAIL_PASSWORD` — SMTP credentials
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` — OAuth
- `ANTHROPIC_API_KEY` — Claude API
- `MAPYCZ_API_KEY` — Mapy.cz
- `CRON_TOKEN` — pro cron úlohy

## Soubory
- `.github/workflows/deploy.yml`
- `public/deploy-hook.php`
