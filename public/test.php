<?php
echo '<h1>PHP werkt!</h1>';
echo '<p>Document root: ' . $_SERVER['DOCUMENT_ROOT'] . '</p>';
echo '<p>Script: ' . $_SERVER['SCRIPT_FILENAME'] . '</p>';
echo '<p>Host: ' . ($_SERVER['HTTP_HOST'] ?? 'onbekend') . '</p>';
echo '<p>PHP versie: ' . phpversion() . '</p>';

echo '<h2>Autoloader check:</h2>';
$autoloader = __DIR__ . '/../vendor/autoload.php';
echo '<p>vendor/autoload.php: ' . (file_exists($autoloader) ? 'GEVONDEN' : 'NIET GEVONDEN - run composer install!') . '</p>';

echo '<h2>.env check:</h2>';
$envFile = __DIR__ . '/../.env';
echo '<p>.env bestand: ' . (file_exists($envFile) ? 'GEVONDEN' : 'NIET GEVONDEN') . '</p>';

echo '<h2>Twig cache schrijfbaar:</h2>';
$cacheDir = __DIR__ . '/../storage/cache/twig';
echo '<p>storage/cache/twig: ' . (is_writable($cacheDir) ? 'SCHRIJFBAAR' : 'NIET SCHRIJFBAAR - chmod 775!') . '</p>';

echo '<h2>mod_rewrite check:</h2>';
echo '<p>Probeer <a href="/">/</a> te laden. Als je dan dit bestand ziet ipv de homepage, werkt mod_rewrite niet.</p>';
