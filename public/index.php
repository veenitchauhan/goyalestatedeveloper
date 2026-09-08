<?php
declare(strict_types=1);
$config = require dirname(__DIR__) . '/config/development.php';
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'none'");
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '/health') {
    $health = json_encode(['status' => 'ok', 'environment' => 'local', 'checkpoint' => 1]);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html')) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Local connection check</title><h1>Local connection is working</h1><pre>' . $health . '</pre><a href="/">Back to checkpoint</a></html>';
    } else {
        header('Content-Type: application/json');
        echo $health;
    }
    exit;
}
$missing = !in_array($path, ['/', '/index.php'], true);
if ($missing) http_response_code(404);
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$total = count($config['modules']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= $escape($config['company']) ?> | Development checkpoint</title>
    <link rel="stylesheet" href="/assets/checkpoint.css">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header><span class="brand"><?= $escape($config['company']) ?></span><span class="environment">LOCAL DEVELOPMENT</span></header>
<main id="main">
    <section class="intro">
        <p class="eyebrow">DEVELOPMENT CHECKPOINT / 01</p>
        <h1><?= $missing ? 'This page isn’t here.' : 'A considered start.<br>A solid foundation.' ?></h1>
        <p class="description"><?= $missing ? 'The requested page is not available at this development checkpoint.' : 'The local foundation for your corporate digital platform. This checkpoint lets you review the setup before the next module begins.' ?></p>
        <a class="button" href="<?= $missing ? '/' : '/health' ?>"><?= $missing ? 'Back to checkpoint' : 'Check local connection' ?> <span aria-hidden="true">↗</span></a>
        <p class="note">Development checkpoint only. The corporate homepage will be designed in Module 4.</p>
    </section>
    <section class="panel" aria-labelledby="progress-title">
        <p class="eyebrow" id="progress-title">DELIVERY PROGRESS</p>
        <div class="stats"><div><strong><?= $total ?></strong><span>Total modules</span></div><div><strong><?= $config['completed'] ?></strong><span>Completed</span></div><div><strong><?= $total - $config['completed'] ?></strong><span>Remaining</span></div></div>
        <p class="status"><?= $escape($config['status']) ?></p>
        <p>Development pauses after every module for your testing and approval.</p>
    </section>
    <section class="roadmap" aria-labelledby="roadmap-title">
        <div class="section-heading"><h2 id="roadmap-title">The path ahead</h2><span>One module at a time</span></div>
        <ol><?php foreach ($config['modules'] as $index => $module): ?><li><span class="number"><?= sprintf('%02d', $index + 1) ?></span><span><?= $escape($module) ?></span><small><?= $index < $config['completed'] ? 'Ready for review' : ($index === 0 ? 'In progress' : 'Upcoming') ?></small></li><?php endforeach; ?></ol>
    </section>
</main>
<footer><span><?= $escape($config['company']) ?></span><span><?= $escape($config['host']) ?></span></footer>
</body>
</html>
