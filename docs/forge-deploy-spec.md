# Forge deployment spec — status.hrconnectum.com (Cachet v3)

A prompt/spec for **forge-manager** to provision the hrConnectum status page as a new
Laravel Forge site via the Forge API. Hand this to the forge-manager agent (it runs in
`D:/Code/hrConnectum/forge-manager`, with `FORGE_API_KEY` and Cloudflare creds in its `.env`).

---

## Agent context

- Operate inside the **forge-manager** repo. Reuse `lib/forge-api.js`
  (`forgeGet` / `forgePost` / `forgePut`, `issueLetsEncrypt`, `pollCertificate`, `poll`)
  and `lib/cloudflare-api.js`, exactly the way `scripts/create-sites.js` already does.
- This deploys an **existing GitHub repo onto an existing server**. Do NOT create a server.
- Recommended: add a `scripts/create-status-site.js` modeled on `create-sites.js`,
  idempotent and re-runnable, and log progress in `status.md`.

### Target server (already provisioned)

| Field | Value |
|---|---|
| Forge name | `celestial-frost` |
| Forge Server ID | **1214005** |
| IP | **23.88.1.18** |
| Stack | Ubuntu 24.04, PHP 8.5, MySQL 8.4 |

This is the live app host (also runs inga.one, staging.hrconnectum.com, pma).

### ⚠️ Critical: PHP 8.4+ is mandatory

Cachet v3's committed `composer.lock` pins Symfony 8, which requires **PHP >= 8.4.1**.
PHP 8.3 will fatally error at runtime. The server already runs **PHP 8.5**, which satisfies this.

- Set the **site's** `php_version` to `php84` if installed, otherwise `php85`.
- Check what's available: `forge.forgeGet('/servers/1214005/php')`. If you want 8.4 and it is
  missing: `forge.forgePost('/servers/1214005/php', { version: 'php84' })` and wait for install.
- Every `composer` / `artisan` invocation (deploy script, queue worker, scheduler) MUST run on
  that version. In the deploy script use Forge's `$FORGE_PHP` variable (resolves to the site's
  binary). For the scheduler job, use the explicit binary (`php8.4` or `php8.5`).

---

## Target site parameters

| Field | Value |
|---|---|
| Domain | `status.hrconnectum.com` |
| project_type | `php` |
| Web directory | `/public` |
| php_version | `php84` (fallback `php85`) |
| Git repository | `hrConnectum/status.hrconnectum.com` (GitHub) |
| Branch | **`production`** |
| Database name / user | `status_hrconnectum` / `status_hrconnectum` |
| Cloudflare zone | `hrconnectum.com` |

---

## Steps (Forge API)

### 1. Create the site
```js
const site = await forge.forgePost(`/servers/1214005/sites`, {
  domain: 'status.hrconnectum.com',
  project_type: 'php',
  directory: '/public',
  php_version: 'php84', // or 'php85'
});
const siteId = site.site.id;
```

### 2. Create the database and user
```js
await forge.forgePost(`/servers/1214005/databases`, {
  name: 'status_hrconnectum',
  user: 'status_hrconnectum',
  password: process.env.FORGE_DATABASE_PASSWORD, // or a freshly generated strong password
});
```
Record the DB password; it goes into `.env` (step 4).

### 3. Connect the Git repository
```js
await forge.forgePost(`/servers/1214005/sites/${siteId}/git`, {
  provider: 'github',
  repository: 'hrConnectum/status.hrconnectum.com',
  branch: 'production',
  composer: false, // we run composer (with the right PHP + --no-dev) in the deploy script
});
```
The server's `forge` user SSH key must be a deploy key on the repo. If the repo is private and
the install fails on auth, add the server key to the GitHub repo's deploy keys (or use a
machine user with read access), then retry.

### 4. Set the production `.env`
Generate an app key once and paste it in (do **not** put `key:generate` in the deploy script,
it would rotate the key every deploy). Locally: `php artisan key:generate --show`.
```js
await forge.forgePut(`/servers/1214005/sites/${siteId}/env`, { content: ENV_BELOW });
```

### 5. Set the deploy script + enable quick deploy
```js
await forge.forgePut(`/servers/1214005/sites/${siteId}/deployment/script`, { content: DEPLOY_BELOW });
await forge.forgePost(`/servers/1214005/sites/${siteId}/deployment`, {}); // enable quick deploy
```

### 6. Queue worker (Cachet sends webhooks/notifications via the queue)
```js
await forge.forgePost(`/servers/1214005/sites/${siteId}/workers`, {
  connection: 'database',
  queue: 'default,webhooks',
  timeout: 60, sleep: 3, tries: 3, processes: 1,
  daemon: true,
  php_version: 'php84', // or php85
});
```

### 7. Scheduler (Laravel cron)
```js
await forge.forgePost(`/servers/1214005/jobs`, {
  command: 'php8.4 /home/forge/status.hrconnectum.com/artisan schedule:run',
  frequency: 'minutely',
  user: 'forge',
});
```

