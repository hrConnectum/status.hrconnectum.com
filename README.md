<!-- ════════════════════════════════════════════════════════════════════════
     hrConnectum customizations. This section is maintained by hrConnectum and
     kept at the top of the file so upstream merges (which touch the content
     below) rarely conflict here. Everything below this block is upstream Cachet.
     ════════════════════════════════════════════════════════════════════════ -->

# status.hrconnectum.com

Self-hosted, hrConnectum-branded [Cachet v3](https://github.com/cachethq/cachet) status page.

This repo is a **fork of `cachethq/cachet`** (the Laravel application *shell*). All the real
status-page logic lives in the `cachethq/core` Composer package, which we never edit. We only
touch the **additive layer** so upstream updates stay painless:

| Layer | Where | Notes |
|---|---|---|
| Env / config | `.env`, `config/cachet.php`, `config/hrconnectum.php` | App name, URL, mail, brand palette |
| Branding settings | `database/seeders/BrandingSeeder.php` | Writes Cachet settings (theme accent, custom CSS, footer, favicon) |
| Logo | `resources/views/vendor/cachet/components/logo.blade.php`, `logomark.blade.php` + `public/vendor/hrconnectum/` | Published view override + our own assets |

We **never** edit `vendor/cachethq/core` or core migrations.

### ⚠️ PHP 8.4 required

Cachet's committed `composer.lock` pins Symfony 8 components, which require **PHP >= 8.4.1**.
Locally (Laragon) this site runs on `php-8.4.5` via a per-vhost fcgid wrapper; the rest of the
machine can stay on 8.3. On Forge, set the site's PHP version to **8.4**.

### Local setup (Laragon)

```bash
composer install                 # run with PHP 8.4
cp .env.example .env             # then set DB_* to MySQL: database status_hrconnectum
php artisan key:generate
php artisan migrate
php artisan cachet:make:user admin@hrconnectum.com --name "hrConnectum Admin" --password "<pw>" --no-interaction
php artisan db:seed --class=BrandingSeeder --force   # applies hrConnectum branding
php artisan storage:link
```

Local URL: `http://status.hrconnectum.test` (Apache vhost at
`C:/laragon/etc/apache2/sites-enabled/status.hrconnectum.test.conf`, with a PHP-8.4 fcgid wrapper).
Dashboard: `/dashboard`.

### Change the brand colour

Brand colour is driven by `config/hrconnectum.php` (palettes: `teal`, `indigo`). Switch via
`HRC_ACCENT` in `.env` (or change the default), then re-seed:

```bash
php artisan db:seed --class=BrandingSeeder --force && php artisan optimize:clear
```

Exact hrConnectum hex values are injected as custom CSS that overrides Cachet's named theme.

### Update workflow

**Shell (this repo) from upstream Cachet** — note upstream's default branch is `3.x`, not `main`:

```bash
git fetch upstream
git checkout -b update/<date> 3.x
git merge upstream/3.x          # conflicts should be minimal — we only touch the additive layer
composer install                # PHP 8.4
php artisan migrate
# test locally, then merge to the production branch and push (Forge auto-deploys)
```

**Core package only:**

```bash
composer update cachethq/core
php artisan migrate
# commit the updated composer.lock, push, deploy
```

### Deploy (Laravel Forge)

PHP 8.4 site, private repo `hrConnectum/status.hrconnectum.com`. Deploy script: `composer install
--no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan db:seed
--class=BrandingSeeder --force`, `php artisan config:cache route:cache view:cache`, restart queue.
Provision a queue worker and the scheduler cron per Cachet docs. `.env` lives on the server (never
committed).

Full Forge-API provisioning runbook (target server, exact API calls, `.env`, deploy script,
workers, SSL): [docs/forge-deploy-spec.md](docs/forge-deploy-spec.md).

---

<p align="center">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://cachethq.io/assets/cachet-logo-dark.svg">
      <img alt="Cachet Logo" src="https://cachethq.io/assets/cachet-logo-light.svg">
    </picture>
</p>

Cachet, the open-source self-hosted status page system.

## Cachet 3.x Announcement

For more information on the Cachet rebuild and our plans for 3.x, you can read the announcement [here](https://github.com/CachetHQ/Cachet/discussions/4342).

## Requirements

- PHP 8.2 or later
- [Composer](https://getcomposer.org)
- A supported database: MariaDB, MySQL, PostgreSQL or SQLite

## Installation, Upgrades and Documentation

You can find documentation at [https://docs.cachethq.io](https://docs.cachethq.io).

Here are some useful quick links:

- [Installing Cachet](https://docs.cachethq.io/v3.x/installation)

### Demo

To test out the v3 demo, you can log in to the [Cachet dashboard](https://v3.cachethq.io/dashboard) with the following credentials:

- **Email:** `test@test.com`
- **Password:** `test123`

> **Note**
> The demo will automatically reset every 30 minutes.
> 
## Sponsors

<p align="center">
    <a href="https://jump24.co.uk"><img width="100px" src="https://github.com/jumptwentyfour.png" alt="Jump24"></a>
    <a href="https://dreamtilt.com.au"><img width="100px" src="https://github.com/dreamtilt.png" alt="Dreamtilt"></a>
    <a href="https://xyphen-it.nl"><img width="100px" src="https://github.com/xyphen-it.png" alt="Xyphen-IT"></a>
    <a href="https://coderabbit.ai/"><img width="100px" src="https://github.com/coderabbitai.png" alt="Code Rabbit"></a>
    <a href="https://scramble.dedoc.co/"><img width="100px" src="https://github.com/dedoc.png" alt="de:doc"></a>
</p>

## Security Vulnerabilities

If you discover a security vulnerability within Cachet, please send an e-mail to [support@cachethq.io](mailto:support@cachethq.io?Cachet%20Security%20Vulnerability). All security vulnerabilities are reviewed on a case-by-case basis.
