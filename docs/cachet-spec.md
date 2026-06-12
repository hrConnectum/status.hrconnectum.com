# Cachet v3 Self-Host Spec - status.hrconnectum.com

## Goal

Set up a self-hosted, customized Cachet v3 status page for hrConnectum, deployed at `status.hrconnectum.com` on Laravel Forge. Customizations must survive upstream updates.

## Critical Architecture Note

Cachet v3 is split into two parts:

- `cachethq/cachet` - the standalone Laravel **application shell** (what you clone and own)
- `cachethq/core` - a **Composer package** with all the real logic (incidents, components, metrics, API, dashboard)

This split is the whole strategy. We own and edit the shell. We never edit core. Core upgrades come through `composer update`. This keeps updates painless.

Caution: Cachet v3 is still in active development and not officially marked production-ready. Pin versions, test every update locally before deploying.

## Repo Strategy

Use a **fork that tracks upstream**. Do not wipe git history (we want security patches).

- Fork `cachethq/cachet` on GitHub into the `hrConnectum` org, named `status.hrconnectum.com`
- Clone the fork locally
- Add upstream remote for future merges:
  - origin -> `git@github.com:hrConnectum/status.hrconnectum.com.git`
  - upstream -> `https://github.com/cachethq/cachet.git`

## Local Setup

- `composer install`
- copy `.env.example` to `.env`
- `php artisan key:generate`
- Configure DB in `.env` (MySQL, English table/column names already handled by the package)
- Run migrations
- Boot locally and confirm dashboard login works before touching anything

## Customization Rules (keep updates clean)

Only touch the **additive layer**. Never edit core package files or core migrations.

Allowed:

- `.env` and `config/` for anything configurable (app name, URL, mail, branding toggles)
- Published views: publish only the Blade views being rebranded, override those copies, leave the rest
- Custom CSS / assets in our own files, not by rewriting package internals
- New features as new files (service classes, separate routes, separate components)

Not allowed:

- Editing files inside `vendor/cachethq/core`
- Editing core migrations
- Inline edits to core controllers or models

If a core view needs changing, publish it first, then edit the published copy.

## Branding Targets

- App name and logo -> hrConnectum
- Primary color / theme via custom CSS
- Favicon and meta
- Footer / support links pointing to hrConnectum
- Default language and timezone

(Keep all of the above in config, env, published views, and custom CSS only.)

## Deployment (Laravel Forge)

Deploy from the `hrConnectum/status.hrconnectum.com` repo, production branch.

- New site on the target server, PHP/Laravel type
- Connect private repo + branch
- `.env` set on the server (never committed)
- Deploy script: composer install (no-dev, optimized), run migrations, cache config/routes/views, restart queue
- Subdomain: `status.hrconnectum.com`
- DNS record + SSL (Let's Encrypt)
- Set up any required daemons (queue worker, scheduler cron) per Cachet docs

## Update Workflow (the important part)

For shell/app updates from upstream:

- `git fetch upstream`
- branch per update: `git checkout -b update/vX.Y`
- `git merge upstream/main` (or rebase)
- resolve conflicts (should be minimal if customization rules were followed)
- test locally, run migrations
- merge to production branch, push
- Forge auto-deploys

For core package updates:

- `composer update cachethq/core` (respecting version constraints)
- test locally, run any new migrations
- commit the updated lock file, push, deploy

## Definition of Done

- Cachet v3 running at `status.hrconnectum.com` over HTTPS
- hrConnectum branding applied entirely through the additive layer
- Zero edits inside the core package
- Documented update steps in the repo README so future upgrades are a known process
