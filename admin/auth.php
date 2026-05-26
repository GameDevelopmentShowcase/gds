<?php    /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later
	      * auth.php
*/

session_start();

$authFile = __DIR__ . '/../data/admin-auth.json';

if (!file_exists($authFile)) {
    $defaultAuth = [
        'username' => 'admin',
        'password' => password_hash('admin', PASSWORD_DEFAULT)
    ];
    file_put_contents($authFile, json_encode($defaultAuth, JSON_PRETTY_PRINT));
    chmod($authFile, 0600);
}

function getAuthData() {
    global $authFile;
    if (!file_exists($authFile)) {
        return ['username' => 'admin', 'password' => password_hash('admin', PASSWORD_DEFAULT)];
    }
    $data = json_decode(file_get_contents($authFile), true);
    return is_array($data) ? $data : ['username' => 'admin', 'password' => password_hash('admin', PASSWORD_DEFAULT)];
}

function saveAuthData($username, $password) {
    global $authFile;
    $data = [
        'username' => trim($username),
        'password' => password_hash($password, PASSWORD_DEFAULT)
    ];
    return file_put_contents($authFile, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

$loginError = '';

if (isset($_POST['login_user'], $_POST['login_pass'])) {
    $auth = getAuthData();
    $user = trim($_POST['login_user']);
    $pass = $_POST['login_pass'];
    
    if ($user === $auth['username'] && password_verify($pass, $auth['password'])) {
        $_SESSION['gds_admin'] = true;
        header('Location: edit.php');
        exit;
    }
    $loginError = 'Incorrect login credentials.';
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: edit.php');
    exit;
}

function is_admin() {
    return !empty($_SESSION['gds_admin']);
}

function require_admin() {
    if (!is_admin()) {
        header('Location: edit.php');
        exit;
    }
}