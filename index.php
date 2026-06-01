<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Optional: LOCALHOST_PK_MYWORK_KEY in this folder's .env (only if not already in the environment)
$envFile = __DIR__ . '/.env';
if ((getenv('LOCALHOST_PK_MYWORK_KEY') === false || getenv('LOCALHOST_PK_MYWORK_KEY') === '') && is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if ($v !== '' && ($v[0] === '"' || $v[0] === "'")) {
            $v = trim($v, $v[0]);
        }
        if ($k === 'LOCALHOST_PK_MYWORK_KEY' && $v !== '') {
            putenv($k . '=' . $v);
            $_ENV[$k] = $v;
        }
    }
}

// Or set LOCALHOST_PK_MYWORK_KEY in the real environment (e.g. docker-compose). Falls back for local use only.
$myworkGateSecret = getenv('LOCALHOST_PK_MYWORK_KEY');
if ($myworkGateSecret === false || $myworkGateSecret === '') {
    $myworkGateSecret = 'local-dev';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirectPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!is_string($redirectPath) || $redirectPath === '') {
        $redirectPath = '/';
    }

    if (isset($_POST['lock_mywork'])) {
        unset($_SESSION['localhost_pk_mywork_unlocked'], $_SESSION['localhost_pk_unlock_flash']);
        header('Location: ' . $redirectPath, true, 303);
        exit;
    }

    if (isset($_POST['unlock_mywork'])) {
        unset($_SESSION['localhost_pk_unlock_flash']);
        $candidate = isset($_POST['mywork_secret']) ? (string) $_POST['mywork_secret'] : '';
        if (hash_equals($myworkGateSecret, $candidate)) {
            $_SESSION['localhost_pk_mywork_unlocked'] = true;
        } else {
            $_SESSION['localhost_pk_unlock_flash'] = 'That secret was not recognised.';
        }
        header('Location: ' . $redirectPath, true, 303);
        exit;
    }
}

$myworkVisible = !empty($_SESSION['localhost_pk_mywork_unlocked']);
$unlockFlash = isset($_SESSION['localhost_pk_unlock_flash']) ? (string) $_SESSION['localhost_pk_unlock_flash'] : '';
unset($_SESSION['localhost_pk_unlock_flash']);

$excludeDirs = ['.', '..', 'localhost.pk', 'moodledata', 'yii1119', 'phpmyadmin4.pk', 'phpmyadmin5.pk', 'phpmyadmin6.pk', 'sphinx.pk','teampass_secure_key'];

$projectsRoot = dirname(dirname(__DIR__));
$myworkPath = dirname(__DIR__);
$yiiPath = $projectsRoot . DIRECTORY_SEPARATOR . 'yii';
$workPath = $projectsRoot . DIRECTORY_SEPARATOR . 'work';

$listProjects = static function (string $basePath) use ($excludeDirs): array {
    if (!is_dir($basePath) || !is_readable($basePath)) {
        return [];
    }
    $names = array_filter(scandir($basePath), static function (string $dir) use ($basePath, $excludeDirs): bool {
        return $dir !== '.' && $dir !== '..' && is_dir($basePath . '/' . $dir) && !in_array($dir, $excludeDirs, true);
    });
    sort($names, SORT_STRING | SORT_FLAG_CASE);

    return $names;
};

$projectsByCategory = [
    'mywork' => $listProjects($myworkPath),
    'yii' => is_dir($yiiPath) ? $listProjects($yiiPath) : [],
    'work' => $listProjects($workPath),
];

$myworkHasProjects = $projectsByCategory['mywork'] !== [] || $projectsByCategory['yii'] !== [];

$myWorkDisplayProjects = array_merge($projectsByCategory['mywork'], $projectsByCategory['yii']);
sort($myWorkDisplayProjects, SORT_STRING | SORT_FLAG_CASE);

