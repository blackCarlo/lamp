<?php
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$host = getenv('MYSQL_HOST') ? getenv('MYSQL_HOST') : '127.0.0.1';
$port = getenv('MYSQL_PORT') ? (int) getenv('MYSQL_PORT') : 3306;
$user = getenv('MYSQL_USER') ? getenv('MYSQL_USER') : 'root';
if ($user !== 'root') {
    $pass = getenv('MYSQL_PASSWORD') ? getenv('MYSQL_PASSWORD') : '';
} else {
    $pass = getenv('MYSQL_ROOT_PASSWORD') ? getenv('MYSQL_ROOT_PASSWORD') : (getenv('MYSQL_PASS') ? getenv('MYSQL_PASS') : 'root');
}
$db = getenv('MYSQL_DATABASE') ? getenv('MYSQL_DATABASE') : '';

$connected = false;
$error = '';
$serverVersion = 'Unavailable';
$clientVersion = function_exists('mysqli_get_client_info') ? mysqli_get_client_info() : 'Unavailable';
$charset = 'Unavailable';
$currentUser = 'Unavailable';
$currentDatabase = $db !== '' ? $db : 'default connection';
$tables = array();

$conn = @new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    $error = $conn->connect_error;
} else {
    $connected = true;
    $conn->set_charset('utf8');
    $serverVersion = $conn->server_info;
    $charset = $conn->character_set_name();

    $statusResult = $conn->query('SELECT DATABASE() AS db_name, USER() AS user_name');
    if ($statusResult) {
        $status = $statusResult->fetch_assoc();
        $currentDatabase = $status['db_name'];
        $currentUser = $status['user_name'];
        $statusResult->free();
    }

    $tablesResult = $conn->query('SHOW TABLES');
    if ($tablesResult) {
        while ($row = $tablesResult->fetch_array(MYSQLI_NUM)) {
            $tables[] = $row[0];
        }
        $tablesResult->free();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MySQL Status</title>
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
            --danger: #dc2626;
            --warning-bg: #fff7ed;
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
            width: min(1040px, calc(100% - 32px));
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

        .status-panel {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(300px, .9fr);
            gap: 18px;
            align-items: stretch;
            margin-bottom: 18px;
        }

        .panel {
            border: 1px solid rgba(219, 227, 238, .9);
            border-radius: 8px;
            background: rgba(255, 255, 255, .9);
            box-shadow: var(--shadow);
        }

        .summary {
            padding: 30px;
            min-height: 260px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .eyebrow {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            color: <?php echo $connected ? 'var(--brand-dark)' : 'var(--danger)'; ?>;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: <?php echo $connected ? 'var(--ok)' : 'var(--danger)'; ?>;
            box-shadow: 0 0 0 5px <?php echo $connected ? 'rgba(22, 163, 74, .14)' : 'rgba(220, 38, 38, .12)'; ?>;
        }

        .summary h2 {
            margin: 0;
            max-width: 620px;
            font-size: clamp(32px, 5vw, 54px);
            line-height: 1;
            letter-spacing: 0;
        }

        .summary p {
            margin: 18px 0 0;
            max-width: 620px;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.7;
        }

        .alert {
            margin-top: 22px;
            padding: 14px 16px;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            background: var(--warning-bg);
            color: #9a3412;
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .connection {
            padding: 24px;
        }

        .connection h3,
        .table-panel h3 {
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
            min-height: 140px;
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            box-shadow: 0 12px 30px rgba(15, 23, 42, .07);
        }

        .label {
            margin: 0 0 12px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .value {
            margin: 0;
            font-size: 24px;
            font-weight: 850;
            overflow-wrap: anywhere;
        }

        .hint {
            margin: 14px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.55;
        }

        .table-panel {
            padding: 24px;
        }

        .table-list {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 10px;
        }

        .table-name,
        .empty {
            min-height: 54px;
            display: flex;
            align-items: center;
            padding: 0 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fbfdff;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .empty {
            grid-column: 1 / -1;
            color: var(--muted);
            font-weight: 700;
        }

        .pill {
            display: inline-flex;
            min-height: 28px;
            align-items: center;
            justify-content: center;
            padding: 0 12px;
            border-radius: 999px;
            color: #fff;
            background: <?php echo $connected ? 'var(--ok)' : 'var(--danger)'; ?>;
            font-size: 12px;
            font-weight: 800;
        }

        @media (max-width: 820px) {
            .topbar,
            .status-panel {
                display: grid;
                grid-template-columns: 1fr;
            }

            .nav {
                justify-content: flex-start;
            }

            .grid,
            .table-list {
                grid-template-columns: 1fr;
            }

            .summary {
                min-height: auto;
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="topbar">
            <a class="brand" href="/">
                <span class="logo">DB</span>
                <span>
                    <h1>MySQL Status</h1>
                    <p>mysqli connection check for the local LAMP stack</p>
                </span>
            </a>
            <nav class="nav" aria-label="Primary">
                <a href="/">Dashboard</a>
                <a href="/info.php">phpinfo()</a>
            </nav>
        </header>

        <section class="status-panel">
            <div class="panel summary">
                <div>
                    <span class="eyebrow"><span class="dot"></span><?php echo $connected ? ' Connected' : ' Connection failed'; ?></span>
                    <h2><?php echo $connected ? 'Database is ready.' : 'Database is unreachable.'; ?></h2>
                    <p><?php echo $connected ? 'The PHP mysqli extension can connect to MariaDB with the configured MYSQL_* credentials.' : 'The page loaded, but mysqli could not open a database connection with the configured credentials.'; ?></p>
                    <?php if (!$connected): ?>
                        <div class="alert"><?php echo e($error); ?></div>
                    <?php endif; ?>
                </div>
                <span class="pill"><?php echo $connected ? 'Healthy' : 'Check logs'; ?></span>
            </div>

            <aside class="panel connection">
                <h3>Connection</h3>
                <div class="metric">
                    <span>Host</span>
                    <strong><?php echo e($host . ':' . $port); ?></strong>
                </div>
                <div class="metric">
                    <span>Database</span>
                    <strong><?php echo e($currentDatabase); ?></strong>
                </div>
                <div class="metric">
                    <span>User</span>
                    <strong><?php echo e($currentUser); ?></strong>
                </div>
                <div class="metric">
                    <span>Charset</span>
                    <strong><?php echo e($charset); ?></strong>
                </div>
            </aside>
        </section>

        <section class="grid" aria-label="Database summary">
            <article class="card">
                <p class="label">Server version</p>
                <p class="value"><?php echo e($serverVersion); ?></p>
                <p class="hint">MariaDB is managed by supervisor inside the same container.</p>
            </article>
            <article class="card">
                <p class="label">Client library</p>
                <p class="value"><?php echo e($clientVersion); ?></p>
                <p class="hint">PHP uses mysqli to verify the active database connection.</p>
            </article>
            <article class="card">
                <p class="label">Tables</p>
                <p class="value"><?php echo count($tables); ?></p>
                <p class="hint">Tables are listed from the current default database.</p>
            </article>
        </section>

        <section class="panel table-panel">
            <h3>Tables in <?php echo e($currentDatabase); ?></h3>
            <div class="table-list">
                <?php if ($connected && count($tables) > 0): ?>
                    <?php foreach ($tables as $table): ?>
                        <div class="table-name"><?php echo e($table); ?></div>
                    <?php endforeach; ?>
                <?php elseif ($connected): ?>
                    <div class="empty">No tables found in this database yet.</div>
                <?php else: ?>
                    <div class="empty">Connect to the database before loading table metadata.</div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
<?php
if ($connected) {
    $conn->close();
}
?>
