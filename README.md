# Docker Dashboard (localhost.pk)

A local landing page for Docker-hosted `.pk` sites. It lists application shortcuts and auto-discovers project folders from the filesystem, with an optional secret gate for personal projects.

**URL:** [https://localhost.pk](https://localhost.pk) (proxied via nginx to Apache/PHP 8.3)

## Features

- **Applications** — fixed shortcuts to common tools (phpMyAdmin, MailHog, Sphinx, Site Manager, etc.)
- **Manage project** — opens [Site Manager](https://sitemanager.pk) to list and manage sites
- **Projects** — auto-discovered folders, grouped into:
  - **My Work** — folders under `projects/mywork/` and `projects/yii/` (hidden until unlocked)
  - **Work** — folders under `projects/work/` (always visible)
- **Search** — filter dashboard buttons and cards
- **Server Info** — PHP version, document roots, link to `phpinfo.php`
- **My Work gate** — key button (bottom-right) unlocks or hides personal projects for the current session

## Directory layout

Project discovery is based on sibling folders under the Docker projects root:

```text
/home/pankaj/docker/projects/
├── mywork/              # personal projects (gated)
│   └── localhost.pk/    # this dashboard
├── yii/                 # also shown under My Work when unlocked
└── work/                # always visible Work projects
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
| `phpinfo.php` | PHP environment details |
| `.env` | Optional My Work unlock secret |

## Security

- `.env` contains a secret — do not commit it to version control.
- My Work unlock is session-based, not persistent authentication.
- Change the default `local-dev` secret before using this outside local development.
