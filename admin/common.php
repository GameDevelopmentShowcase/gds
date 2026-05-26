<?php    /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later
	      * common.php
*/

require_once 'auth.php';
require_admin();

$settingsFile = __DIR__ . '/../data/settings.json';
$settings = [];
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
}

$settings = array_merge($settings);

$activeTheme = $settings['active_theme'] ?? 'grün';
$themeFile = '../assets/css/frontend-' . $activeTheme . '.css';
if (!file_exists(__DIR__ . '/' . $themeFile) && !file_exists(__DIR__ . '/../assets/css/frontend-' . $activeTheme . '.css')) {
    $themeFile = '../assets/css/frontend-grün.css';
}
if (!is_dir(dirname($galleryFile))) {
    mkdir(dirname($galleryFile), 0775, true);
}
if (!is_dir(dirname($landingFile))) {
    mkdir(dirname($landingFile), 0775, true);
}
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

if (!is_dir(dirname($pagesFile))) {
    mkdir(dirname($pagesFile), 0775, true);
}

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

if (!is_dir(dirname($devlogFile))) {
    mkdir(dirname($devlogFile), 0775, true);
}
?>