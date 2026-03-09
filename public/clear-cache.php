<?php
// Clear Twig template cache
// Usage: visit /clear-cache.php or run: php public/clear-cache.php

$cacheDir = dirname(__DIR__) . '/storage/cache/twig';

if (is_dir($cacheDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    $count = 0;
    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
            $count++;
        }
    }
    echo "Cache cleared: {$count} files removed.\n";
} else {
    echo "No cache directory found.\n";
}
