# TMI Archive (Laravel)

An archive of talks and texts from the late Culadasa, author of *The Mind Illuminated*.
This is the Laravel + MySQL rewrite of the original Django + PostgreSQL application.

## Stack

- **Laravel 13** (PHP 8.4)
- **MySQL / MariaDB**
- **Filament 4** admin panel (`/admin`)
- **Bootstrap 5** front-end (no build step — loaded via CDN)
- **DDEV** for local development

## Local development

```bash
ddev start
ddev composer install
ddev artisan key:generate      # if APP_KEY is empty
ddev artisan migrate
# Load the archive data (see "Data migration" below):
ddev import-db --file=database/data/tmi-archive-data.sql   # or: mysql ... < database/data/tmi-archive-data.sql
```

The site is served from `public/` (see `.ddev/config.yaml`). Visit `https://tmi-archive.ddev.site`.

## Data migration (Postgres → MySQL)

The original data lives in a PostgreSQL data-only dump, `db-dump.psql`. It is converted
into a MySQL population script by a standalone Python script:

```bash
python3 tools/psql_to_mysql.py        # reads db-dump.psql, writes database/data/tmi-archive-data.sql
```

The generated `database/data/tmi-archive-data.sql`:

- Populates `users`, `talks`, `playlists`, `playlist_talk`, `talk_metrics`.
- Is **idempotent** (each table is `DELETE`d then re-inserted) and wraps the load in
  `SET FOREIGN_KEY_CHECKS=0` / `SET time_zone='+00:00'` so it is portable across servers.
- Preserves original primary keys and the Django `pbkdf2_sha256` password hashes.

### Populating the live database

1. Deploy the code and run `php artisan migrate` to create the schema.
2. Run the population script against the live MySQL database:

   ```bash
   mysql -h <host> -u <user> -p <database> < database/data/tmi-archive-data.sql
   ```

> `db-dump.psql` and the generated `.sql` contain personal data (emails, password
> hashes) and are git-ignored. Regenerate the SQL from the dump as needed.

### Passwords

Imported users keep their Django `pbkdf2_sha256` hashes. `App\Support\Hashing\DjangoPbkdf2Hasher`
(registered as the default hasher) verifies those transparently and re-hashes to bcrypt the
next time a password is set. New passwords use bcrypt.

## Admin

Filament panel at `/admin`, restricted to users with `is_admin = 1` (the old Django
`is_staff` / `is_superuser`). It provides:

- **Talks** and **Playlists** resources (create / edit / delete).
- A **metrics dashboard**: totals, unique visitors, bot-filtered view/download trends
  by month, and a most-popular-talks table.

## Public endpoints

The old `/api/v1/*` API has been removed. The download and transcript endpoints moved to:

| Purpose | Endpoint |
|---|---|
| Download audio (cleaned, original fallback) | `GET /talks/{talk}/download` |
| Download original audio | `GET /talks/{talk}/download/original` |
| Player-formatted transcript (text) | `GET /talks/{talk}/transcription` |
| Structured transcript (JSON) | `GET /talks/{talk}/transcription.json` |

Audio is served by redirecting to the media host (`MEDIA_BASE_URL`); set `MEDIA_ROOT`
to stream from the local filesystem instead.

## Bot / spam mitigation

- View & download metrics are **bot-filtered** (`App\Support\BotDetector`) — automated
  traffic is stored but flagged `is_bot` and excluded from all stats.
- Repeated hits from the same client are **de-duplicated** within a 30-minute window.
- Download / transcript endpoints and the whole site are **rate-limited** per IP.
- `robots.txt` blocks heavy endpoints and known AI/SEO scrapers.
- Trusted-proxy handling ensures real client IPs behind the load balancer.

## Tests

```bash
php artisan test
```

Covers public pages, search, metric tracking, bot filtering & de-duplication,
download redirects, transcript endpoints, Django password verification, and admin
access control.
