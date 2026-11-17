# LaunchPad

A private dashboard for the client sites deployed on our VPS. Each site shows its status (`live`, `slow` or `down`), the version it reports, and a fresh server-side screenshot of its homepage.

Checks run when the dashboard opens and when you press **Refresh**. Nothing runs on a timer. See [REQUIREMENTS.md](REQUIREMENTS.md) for the full spec.

## Stack

Laravel 13 · Inertia 3 · Vue 3 · Vite · Tailwind 4 · Playwright (server-side screenshots) · Pest

Site records live in `storage/app/sites.json`. The database only holds users, sessions and cache (SQLite by default).

## Setup

```bash
composer install
npm install
npx playwright install chromium
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

Create the first account (there is no public sign-up):

```bash
php artisan launchpad:user
```

It prompts for name, email and password. You can also pass `--name`, `--email` and `--password`.

## Configuration

| `.env` key                    | Default | Meaning                                                              |
| ----------------------------- | ------- | -------------------------------------------------------------------- |
| `LAUNCHPAD_SLOW_THRESHOLD_MS` | `1500`  | Responses slower than this are `slow`.                               |
| `LAUNCHPAD_TIMEOUT_MS`        | `8000`  | Per-request timeout.                                                 |
| `LAUNCHPAD_RETRIES`           | `3`     | Attempts before a site is marked `down`.                             |
| `LAUNCHPAD_SAMPLES`           | `2`     | A slow answer is measured up to this many times; the fastest counts. |
| `NODE_BINARY`                 | `node`  | Node used to run the Playwright screenshot script.                   |

Other settings, such as file paths and backoff, are in `config/launchpad.php`.

## Deploying Paylance sites

Sites with a **client folder** can be deployed from their quick look. LaunchPad starts the Paylance repository's _Deploy to VPS_ GitHub Actions workflow with three choices: the tag (every tag in the repository, newest selected), the site's client folder, and whether to run tests first. It doesn't follow the run; the toast links to the workflow's runs on GitHub.

1. Create a [fine-grained personal access token](https://github.com/settings/personal-access-tokens/new) with access to **only** the Paylance repository and these permissions:
    - **Actions:** Read and write (to start the workflow)
    - **Contents:** Read-only (to list tags)
2. Add it to `.env`:

    ```dotenv
    LAUNCHPAD_DEPLOY_REPOSITORY=charuyasas/paylance
    LAUNCHPAD_DEPLOY_WORKFLOW=deploy-on-main.yml
    LAUNCHPAD_GITHUB_TOKEN=github_pat_…
    ```

3. Edit each Paylance-based site and fill in its **Client folder** (the `client` input of the workflow, e.g. `paylance`, `rasangi`).

Only tags that exist in the repository can be deployed. Each site remembers its last deploy (tag, tests on/off, who, when). The token stays on the server and is never sent to the browser.

## Development

```bash
composer dev
```

Checks:

```bash
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
npm run check
npm run types:check
```

## Layout

Feature code follows the Paylance module layout:

```
app/Dashboard/modules/Sites/
├── Entities/        Site
├── UseCases/        register, update, delete, refresh, status rules
├── IO/Http/         controller, request, HTTP transport
├── IO/Storage/      JSON repository
├── IO/Screenshots/  Playwright screenshotter
└── Tests/           Unit + Feature (Pest)
```

The Vue pages are in `resources/js/pages` and the dashboard components are in `resources/js/components/sites`.
