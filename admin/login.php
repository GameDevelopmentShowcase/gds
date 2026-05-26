<?php    /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later
	      * login.php
*/

require_once __DIR__ . '/common.php';
?>

<!doctype html>
<html lang="de">
<head>

<?php require_once '../includes/head.php'; ?>

</head>
<body class="login-page">

<form class="login-box" method="post">

    <h2>Admin Login</h2>

    <?php if (!empty($loginError)): ?>
        <div class="error"><?= htmlspecialchars($loginError) ?></div>
    <?php endif; ?>

    <input type="text" name="login_user" placeholder="User Name" required>
    <input type="password" name="login_pass" placeholder="Password" required>
    <button type="submit">LOGIN</button>

</form>

</body>
</html>
