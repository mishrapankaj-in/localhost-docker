# Docker Dashboard (localhost.pk)

A local landing page for Docker-hosted `.pk` sites. It lists application shortcuts and auto-discovers project folders from the filesystem, with an optional secret gate for personal projects.

**URL:** [https://localhost.pk](https://localhost.pk) (proxied via nginx to Apache/PHP 8.3)

## Overview

| | |
|---|---|
| **Purpose** | Central dashboard for a multi-site local dev stack — one page to reach tools and project vhosts |
| **Runtime** | PHP 8.3 (Apache, no framework, no build step) |
| **State** | PHP sessions only (My Work unlock gate) |
| **Data** | Filesystem directory scan under `mywork/`, `yii/`, and `work/` |
| **Frontend** | Bootstrap 5.3 + Bootstrap Icons (CDN), inline CSS/JS in `index.php` |

The app is a single-file PHP dashboard (`index.php`) with companion pages for diagnostics and documentation preview. Reverse-proxy and container configuration live outside this repository.

For a full architecture review (request lifecycle, security model, integration points), see [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

## Setup

### Prerequisites

- PHP 8.3+ with Apache (session and filesystem support)
- nginx TLS termination for `*.pk` domains (configured outside this repo)
- Project folders laid out as siblings under a shared parent directory (see [Directory layout](#directory-layout))

### Deploy / run locally

1. Place this repository at `mywork/localhost.pk/` under your projects root (see layout below).
2. Ensure the web server maps `https://localhost.pk` to this folder’s document root.
3. Optionally create a `.env` file or set `LOCALHOST_PK_MYWORK_KEY` in the container environment (see [Configuration](#configuration)).
4. Open [https://localhost.pk](https://localhost.pk) in a browser.

No Composer, npm, or build step is required.

### My Work secret (optional)

To unlock personal projects, set a secret before first use:

```env
LOCALHOST_PK_MYWORK_KEY=your-secret-here
```

If neither `.env` nor the environment provides a value, the dashboard falls back to `local-dev` (local development only — change this for any non-local use).

## Usage

### Dashboard layout

- **Sidebar** — fixed navigation for Applications and Projects (collapsible sections).
- **Main content** — cards for Applications, Projects, and Server Info.
- **Manage project** — opens [Site Manager](https://sitemanager.pk) in a new tab.
- **Read ME** — opens this README in a lightbox (`readme.php?embed=1`); use **Open preview** for a full-page view.

### Applications

Fixed shortcuts to shared infrastructure (phpMyAdmin, MailHog, Sphinx, Site Manager). Links open in a new tab from both the sidebar and the Applications card.

### Projects

Folders are auto-discovered from disk and grouped into buckets:

| Bucket | Source paths | Visibility |
|--------|--------------|------------|
| **HTML** | Folders whose names start with `html` (from Work, and My Work when unlocked) | Work: always; My Work: when unlocked |
| **Work** | `{projectsRoot}/work/` (non-`html*` folders) | Always visible |
| **My Work** | `{projectsRoot}/mywork/` and `{projectsRoot}/yii/` (non-`html*` folders) | Hidden until unlocked |

Each folder name becomes a link: `https://{folder-name}/`.

### My Work gate

Personal projects are hidden by default.

1. Click the **key** button (bottom-right).
2. Enter your secret and submit.
3. My Work and Yii folders appear in the Projects section; Server Info shows their document roots.
4. Click the **lock** button to hide them again for the current session.

Unlock is session-based, not persistent authentication. Failed attempts show an error and reopen the unlock modal.

### Search

Use the search box above the cards to filter links in the main content area. Press **Escape** or **Backspace** to clear the filter.

### Server Info

Shows web server software, PHP version (links to `phpinfo.php`), and document root paths. My Work paths are shown only when unlocked.

## Features

- **Applications** — fixed shortcuts to common tools (phpMyAdmin, MailHog, Sphinx, Site Manager, etc.)
- **Manage project** — opens [Site Manager](https://sitemanager.pk) to list and manage sites
- **Projects** — auto-discovered folders in HTML, Work, and My Work buckets
- **Search** — filter dashboard buttons and cards in the main content area
- **Server Info** — PHP version, document roots, link to `phpinfo.php`
- **My Work gate** — key button (bottom-right) unlocks or hides personal projects for the current session
- **Read ME** — in-dashboard README preview via `readme.php`

## Directory layout

Project discovery is based on sibling folders under the Docker projects root. Paths are resolved relative to this repo’s location on disk:

```text
{projectsRoot}/                 # parent of mywork/ (e.g. /var/www/html)
├── mywork/                     # personal projects (gated)
│   └── localhost.pk/           # this dashboard
├── yii/                        # also shown under My Work when unlocked
└── work/                       # always visible Work projects
```

Each discovered folder name becomes a link like `https://{folder-name}/`.

## Configuration

The My Work unlock secret can be set in two ways:

1. **`.env` file** in this folder (optional):

   ```env
   LOCALHOST_PK_MYWORK_KEY=your-secret-here
   ```

2. **Environment variable** — set `LOCALHOST_PK_MYWORK_KEY` in docker-compose or the container environment.

If neither is set, the dashboard falls back to `local-dev` (local development only — change this for any non-local use).

## Customization

Edit [`index.php`](index.php) to change dashboard behaviour.

### Applications

Add or remove shortcuts in the `$applications` array:

```php
$applications = [
    'phpMyAdmin' => 'https://phpmyadmin.pk/',
    'MailHog' => 'https://mailhog.pk/',
    'sphinix' => 'https://sphinx.pk/',
];
```

The key is the label shown in the UI; the value is the URL opened in a new tab.

### Excluded project folders

Folders listed in `$excludeDirs` are skipped during project discovery. Use this to hide folders that are listed under Applications instead, or that should never appear in the Projects section:

```php
$excludeDirs = [
    '.', '..', 'localhost.pk', 'moodledata', 'yii1119',
    'phpmyadmin4.pk', 'phpmyadmin5.pk', 'phpmyadmin6.pk',
    'sphinx.pk', 'teampass_secure_key',
];
```

For example, `sphinx.pk` lives under `work/` but is shown as an Application link rather than a Project folder.

## Files

| File | Purpose |
|------|---------|
| `index.php` | Dashboard UI and logic |
| `readme.php` | README preview (`?embed=1` for dashboard lightbox) |
| `phpinfo.php` | PHP environment details |
| `.env` | Optional My Work unlock secret |
| `docs/ARCHITECTURE.md` | Project architecture reference |
| `docs/QA-REVIEW-LCLDCR-T00001.md` | QA review checklist for architecture deliverable |

## Security

- `.env` contains a secret — do not commit it to version control.
- My Work unlock is session-based, not persistent authentication.
- Change the default `local-dev` secret before using this outside local development.

---

## Hub Alignment E2E

Created by Agent (LOCAL mode)

| Field | Value |
|-------|-------|
| Status | PASS |
| Project | #2 (`LCLDCR` / `localhost.pk`) |
| Command | #37 (`agent/cmd-37`) |
| Execution mode | LOCAL |
| Timestamp (UTC) | 2026-06-15T06:10:00Z |

**Review:** PHP 8.3 single-file dashboard (`index.php`) with filesystem project discovery, My Work session gate, Bootstrap 5.3 UI, and companion pages (`readme.php`, `phpinfo.php`). README setup, usage, and overview match live behaviour; architecture documented in `docs/ARCHITECTURE.md`.

---

## Hub Worker Reliability Validation

Created by Agent (LOCAL mode)

| Field | Value |
|-------|-------|
| Status | PASS |
| Project | #2 (`LCLDCR` / `localhost.pk`) |
| Command | #38 (`agent/cmd-38`) |
| Execution mode | LOCAL |
| Timestamp (UTC) | 2026-06-15T06:11:46Z |

**Review:** `localhost.pk` reviewed for Hub worker reliability validation — filesystem project discovery (`mywork/`, `yii/`, `work/`), HTML bucket partitioning, session-gated My Work unlock (`hash_equals`), application shortcuts, and README preview (`readme.php`) confirmed against `index.php`. LOCAL agent execution completed; validation stamp appended per HR-7 Phase A pilot scope.

---

## Cursor SDK Runtime Fix Validation

Created by Agent (LOCAL mode)

| Field | Value |
|-------|-------|
| Status | PASS |
| Project | #2 (`LCLDCR` / `localhost.pk`) |
| Command | #40 (`agent/cmd-40`) |
| Execution mode | LOCAL |
| Timestamp (UTC) | 2026-06-15T06:17:34Z |

**Review:** `localhost.pk` reviewed for Cursor SDK runtime fix validation — PHP 8.3 entry points (`index.php`, `readme.php`, `phpinfo.php`) pass syntax check; filesystem project discovery, My Work session gate, and README preview pipeline remain consistent with documented behaviour. LOCAL agent execution completed without push, merge, or deploy; validation stamp appended per HR-7 Phase A pilot scope.

---

## Cursor SDK Runtime Fix Validation

Created by Agent (LOCAL mode)

| Field | Value |
|-------|-------|
| Status | PASS |
| Project | #2 (`LCLDCR` / `localhost.pk`) |
| Command | #41 (`agent/cmd-41`) |
| Execution mode | LOCAL |
| Timestamp (UTC) | 2026-06-15T06:20:26Z |

**Review:** `localhost.pk` re-reviewed for Cursor SDK runtime fix validation — PHP 8.3 entry points (`index.php`, `readme.php`, `phpinfo.php`) pass `php -l` syntax check; `.env`/environment secret loading, `hash_equals` session gate, HTML/Work/My Work bucket partitioning, and `readme.php` embed preview align with README overview and `docs/ARCHITECTURE.md`. LOCAL agent execution completed without push, merge, or deploy; validation stamp appended per HR-7 Phase A pilot scope.
