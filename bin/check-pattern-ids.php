<?php

declare(strict_types=1);

$pattern_dir = dirname(__DIR__) . '/patterns';
$files = glob($pattern_dir . '/*.php') ?: [];

$ids = [];

foreach ($files as $file) {
    if (str_starts_with(basename($file), '_')) {
        continue;
    }

    preg_match_all('/"id":"(gsbp-[a-z0-9]+)"/', file_get_contents($file), $matches);

    foreach ($matches[1] as $id) {
        $ids[$id][] = basename($file);
    }
}

$dupes = array_filter($ids, fn($files) => count($files) > 1);

if (empty($dupes)) {
    $total = array_sum(array_map('count', $ids));
    echo "OK — {$total} IDs across " . count($files) . " patterns, no duplicates.\n";
    exit(0);
}

echo "DUPLICATE BLOCK IDs FOUND:\n";
foreach ($dupes as $id => $files) {
    echo "  {$id}  →  " . implode(', ', $files) . "\n";
}
exit(1);
