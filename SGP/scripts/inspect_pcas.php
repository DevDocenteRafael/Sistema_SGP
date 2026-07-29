<?php
$dbPath = __DIR__ . '/../database/database.sqlite';
if (!file_exists($dbPath)) {
    echo "DB file not found: $dbPath\n";
    exit(1);
}
$db = new PDO('sqlite:' . $dbPath);
$stmt = $db->query("PRAGMA table_info('pcas')");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo implode('|', [$r['cid'], $r['name'], $r['type'], $r['notnull'], $r['dflt_value']]) . PHP_EOL;
}
