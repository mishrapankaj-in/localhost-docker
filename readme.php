<?php
declare(strict_types=1);

$readmePath = __DIR__ . '/README.md';
$readmeMarkdown = is_readable($readmePath) ? (string) file_get_contents($readmePath) : '';
$embed = isset($_GET['embed']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $embed ? 'README' : 'README — localhost.pk' ?></title>
    <?php if (!$embed) { ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php } ?>
    <style>
        body {
            margin: 0;
            background: #fff;
            color: #212529;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .readme-shell {
            max-width: 52rem;
            margin: 0 auto;
            padding: <?= $embed ? '1rem 1.25rem 1.5rem' : '2rem 1.25rem 3rem' ?>;
        }
        .readme-markdown h1,
        .readme-markdown h2,
        .readme-markdown h3 {
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .readme-markdown h1:first-child {
            margin-top: 0;
        }
        .readme-markdown p,
        .readme-markdown ul,
        .readme-markdown ol,
        .readme-markdown table {
            margin-bottom: 1rem;
        }
        .readme-markdown pre {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.75rem 1rem;
            overflow-x: auto;
        }
        .readme-markdown code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 0.9em;
        }
        .readme-markdown :not(pre) > code {
            background: #f8f9fa;
            padding: 0.1rem 0.35rem;
            border-radius: 0.25rem;
        }
        .readme-markdown table {
            width: 100%;
            border-collapse: collapse;
        }
        .readme-markdown th,
        .readme-markdown td {
            border: 1px solid #dee2e6;
            padding: 0.5rem 0.75rem;
            vertical-align: top;
        }
        .readme-markdown th {
            background: #f8f9fa;
        }
        .readme-empty {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="readme-shell">
        <?php if (!$embed) { ?>
            <p class="mb-3"><a href="/" class="text-decoration-none">&larr; Back to dashboard</a></p>
        <?php } ?>
        <div id="readmeContent" class="readme-markdown">
            <?php if ($readmeMarkdown === '') { ?>
                <p class="readme-empty mb-0">README.md was not found or is empty.</p>
            <?php } ?>
        </div>
    </div>
    <?php if ($readmeMarkdown !== '') { ?>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        (function () {
            var source = <?= json_encode($readmeMarkdown, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            var target = document.getElementById('readmeContent');
            if (typeof marked !== 'undefined' && target) {
                target.innerHTML = marked.parse(source);
            }
        })();
    </script>
    <?php } ?>
</body>
</html>