/** Split folder names: html.* → HTML bucket, everything else unchanged. */
$partitionHtmlProjects = static function (array $projects): array {
    $html = [];
    $other = [];
    foreach ($projects as $project) {
        if (str_starts_with(strtolower($project), 'html')) {
            $html[] = $project;
        } else {
            $other[] = $project;
        }
    }
    sort($html, SORT_STRING | SORT_FLAG_CASE);
    sort($other, SORT_STRING | SORT_FLAG_CASE);

    return ['html' => $html, 'other' => $other];
};

$workPartition = $partitionHtmlProjects($projectsByCategory['work'] ?? []);
$myWorkPartition = $partitionHtmlProjects($myWorkDisplayProjects);

$workHtmlProjects = $workPartition['html'];
$workRegularProjects = $workPartition['other'];
$myWorkHtmlProjects = $myWorkPartition['html'];
$myWorkRegularProjects = $myWorkPartition['other'];

$htmlProjects = array_values(array_unique(array_merge($workHtmlProjects, $myWorkHtmlProjects)));
sort($htmlProjects, SORT_STRING | SORT_FLAG_CASE);

$applications = [
    'phpMyAdmin' => 'https://phpmyadmin.pk/',
    'MailHog' => 'https://mailhog.pk/',
    'sphinix' => 'https://sphinx.pk/',
    'Site Manager' => 'https://docker-site-manager.pk/sites',
];

$apacheDocumentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$myworkDocumentRoot = realpath($myworkPath) ?: $myworkPath;
$yiiDocumentRoot = realpath($yiiPath) ?: $yiiPath;
$workDocumentRoot = realpath($workPath) ?: $workPath;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docker Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect fill='%232496ed' width='32' height='32' rx='8'/%3E%3Cpath fill='white' d='M8 12h2v8H8zm4-2h2v10h-2zm4 2h2v8h-2zm4-4h2v12h-2z'/%3E%3C/svg%3E">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            height: 100vh;
            position: fixed;
            width: 250px;
            background: #087762;
            padding-top: 20px;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }
        .sidebar a {
            color: #ffffff;
            padding: 10px;
            display: block;
            text-decoration: none;
            font-weight: bold;
        }
        .sidebar .submenu a {
            padding-left: 20px;
            font-weight: normal;
        }
        .sidebar a:hover {
            color: #087762;
            background: #f8f9fa;
        }
        .sidebar a.text-white:hover {
            color: #087762 !important;
        }
        .home a:hover {
            color: #087762;
            background: #f8f9fa;
        }
        .content {
            width: 100%;
            margin-left: 270px;
            padding: 20px;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .mywork-gate-fab-wrap {
            position: fixed;
            bottom: 1.25rem;
            right: 1.25rem;
            z-index: 1040;
        }
        .mywork-gate-fab {
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-size: 1.35rem;
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.18);
        }
    </style>
    <script>
        function filterItems() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let items = document.querySelectorAll('.content .card-body a');
            
            items.forEach(item => {
                if (item.textContent.toLowerCase().includes(input)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }       
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' || event.key === 'Backspace') {
                document.getElementById('searchInput').value = '';
                filterItems();
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.sidebar a.js-sidebar-top-toggle[data-bs-toggle="collapse"]').forEach(function (item) {
                item.addEventListener('click', function() {
                    var target = document.querySelector(this.getAttribute('href'));
                    document.querySelectorAll('.sidebar > .collapse').forEach(function (section) {
                        if (section !== target) {
                            section.classList.remove('show');
                        }
                    });
                });
            });
        });
    </script>
