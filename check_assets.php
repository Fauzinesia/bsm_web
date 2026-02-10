<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Asset Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .check { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; }
        .ok { color: #28a745; }
        .error { color: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
    </style>
</head>
<body>
    <h1>Asset Verification</h1>
    
    <div class="check">
        <h2>Server Variables</h2>
        <table>
            <tr><th>Variable</th><th>Value</th></tr>
            <tr><td>PHP_SELF</td><td><?= htmlspecialchars($_SERVER['PHP_SELF'] ?? 'N/A') ?></td></tr>
            <tr><td>SCRIPT_NAME</td><td><?= htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'N/A') ?></td></tr>
            <tr><td>DOCUMENT_ROOT</td><td><?= htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') ?></td></tr>
            <tr><td>REQUEST_URI</td><td><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A') ?></td></tr>
        </table>
    </div>

    <div class="check">
        <h2>Path Calculation Test</h2>
        <?php
        $scriptPath = trim(str_replace('\\', '/', $_SERVER['SCRIPT_NAME']), '/');
        $segments = $scriptPath === '' ? [] : explode('/', $scriptPath);
        $segmentCount = count($segments);
        $levelToRoot = $segmentCount > 0 ? $segmentCount - 1 : 0;
        $assetBase = $levelToRoot > 0 ? str_repeat('../', $levelToRoot) : '';
        ?>
        <table>
            <tr><th>Item</th><th>Value</th></tr>
            <tr><td>Script Path</td><td><?= htmlspecialchars($scriptPath) ?></td></tr>
            <tr><td>Segments</td><td><?= htmlspecialchars(implode(' / ', $segments)) ?></td></tr>
            <tr><td>Segment Count</td><td><?= $segmentCount ?></td></tr>
            <tr><td>Level to Root</td><td><?= $levelToRoot ?></td></tr>
            <tr><td>Asset Base</td><td>'<?= htmlspecialchars($assetBase) ?>'</td></tr>
        </table>
    </div>

    <div class="check">
        <h2>File Existence Check</h2>
        <table>
            <tr><th>File</th><th>Path</th><th>Status</th></tr>
            <?php
            $files = [
                'style.css' => 'assets/css/style.css',
                'tabler-icons.min.css' => 'assets/fonts/tabler-icons.min.css',
                'feather.css' => 'assets/fonts/feather.css',
                'fontawesome.css' => 'assets/fonts/fontawesome.css',
                'material.css' => 'assets/fonts/material.css',
                'component.js' => 'assets/js/component.js',
                'theme.js' => 'assets/js/theme.js',
                'script.js' => 'assets/js/script.js',
                'feather.min.js' => 'assets/js/plugins/feather.min.js',
            ];
            foreach ($files as $name => $path) {
                $fullPath = __DIR__ . '/' . $path;
                $exists = file_exists($fullPath);
                $class = $exists ? 'ok' : 'error';
                $status = $exists ? '✓ EXISTS' : '✗ NOT FOUND';
                echo "<tr class='$class'><td>$name</td><td>$path</td><td><strong>$status</strong></td></tr>";
            }
            ?>
        </table>
    </div>

    <div class="check">
        <h2>Test Asset Loading</h2>
        <p>Actual CSS path: <code><?= htmlspecialchars($assetBase . 'assets/css/style.css') ?></code></p>
        <p>For admin/dashboard.php it should be: <code>../assets/css/style.css</code></p>
        <p><a href="admin/dashboard.php">Go to Dashboard</a></p>
    </div>
</body>
</html>
