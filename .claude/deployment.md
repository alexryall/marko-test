# Deployment

Fly.io deployment via GitHub Actions. Pushes to `main` trigger automatic deploys.

## Infrastructure

- **Platform**: Fly.io
- **Region**: `iad` (US East, Ashburn VA)
- **App name**: `marko-shop`
- **VM**: shared CPU, 256MB RAM
- **Database**: Fly Postgres (attached)
- **Server**: OpenSwoole (boots app once, handles requests from memory)

## Files

- `Dockerfile` — PHP 8.5 CLI with OpenSwoole, pdo_pgsql, pcntl, posix. Runs `bin/swoole-server.php`
- `fly.toml` — Fly app config, port 8000, HTTPS forced, auto-stop/start machines
- `.github/workflows/deploy.yml` — Deploys on push to main via `flyctl`
- `.dockerignore` — Excludes .git, vendor, storage, stitch, .claude, .env from image

## Deploy Flow

1. Push to `main` triggers GitHub Actions
2. Action runs `flyctl deploy --remote-only` (builds Docker image on Fly's builders)
3. `fly.toml` release_command runs `php bin/setup-db.php` to ensure tables exist
4. Container starts with `php bin/swoole-server.php --port=8000 --workers=4`

## One-Time Setup

```bash
# Create app (don't deploy yet)
fly launch --no-deploy

# Create Postgres database
fly postgres create --name marko-shop-db

# Attach database to app (sets DATABASE_URL automatically)
fly postgres attach marko-shop-db

# Set individual DB env vars (from the attach output)
fly secrets set DB_HOST=<host> DB_PORT=5432 DB_DATABASE=<db> DB_USERNAME=<user> DB_PASSWORD=<password>

# Create deploy token and add to GitHub
fly tokens create deploy -x 999999h
# Add as FLY_API_TOKEN in GitHub repo > Settings > Secrets > Actions
```

## Notes

- `setup-db.php` drops and recreates tables on every deploy — fine for a PoC, would need migrations for production
- Auto-stop machines is enabled to stay within free tier when idle
- The Dockerfile excludes `vendor/` via `.dockerignore` and runs `composer install --no-dev` during build for a clean production install
