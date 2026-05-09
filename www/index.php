<?php
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$phpVersion = PHP_VERSION;
$serverSoftware = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Apache';
$documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '/var/www/html';
$serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
$serverTime = date('Y-m-d H:i:s');
$extensions = array('mysqli', 'pdo_mysql', 'mysql');
$databaseName = getenv('MYSQL_DATABASE') ? getenv('MYSQL_DATABASE') : 'lamp';
$databaseUser = getenv('MYSQL_USER') ? getenv('MYSQL_USER') : 'lamp';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LAMP PHP 5.6</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --panel: #ffffff;
            --ink: #1f2937;
            --muted: #64748b;
            --line: #dbe3ee;
            --brand: #0f766e;
            --brand-dark: #115e59;
            --accent: #2563eb;
            --ok: #16a34a;
            --warn: #d97706;
            --shadow: 0 18px 45px rgba(15, 23, 42, .10);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            background:
                linear-gradient(135deg, rgba(15, 118, 110, .12), rgba(37, 99, 235, .08) 42%, rgba(255, 255, 255, 0) 70%),
                var(--bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 48px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .logo {
            display: grid;
            width: 44px;
            height: 44px;
            place-items: center;
            flex: 0 0 auto;
            color: #fff;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--brand), var(--accent));
            box-shadow: 0 12px 26px rgba(15, 118, 110, .22);
            font-weight: 800;
        }

        .brand h1 {
            margin: 0;
            font-size: 22px;
            line-height: 1.1;
            letter-spacing: 0;
        }

        .brand p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .nav {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .nav a {
            display: inline-flex;
            min-height: 38px;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            color: var(--brand-dark);
            border: 1px solid rgba(15, 118, 110, .24);
            border-radius: 8px;
            background: rgba(255, 255, 255, .72);
            font-size: 14px;
            font-weight: 700;
        }

        .nav a:hover {
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .08);
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(300px, .75fr);
            gap: 18px;
            align-items: stretch;
            margin-bottom: 18px;
        }

        .panel {
            border: 1px solid rgba(219, 227, 238, .9);
            border-radius: 8px;
            background: rgba(255, 255, 255, .88);
            box-shadow: var(--shadow);
        }

        .intro {
            padding: 30px;
            min-height: 270px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            position: relative;
        }

        .intro:after {
            content: "";
            position: absolute;
            right: 30px;
            bottom: 24px;
            width: 170px;
            height: 92px;
            opacity: .16;
            background:
                linear-gradient(var(--brand), var(--brand)) 0 0 / 100% 8px no-repeat,
                repeating-linear-gradient(90deg, var(--accent) 0 8px, transparent 8px 18px);
            border: 2px solid var(--brand);
            border-radius: 8px;
        }

        .eyebrow {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            color: var(--brand-dark);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--ok);
            box-shadow: 0 0 0 5px rgba(22, 163, 74, .14);
        }

        .intro h2 {
            max-width: 650px;
            margin: 0;
            font-size: clamp(34px, 5vw, 58px);
            line-height: 1;
            letter-spacing: 0;
        }

        .intro p {
            max-width: 640px;
            margin: 18px 0 0;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.7;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 26px;
            position: relative;
            z-index: 1;
        }

        .button {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 800;
        }

        .button.primary {
            color: #fff;
            background: var(--brand);
            box-shadow: 0 10px 20px rgba(15, 118, 110, .24);
        }

        .button.secondary {
            color: var(--ink);
            background: #eef5ff;
            border: 1px solid #cfe0f7;
        }

        .runtime {
            padding: 24px;
        }

        .runtime h3,
        .section h3 {
            margin: 0 0 16px;
            font-size: 16px;
            letter-spacing: 0;
        }

        .metric {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 0;
            border-bottom: 1px solid var(--line);
        }

        .metric:last-child {
            border-bottom: 0;
        }

        .metric span {
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
        }

        .metric strong {
            max-width: 62%;
            overflow-wrap: anywhere;
            text-align: right;
            font-size: 14px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 18px;
        }

        .card {
            min-height: 150px;
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            box-shadow: 0 12px 30px rgba(15, 23, 42, .07);
        }

        .card .label {
            margin: 0 0 12px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .card .value {
            margin: 0;
            font-size: 25px;
            font-weight: 850;
            overflow-wrap: anywhere;
        }

        .card .hint {
            margin: 14px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.55;
        }

        .section {
            padding: 24px;
            margin-top: 18px;
        }

        .extensions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .extension {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 56px;
            padding: 0 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fbfdff;
            font-weight: 800;
        }

        .pill {
            display: inline-flex;
            min-width: 70px;
            min-height: 26px;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
            border-radius: 999px;
            color: #fff;
            background: var(--ok);
            font-size: 12px;
            font-weight: 800;
        }

        .pill.missing {
            background: var(--warn);
        }

        @media (max-width: 820px) {
            .topbar,
            .hero {
                grid-template-columns: 1fr;
            }

            .topbar {
                display: grid;
            }

            .nav {
                justify-content: flex-start;
            }

            .grid,
            .extensions {
                grid-template-columns: 1fr;
            }

            .intro {
                min-height: auto;
                padding: 24px;
            }

            .intro:after {
                display: none;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="topbar">
            <a class="brand" href="/">
                <span class="logo">L</span>
                <span>
                    <h1>LAMP PHP 5.6</h1>
                    <p>Apache, PHP and MariaDB in one container</p>
                </span>
            </a>
            <nav class="nav" aria-label="Primary">
                <a href="/mysqli.php">MySQL Check</a>
                <a href="/info.php">phpinfo()</a>
            </nav>
        </header>

        <section class="hero">
            <div class="panel intro">
                <div>
                    <span class="eyebrow"><span class="dot"></span> Stack online</span>
                    <h2>Ready for legacy PHP projects.</h2>
                    <p>This workspace is running PHP 5.6 with Apache and local MariaDB support, tuned for quick checks and old application compatibility.</p>
                </div>
                <div class="actions">
                    <a class="button primary" href="/mysqli.php">Open database status</a>
                    <a class="button secondary" href="/info.php">View PHP details</a>
                </div>
            </div>

            <aside class="panel runtime">
                <h3>Runtime</h3>
                <div class="metric">
                    <span>Server</span>
                    <strong><?php echo e($serverName); ?></strong>
                </div>
                <div class="metric">
                    <span>Software</span>
                    <strong><?php echo e($serverSoftware); ?></strong>
                </div>
                <div class="metric">
                    <span>Time</span>
                    <strong><?php echo e($serverTime); ?></strong>
                </div>
                <div class="metric">
                    <span>Root</span>
                    <strong><?php echo e($documentRoot); ?></strong>
                </div>
            </aside>
        </section>

        <section class="grid" aria-label="Stack summary">
            <article class="card">
                <p class="label">PHP</p>
                <p class="value"><?php echo e($phpVersion); ?></p>
                <p class="hint">Official `php:5.6-apache` base image with common MySQL extensions enabled.</p>
            </article>
            <article class="card">
                <p class="label">Web server</p>
                <p class="value">Apache 2.4</p>
                <p class="hint">Document root is mounted from the local `www` directory.</p>
            </article>
            <article class="card">
                <p class="label">Database</p>
                <p class="value">MariaDB</p>
                <p class="hint">Default database is `<?php echo e($databaseName); ?>`; default user is `<?php echo e($databaseUser); ?>`.</p>
            </article>
        </section>

        <section class="panel section">
            <h3>PHP MySQL extensions</h3>
            <div class="extensions">
                <?php foreach ($extensions as $extension): ?>
                    <?php $loaded = extension_loaded($extension); ?>
                    <div class="extension">
                        <span><?php echo e($extension); ?></span>
                        <span class="pill<?php echo $loaded ? '' : ' missing'; ?>"><?php echo $loaded ? 'Loaded' : 'Missing'; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