### 8. DNS (Cloudflare, zone `hrconnectum.com`)
Back up the zone first (as `create-sites.js` does), then create a proxied A record:
`status.hrconnectum.com -> 23.88.1.18` (orange cloud, like the other hrconnectum sites).

### 9. SSL (Let's Encrypt)
```js
const cert = await forge.issueLetsEncrypt(1214005, siteId, ['status.hrconnectum.com']);
await forge.pollCertificate(1214005, siteId, cert.certificate.id);
```
If issuance fails because DNS has not propagated, log it and enable SSL later
(same as `create-sites.js`). Cloudflare proxying is fine with Forge's HTTP-01 challenge;
alternatively use a Cloudflare Origin certificate.

### 10. First deploy
```js
await forge.forgePost(`/servers/1214005/sites/${siteId}/deployment/deploy`, {});
// poll: forge.forgeGet(`/servers/1214005/sites/${siteId}`) until deployment_status is null/finished
```

### 11. Create the admin user (one-time, post first successful deploy)
```js
await forge.forgePost(`/servers/1214005/sites/${siteId}/commands`, {
  command: `php8.4 artisan cachet:make:user admin@hrconnectum.com --name "hrConnectum Admin" --password '<STRONG_PASSWORD>' --no-interaction`,
});
```
Use a strong password and store it in the team password manager, not in this file.

---

## Production `.env`

```dotenv
APP_NAME="hrConnectum Status"
APP_ENV=production
APP_KEY=base64:__GENERATE_ONCE_AND_PASTE__
APP_DEBUG=false
APP_TIMEZONE=Europe/Istanbul
APP_URL=https://status.hrconnectum.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=status_hrconnectum
DB_USERNAME=status_hrconnectum
DB_PASSWORD=__DB_PASSWORD_FROM_STEP_2__

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

QUEUE_CONNECTION=database
CACHE_STORE=database
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

# Outbound mail for incident notifications (MailerSend / mailcow relay).
MAIL_MAILER=smtp
MAIL_HOST=__SET_ME__
MAIL_PORT=587
MAIL_USERNAME=__SET_ME__
MAIL_PASSWORD=__SET_ME__
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="status@hrconnectum.com"
MAIL_FROM_NAME="${APP_NAME}"

# hrConnectum branding (drives BrandingSeeder).
HRC_ACCENT=indigo

# Cachet behind the Cloudflare proxy: trust forwarded scheme/IP headers.
CACHET_BEACON=false
CACHET_EMOJI=false
CACHET_PATH=/
CACHET_TRUSTED_PROXIES=*

NIGHTWATCH_ENABLED=false
```

---

## Deploy script

```bash
cd /home/forge/status.hrconnectum.com

git pull origin $FORGE_SITE_BRANCH

# Cachet v3 requires PHP 8.4+. $FORGE_PHP is the site's PHP binary (php8.4 / php8.5).
$FORGE_PHP /usr/local/bin/composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan db:seed --class=BrandingSeeder --force
$FORGE_PHP artisan storage:link || true

$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan view:cache

$FORGE_PHP artisan queue:restart

( flock -w 10 9 || exit 1
  echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
```

> `BrandingSeeder` is idempotent and re-applies the indigo hrConnectum branding on every
> deploy. If you later want admins to manage these settings from the dashboard instead,
> remove the `db:seed` line after the first successful deploy.

---

## Verification (after deploy + DNS + SSL)

```bash
curl -sI https://status.hrconnectum.com/ | grep -iE 'HTTP/|x-powered-by'
#   expect: HTTP/2 200   and   x-powered-by: PHP/8.4.x or 8.5.x

curl -s https://status.hrconnectum.com/ | grep -oiE '<title>[^<]*</title>|--accent: #212154'
#   expect the hrConnectum title and the indigo accent (#212154)
```

- `https://status.hrconnectum.com` shows the indigo hrConnectum status page over HTTPS.
- `https://status.hrconnectum.com/dashboard` loads the login; the admin user from step 11 works.
- Queue worker is running (Forge > site > Workers) and the scheduler job exists (Forge > Scheduler).

---

## Notes / gotchas

- **PHP 8.4+ everywhere.** Site `php_version`, deploy (`$FORGE_PHP`), worker (`php_version`),
  scheduler (`php8.4` binary). An 8.3 default will 500 the whole site.
- **`production` branch**, not `3.x`. `3.x` is the clean upstream mirror; `production` carries
  the hrConnectum customizations and is what Forge deploys.
- **Cloudflare-proxied**, so `CACHET_TRUSTED_PROXIES=*` and `APP_URL=https://...` are required
  for correct links and secure cookies.
- **Never commit `.env`**; it lives only on the server.
- The branding (indigo, logo, footer, favicon) is applied entirely by `BrandingSeeder` in the
  deploy script plus the repo's published views, so a fresh deploy is fully branded with no
  manual dashboard steps.
```
