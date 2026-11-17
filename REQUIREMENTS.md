# LaunchPad requirements

## Overview

LaunchPad is a private dashboard for monitoring client site deployments on a VPS. It shows whether each registered site is working, a fresh preview of the homepage, and the deployed version when the site exposes one.

It is a single-user (or small-team) tool, not a public status page. Every screen requires login.

## Features

- **Site cards.** One card per registered site: name, screenshot preview, status pill, version tag when available, and an Open link.
- **Status pill.** `live`, `down`, or `slow`, taken from the last check. A check runs when LaunchPad loads and when the user refreshes. There is no background timer.
- **Retry before down.** A failed check is retried 2–3 times with a short backoff. The site is marked `down` only after those attempts fail.
- **Screenshots.** Previews are server-side Playwright screenshots of the site’s base URL. Iframes are not used. A new screenshot is generated on each LaunchPad load and on manual refresh.
- **Open in browser.** Each card has a one-click link that opens the site’s base URL in a new browser tab.
- **Version.** On load (and on refresh), LaunchPad requests each site’s version endpoint when one is configured. The version is shown on the card. If the path is empty, the version tag is hidden or grayed out.
- **Registration.** A short form with three fields:
    - Site name (required)
    - Base URL (required, HTTPS)
    - Version endpoint path (optional), relative to the base URL
- **Global refresh.** One action re-checks every site: status, version, and a new screenshot.
- **Dark mode.** The dashboard supports dark mode.
- **Auth.** The app is private. Unauthenticated visitors cannot see sites, previews, or status.

## Tech stack

Same shape as Paylance:

- Laravel 13 backend (PHP 8.5)
- Inertia 3
- Vue 3 frontend, built with Vite
- Session login (Laravel auth)

Screenshots are produced by Playwright running on the server (the VPS or the machine hosting LaunchPad), not in the browser.

The stack stays Laravel, Inertia, Vue, and Playwright. Paylance’s other libraries (MySQL, PrimeVue, Echo, permissions) are not part of LaunchPad.

## Development methods

LaunchPad follows Paylance’s clean code architecture, folder structure, and TDD approach. These are how the app is built. They do not change the stack above.

- **Clean architecture.** Feature code is split the way Paylance modules are: entities, use cases, and IO (HTTP in, storage out). Domain rules stay out of controllers and Vue pages.
- **Folder structure.** Same layout as a Paylance module: a feature folder with `Entities`, `UseCases`, `IO/Http`, and colocated `Tests/Unit` and `Tests/Feature`. LaunchPad stays one small app. It does not copy Paylance’s POS, HRM, Board, Repair, or Restaurant domains.
- **TDD.** Tests come first, using Pest, as in Paylance. Unit and feature tests live next to the feature they cover.

## Data model

Site registration data is stored in a JSON file, not SQLite or another database. The file holds each site’s registration fields and the result of the last check (status, version, screenshot path, and checked time) so that data survives restarts.

Each site record:

| Field           | Notes                                                     |
| --------------- | --------------------------------------------------------- |
| id              | Internal id                                               |
| name            | Display name                                              |
| base_url        | HTTPS origin used for the check, the screenshot, and Open |
| version_path    | Optional path. Empty means no version tag                 |
| status          | `live`, `down`, or `slow` from the last check             |
| version         | Last version string, or empty                             |
| screenshot_path | Last captured image                                       |
| checked_at      | When the last check finished                              |

## Behavior

1. User logs in and lands on the dashboard.
2. LaunchPad loads registered sites and, for each one:
    - Requests the base URL (with 2–3 retries and short backoff on failure).
    - Classifies the result as `live`, `slow`, or `down`.
    - If `version_path` is set, fetches it and stores the version string.
    - Captures a Playwright screenshot of the base URL.
3. Cards render from that result. A missing version path leaves the version tag hidden or grayed out.
4. Global refresh repeats step 2 for every site. Per-card refresh, if added, does the same for one site. No scheduled polling.

## Edge cases

- **Mixed content.** Not a problem. Every monitored site is HTTPS, and LaunchPad only stores HTTPS base URLs.
- **X-Frame-Options / frame blocking.** Solved by not embedding sites. Previews are screenshots taken by Playwright on the server.
- **No version endpoint.** Registration allows an empty version path. The card still shows status, preview, and Open. The version tag stays hidden or grayed out.
- **Check failure.** Transient errors are retried before the pill flips to `down`. A slow response is `slow`, not `down`.
- **Screenshot failure.** A failed capture does not by itself mark the site down if the HTTP check succeeded. The card should still show status and Open, with a placeholder where the image would be.

## Decisions

These were open items. The values below are what LaunchPad ships with; the first four can be changed in `.env` or `config/launchpad.php`.

| Item                       | Decision                                                                                                                                                                                                                                                                                                 |
| -------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `live` vs `slow` threshold | A response slower than 1500 ms is `slow` (`LAUNCHPAD_SLOW_THRESHOLD_MS`).                                                                                                                                                                                                                                |
| Slow re-check              | A successful but slow answer is measured once more and the faster sample counts (`LAUNCHPAD_SAMPLES`, default 2), so one blip doesn't mark a site slow. The response time is stored and shown in each site's quick look.                                                                                 |
| Request timeout            | 8000 ms; a request that times out counts as a failed attempt (`LAUNCHPAD_TIMEOUT_MS`).                                                                                                                                                                                                                   |
| Retries and backoff        | 3 attempts in total, waiting 200 ms then 400 ms between them (`LAUNCHPAD_RETRIES`, `backoff_ms`).                                                                                                                                                                                                        |
| Screenshots                | 1280×720 viewport, saved as PNG in `storage/app/screenshots`, served only to signed-in users.                                                                                                                                                                                                            |
| Refresh                    | Global **Refresh all** plus a per-card **Refresh** in each card's menu. Adding or editing a site checks only that site.                                                                                                                                                                                  |
| Version parsing            | A JSON body uses its `version` field; anything else uses the first line of plain text (tags stripped).                                                                                                                                                                                                   |
| Accounts                   | No public sign-up, password reset, email verification, 2FA or passkeys. Users are created on the server with `php artisan launchpad:user`.                                                                                                                                                               |
| Deploy                     | Paylance-based sites store a client folder and can be deployed from LaunchPad by starting the repository's `deploy-on-main.yml` workflow with a tag (all repository tags, newest by default), the client folder and a run-tests choice. No live progress tracking; the last deploy per site is recorded. |