</head>
<body>
    <div class="d-flex">
        <nav class="sidebar">
            <a href="https://localhost.pk/" class="d-block text-center p-3 home" style="font-size: 1.5rem;">
                <img src="https://cdn-icons-png.flaticon.com/512/25/25694.png" alt="Home" width="24"> Home
            </a>
            <a class="d-block js-sidebar-top-toggle" data-bs-toggle="collapse" href="#applications" role="button" aria-expanded="false">
                🛠 Applications
            </a>
            <div class="collapse show submenu" id="applications">
                <?php foreach ($applications as $name => $url): ?>
                    <a href="<?= htmlspecialchars($url) ?>" target="_blank"> 🖥 <?= htmlspecialchars($name) ?> </a>
                <?php endforeach; ?>
            </div>
            <a class="d-block collapsed js-sidebar-top-toggle" data-bs-toggle="collapse" href="#projects" role="button" aria-expanded="true">
                <img src="https://cdn-icons-png.flaticon.com/512/716/716784.png" alt="Projects" width="20"> Projects
            </a>
            <div class="collapse show submenu" id="projects">
                <?php
                if (!$myworkVisible) {
                    $anySidebar = false;
                    $renderSidebarBucket = static function (string $bucketId, string $label, array $projects): void {
                        ?>
                        <a class="d-flex justify-content-between align-items-center px-3 py-2 small text-white text-decoration-none border-top border-white border-opacity-25" data-bs-toggle="collapse" href="#<?= htmlspecialchars($bucketId, ENT_QUOTES, 'UTF-8') ?>" role="button" aria-expanded="true">
                            <span><?= htmlspecialchars($label) ?></span>
                            <i class="bi bi-chevron-down small opacity-75" aria-hidden="true"></i>
                        </a>
                        <div class="collapse show submenu border-bottom border-white border-opacity-10" id="<?= htmlspecialchars($bucketId, ENT_QUOTES, 'UTF-8') ?>">
                            <?php
                            if ($projects === []) { ?>
                                <span class="d-block px-3 py-2 small text-white-50">No projects</span>
                            <?php } else {
                                foreach ($projects as $project) { ?>
                                    <a href="https://<?= urlencode($project) ?>/" target="_blank" rel="noopener"> 📁 <?= htmlspecialchars($project) ?> </a>
                                <?php }
                            } ?>
                        </div>
                        <?php
                    };
                    if ($workHtmlProjects !== []) {
                        $anySidebar = true;
                        $renderSidebarBucket('sidebar-bucket-html', 'HTML', $workHtmlProjects);
                    }
                    if ($workRegularProjects !== []) {
                        $anySidebar = true;
                        $renderSidebarBucket('sidebar-bucket-work', 'Work', $workRegularProjects);
                    }
                    if (!$anySidebar) { ?>
                        <span class="d-block px-3 py-1 small text-white-50">No project folders found<?= $myworkHasProjects ? ' — use the key (bottom-right)' : '' ?></span>
                    <?php }
                } else {
                    $renderSidebarBucket = static function (string $bucketId, string $label, array $projects): void {
                        ?>
                        <a class="d-flex justify-content-between align-items-center px-3 py-2 small text-white text-decoration-none border-top border-white border-opacity-25" data-bs-toggle="collapse" href="#<?= htmlspecialchars($bucketId, ENT_QUOTES, 'UTF-8') ?>" role="button" aria-expanded="true">
                            <span><?= htmlspecialchars($label) ?></span>
                            <i class="bi bi-chevron-down small opacity-75" aria-hidden="true"></i>
                        </a>
                        <div class="collapse show submenu border-bottom border-white border-opacity-10" id="<?= htmlspecialchars($bucketId, ENT_QUOTES, 'UTF-8') ?>">
                            <?php
                            if ($projects === []) { ?>
                                <span class="d-block px-3 py-2 small text-white-50">No projects</span>
                            <?php } else {
                                foreach ($projects as $project) { ?>
                                    <a href="https://<?= urlencode($project) ?>/" target="_blank" rel="noopener"> 📁 <?= htmlspecialchars($project) ?> </a>
                                <?php }
                            } ?>
                        </div>
                        <?php
                    };
                    if ($htmlProjects !== []) {
                        $renderSidebarBucket('sidebar-bucket-html', 'HTML', $htmlProjects);
                    }
                    $renderSidebarBucket('sidebar-bucket-mywork', 'My Work', $myWorkRegularProjects);
                    $renderSidebarBucket('sidebar-bucket-work', 'Work', $workRegularProjects);
                }
                ?>
            </div>
        </nav>
        
        <div class="content">
            <h1 class="mb-4 text-center">Docker Dashboard</h1>
            <?php if ($unlockFlash !== '') { ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($unlockFlash, ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <a href="https://docker-site-manager.pk/sites" class="btn btn-warning" target="_blank" rel="noopener">
                    <i class="bi bi-gear-fill" aria-hidden="true"></i> Manage project
                </a>
                <input type="text" id="searchInput" class="form-control search-box" style="max-width: 16rem;" placeholder="Search…" onkeyup="filterItems()">
            </div>
            
            <div class="card mb-3">
                <div class="card-header" data-bs-toggle="collapse" href="#dashboard-applications" role="button">Applications</div>
                <div class="collapse show" id="dashboard-applications">
                    <div class="card-body">
                        <?php foreach ($applications as $name => $url): ?>
                            <a href="<?= htmlspecialchars($url) ?>" class="btn btn-success mb-2" target="_blank">🖥 <?= htmlspecialchars($name) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-header" data-bs-toggle="collapse" href="#dashboard-projects" role="button">Projects</div>
                <div class="collapse show" id="dashboard-projects">
                    <div class="card-body">
                        <?php
                        if (!$myworkVisible) {
                            $anyDashboard = false;
                            $renderDashboardBucket = static function (string $bucketId, string $label, array $projects): void {
                                ?>
                                <div class="border rounded mb-3 overflow-hidden">
                                    <button class="w-100 btn btn-light text-start d-flex justify-content-between align-items-center rounded-0 py-2 px-3 fw-semibold small" type="button" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($bucketId, ENT_QUOTES, 'UTF-8') ?>" aria-expanded="true">
                                        <span><?= htmlspecialchars($label) ?></span>
                                        <i class="bi bi-chevron-down small text-muted" aria-hidden="true"></i>
                                    </button>
                                    <div class="collapse show border-top" id="<?= htmlspecialchars($bucketId, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="p-3 bg-body-secondary bg-opacity-25">
                                            <?php
                                            if ($projects === []) { ?>
                                                <span class="text-muted small">No folders in this bucket.</span>
                                            <?php } else {
                                                foreach ($projects as $project) { ?>
                                                    <a href="https://<?= urlencode($project) ?>/" class="btn btn-primary mb-2 me-1" target="_blank" rel="noopener">📁 <?= htmlspecialchars($project) ?></a>
                                                <?php }
                                            } ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            };
                            if ($workHtmlProjects !== []) {
                                $anyDashboard = true;
                                $renderDashboardBucket('dashboard-bucket-html', 'HTML', $workHtmlProjects);
                            }
                            if ($workRegularProjects !== []) {
                                $anyDashboard = true;
                                $renderDashboardBucket('dashboard-bucket-work', 'Work', $workRegularProjects);
                            }
                            if (!$anyDashboard) { ?>
                                <p class="text-muted mb-0">
                                    <?php if ($myworkHasProjects) { ?>
                                        My Work projects are hidden. Use the <strong>key</strong> button (bottom-right) and enter your secret.
                                    <?php } else { ?>
                                        No project folders found under <code>Work</code>.
                                    <?php } ?>
                                </p>
                            <?php }
                        } else {
                            $renderDashboardBucket = static function (string $bucketId, string $label, array $projects): void {
                                ?>
                                <div class="border rounded mb-3 overflow-hidden">
                                    <button class="w-100 btn btn-light text-start d-flex justify-content-between align-items-center rounded-0 py-2 px-3 fw-semibold small" type="button" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($bucketId, ENT_QUOTES, 'UTF-8') ?>" aria-expanded="true">
                                        <span><?= htmlspecialchars($label) ?></span>
                                        <i class="bi bi-chevron-down small text-muted" aria-hidden="true"></i>
                                    </button>
                                    <div class="collapse show border-top" id="<?= htmlspecialchars($bucketId, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="p-3 bg-body-secondary bg-opacity-25">
                                            <?php
                                            if ($projects === []) { ?>
                                                <span class="text-muted small">No folders in this bucket.</span>
                                            <?php } else {
                                                foreach ($projects as $project) { ?>
                                                    <a href="https://<?= urlencode($project) ?>/" class="btn btn-primary mb-2 me-1" target="_blank" rel="noopener">📁 <?= htmlspecialchars($project) ?></a>
                                                <?php }
                                            } ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            };
                            if ($htmlProjects !== []) {
                                $renderDashboardBucket('dashboard-bucket-html', 'HTML', $htmlProjects);
                            }
                            $renderDashboardBucket('dashboard-bucket-mywork', 'My Work', $myWorkRegularProjects);
                            $renderDashboardBucket('dashboard-bucket-work', 'Work', $workRegularProjects);
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header" data-bs-toggle="collapse" href="#dashboard-serverinfo" role="button">Server Info</div>
                <div class="collapse show" id="dashboard-serverinfo">
                    <div class="card-body">
                        <span class="server_info"> <?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? '', ENT_QUOTES, 'UTF-8') ?> </span><br>
                        <a title="phpinfo()" href="phpinfo.php" target="_blank">PHP version: <?= htmlspecialchars(phpversion(), ENT_QUOTES, 'UTF-8') ?></a><br>
                        <strong>Document root (this request)</strong><br>
                        <code class="d-inline-block mb-2"><?= htmlspecialchars($apacheDocumentRoot, ENT_QUOTES, 'UTF-8') ?></code><br>
                        <?php if ($myworkVisible) { ?>
                        <strong>My Work</strong><br>
                        <code class="d-inline-block mb-1"><?= htmlspecialchars($myworkDocumentRoot, ENT_QUOTES, 'UTF-8') ?></code><br>
                        <?php if (is_dir($yiiPath)) { ?>
                        <code class="d-inline-block mb-2"><?= htmlspecialchars($yiiDocumentRoot, ENT_QUOTES, 'UTF-8') ?></code><br>
                        <?php } ?>
                        <?php } ?>
                        <strong>Work</strong><br>
                        <code class="d-inline-block"><?= htmlspecialchars($workDocumentRoot, ENT_QUOTES, 'UTF-8') ?></code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mywork-gate-fab-wrap">
        <?php if (!$myworkVisible) { ?>
            <button type="button" class="btn btn-primary mywork-gate-fab" data-bs-toggle="modal" data-bs-target="#myworkUnlockModal" title="Unlock My Work" aria-label="Unlock My Work">
                <i class="bi bi-key-fill" aria-hidden="true"></i>
            </button>
        <?php } else { ?>
            <form method="post" class="m-0">
                <input type="hidden" name="lock_mywork" value="1">
                <button type="submit" class="btn btn-secondary mywork-gate-fab" title="Hide My Work projects" aria-label="Hide My Work projects">
                    <i class="bi bi-door-closed-fill" aria-hidden="true"></i>
                </button>
            </form>
        <?php } ?>
    </div>

    <?php if ($unlockFlash !== '') { ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var el = document.getElementById('myworkUnlockModal');
            if (el && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(el).show();
            }
        });
    </script>
    <?php } ?>

    <div class="modal fade" id="myworkUnlockModal" tabindex="-1" aria-labelledby="myworkUnlockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="myworkUnlockModalLabel">Unlock My Work</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-2">Enter the secret to list My Work projects.</p>
                        <label for="mywork_secret" class="form-label">Secret</label>
                        <input type="password" class="form-control" id="mywork_secret" name="mywork_secret" autocomplete="off" required>
                        <input type="hidden" name="unlock_mywork" value="1">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Unlock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

