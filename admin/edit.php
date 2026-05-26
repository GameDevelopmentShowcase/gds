<?php    /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later
	      * edit.php
*/

header('Content-Type: text/html; charset=utf-8');
require_once 'auth.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
$settingsFile = __DIR__ . '/../data/settings.json';
$settings = [];

if (!is_dir(__DIR__ . '/../data')) {
    die('ERROR: data-Ordner existiert nicht unter: ' . __DIR__ . '/../data');
}
if (!is_writable(__DIR__ . '/../data')) {
    die('ERROR: The data folder is not writable. Current permissions:' . substr(sprintf('%o', fileperms(__DIR__ . '/../data')), -4));
}

if (file_exists($settingsFile)) {
    $content = file_get_contents($settingsFile);
    $settings = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die('ERROR: settings.json contains invalid JSON:' . json_last_error_msg());
    }
} else {
    $testWrite = file_put_contents($settingsFile, json_encode([], JSON_PRETTY_PRINT));
    if ($testWrite === false) {
        die('ERROR: Could not create settings.json in:' . $settingsFile);
    }
    chmod($settingsFile, 0666);
    $settings = [];
}

if (!is_array($settings)) {
    $settings = [];
}

$defaultSettings = [
    'site_title' => 'Game Development Showcase',
    'site_description' => 'This is a system designed to make the development of a game available to the public.',
    'favicon_standard' => '/assets/img/favicon-96x96.png',
    'favicon_svg' => '/assets/img/favicon.svg',
    'favicon_ico' => '/assets/img/favicon.ico',
    'favicon_apple' => '/assets/img/apple-touch-icon.png',
    'favicon_manifest' => '/assets/img/site.webmanifest',
    'header_logo' => 'assets/img/logo-mini.png',
    'header_name' => 'Game Development Showcase',
    'active_theme' => 'grün',
    'show_credit_link' => true
];

$settings = array_merge($defaultSettings, $settings);

$activeTheme = $settings['active_theme'] ?? 'grün';
$themeFile = '../assets/css/frontend-' . $activeTheme . '.css';
if (!file_exists(__DIR__ . '/' . $themeFile) && !file_exists(__DIR__ . '/../assets/css/frontend-' . $activeTheme . '.css')) {
    $themeFile = '../assets/css/frontend-grün.css';
}

$saveSuccess = false;
$saveError = false;
$errorMessage = '';

function handleImageUpload($fileKey, $settingKey, &$settings, &$saveError, &$errorMessage) {

    if (
        !isset($_FILES[$fileKey]) ||
        $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return;
    }

    if ($_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {

        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File too large',
            UPLOAD_ERR_FORM_SIZE => 'Form limit exceeded',
            UPLOAD_ERR_CANT_WRITE => 'File could not be saved.',
        ];

        $errorCode = $_FILES[$fileKey]['error'];

        $saveError = true;
        $errorMessage = $uploadErrors[$errorCode] ?? 'Upload Error';

        return;
    }

    $uploadDir = __DIR__ . '/../assets/uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!is_writable($uploadDir)) {
        $saveError = true;
        $errorMessage = 'Upload folder not writable';
        return;
    }

$extension = strtolower(
    pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION)
);

$finfo = finfo_open(FILEINFO_MIME_TYPE);

$mime = finfo_file(
    $finfo,
    $_FILES[$fileKey]['tmp_name']
);

finfo_close($finfo);

$allowed = [
    'jpg',
    'jpeg',
    'png',
    'gif',
    'webp',
    'svg',
    'ico'
];

$allowedMime = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/svg+xml',
    'image/x-icon',
    'image/vnd.microsoft.icon'
];

if (!in_array($mime, $allowedMime)) {

    $saveError = true;
    $errorMessage = 'Invalid MIME-Type';

    return;
}
    if (!in_array($extension, $allowed)) {

        $saveError = true;
        $errorMessage = 'Invalid Dateiformat';

        return;
    }

    $newName = $settingKey . '-' . time() . '.' . $extension;

    $targetPath = $uploadDir . $newName;

    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {

if (!empty($settings[$settingKey])) {
    $cleanPath = ltrim($settings[$settingKey], '/');
    $oldFile = __DIR__ . '/../' . $cleanPath;
    if (
        str_contains($settings[$settingKey], 'assets/uploads/')
        && file_exists($oldFile)
    ) {
        unlink($oldFile);
    }
}

        $settings[$settingKey] = '/assets/uploads/' . $newName;

    } else {

        $saveError = true;
        $errorMessage = 'Error saving the file';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_general'])) {
    $settings['site_title'] = trim($_POST['site_title'] ?? $defaultSettings['site_title']);
    $settings['site_description'] = trim($_POST['site_description'] ?? $defaultSettings['site_description']);
    $settings['favicon_manifest'] = trim($_POST['favicon_manifest'] ?? $defaultSettings['favicon_manifest']);
    $settings['header_name'] = trim($_POST['header_name'] ?? $defaultSettings['header_name']);
    $settings['show_credit_link'] = isset($_POST['show_credit_link']) ? true : false;
    $settings['active_theme'] = $_POST['active_theme'] ?? 'grün';

handleImageUpload(
    'header_logo_upload',
    'header_logo',
    $settings,
    $saveError,
    $errorMessage
);

handleImageUpload(
    'favicon_standard_upload',
    'favicon_standard',
    $settings,
    $saveError,
    $errorMessage
);

handleImageUpload(
    'favicon_svg_upload',
    'favicon_svg',
    $settings,
    $saveError,
    $errorMessage
);

handleImageUpload(
    'favicon_ico_upload',
    'favicon_ico',
    $settings,
    $saveError,
    $errorMessage
);

handleImageUpload(
    'favicon_apple_upload',
    'favicon_apple',
    $settings,
    $saveError,
    $errorMessage
);

    if (!$saveError) {
        $jsonData = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        if ($jsonData === false) {
            $saveError = true;
            $errorMessage = 'JSON Encoding Error';
        } else {
            $result = file_put_contents($settingsFile, $jsonData);
            if ($result === false) {
                $saveError = true;
                $errorMessage = 'Error saving settings.json';
            } else {
                $saveSuccess = true;
            }
        }
    }
}

$passwordSuccess = false;
$passwordError = false;
$passwordErrorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_password'])) {
    require_once 'auth.php';
    
    $currentPassword = $_POST['current_password'] ?? '';
    $newUsername = trim($_POST['new_username'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    $auth = getAuthData();
    
    if (!password_verify($currentPassword, $auth['password'])) {
        $passwordError = true;
        $passwordErrorMessage = 'Current password is incorrect.';
    }
    elseif (empty($newUsername) || strlen($newUsername) < 3) {
        $passwordError = true;
        $passwordErrorMessage = 'Username must be at least 3 characters long.';
    }
    elseif (empty($newPassword) || strlen($newPassword) < 6) {
        $passwordError = true;
        $passwordErrorMessage = 'New password must be at least 6 characters long.';
    }
    elseif ($newPassword !== $confirmPassword) {
        $passwordError = true;
        $passwordErrorMessage = 'New passwords do not match.';
    }
    else {
        if (saveAuthData($newUsername, $newPassword)) {
            $passwordSuccess = true;
            $_SESSION['gds_admin'] = true;
        } else {
            $passwordError = true;
            $passwordErrorMessage = 'Error saving login data.';
        }
    }
}

?>

<!doctype html>
<html lang="de">
<head>

<?php require_once '../includes/head.php'; ?>

</head>
<body>

<?php if (!is_admin()): ?>
<div class="login-wrapper">
    <form class="login-box" method="post">
        <h2>Admin Login</h2>
        <?php if (!empty($loginError)): ?>
            <div class="error"><?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        <input type="text" name="login_user" placeholder="User Name" required>
        <input type="password" name="login_pass" placeholder="Password" required>
        <button type="submit">LOGIN</button>
    </form>
</div>
<?php exit; endif; ?>

<div class="scanlines"></div>
<div class="header">
    <div class="logo"><?= htmlspecialchars($settings['header_name']) ?></div>
    <div style="flex: 1; text-align: right;">
        <a href="../">back to Website</a> | 
        <a href="edit.php?logout=1" style="text-decoration:none; font-weight:normal; color:#ffffff;">logout</a>
    </div>
</div>

<div class="container">
    <div class="page-title">
        <p>Administration Control</p>
        <span style="font-size:40px;">Edit Area</span>
    </div>

    <?php if ($saveSuccess): ?>
        <div class="save-success">✓ Settings successfully saved!</div>
    <?php elseif ($saveError): ?>
        <div class="save-error">✗ Error saving settings: <?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <div class="admin-grid">
        <div class="admin-card">
            <small>Global Settings</small>
            <h2>Generally</h2>
            <p>
Manage website title, description, favicons, logo, and header name.
All SEO-relevant meta tags are centrally controlled here.
            </p>
            <a href="#" onclick="document.getElementById('general-settings').scrollIntoView({behavior:'smooth'}); return false;">
                Open Editor &#10141;
            </a>
        </div>

        <div class="admin-card">
            <small>Landing Page Control</small>
            <h2>Landing Editor</h2>
            <p>Sort, activate/deactivate sections; manage media videos and create small, free-form areas.</p>
            <a href="landing-edit.php">Open Editor &#10141;</a>
        </div>

        <div class="admin-card">
            <small>Transmission Control</small>
            <h2>News Editor</h2>
            <p>Manage news, updates, and announcements. Edit Markdown, images, and order.</p>
            <a href="news-edit.php">Open Editor &#10141;</a>
        </div>

        <div class="admin-card">
            <small>Development Archive</small>
            <h2>Devlog Editor</h2>
            <p>Write devlogs, organize categories, manage uploads, and sort entries.</p>
            <a href="devlog-edit.php">Open Editor &#10141;</a>
        </div>

        <div class="admin-card">
            <small>Free Page Control</small>
            <h2>Pages Editor</h2>
            <p>Create custom pages, edit Markdown content, control navigation order, and publish.</p>
            <a href="pages-edit.php">Open Editor &#10141;</a>
        </div>

        <div class="admin-card">
            <small>Visual Archive Control</small>
            <h2>Gallery Editor</h2>
            <p>und Featured Images festlegen.</p>
            <a href="gallery-edit.php">Open Editor &#10141;</a>
        </div>

        <div class="admin-card">
            <small>Footer Control</small>
            <h2>Footer Editor</h2>
            <p>Manage footer content, contact details, navigation, legal links, and social media channels.</p>
            <a href="footer-edit.php">Open Editor &#10141;</a>
        </div>
    </div>
<br><br>
    <div id="general-settings" style="margin-top: 60px; padding-top: 40px; border-top: 1px solid rgba(255,159,24,.2);">
        <div class="page-title" style="margin-bottom: 30px;">
            <div>
                <p>Global Configuration</p>
                <h1 style="font-size: 48px;">General Settings</h1>
            </div>
        </div>

        <form method="post" class="settings-form" enctype="multipart/form-data">
            <h3 style="color: var(--accent); font-family: Rajdhani; margin-bottom: 20px;">Meta Information (SEO)</h3>
            
            <div class="form-group">
                <label>Website Titele (Meta Title)</label>
                <input type="text" name="site_title" value="<?= htmlspecialchars($settings['site_title']) ?>" maxlength="120">
                <div class="hint">Appears in the browser tab and in Google search results. Max. 60–70 characters recommended.</div>
            </div>

            <div class="form-group">
                <label>Website Description (Meta Description)</label>
                <textarea name="site_description" rows="3" maxlength="320"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                <div class="hint">Displayed in search results below the title. Max. 150–160 characters recommended.</div>
            </div>

            <h3 style="color: var(--accent); font-family: Rajdhani; margin: 30px 0 20px;">Favicons</h3>
            <div class="favicon-grid">
                <div class="form-group">
                    <label>Favicon Standard (96x96)</label>
                    <?php if (!empty($settings['favicon_standard'])): ?>
                        <div style="margin-bottom:10px;">
                            <img src="../<?= htmlspecialchars($settings['favicon_standard']) ?>" style="max-height:48px;">
                            <br>
                            <small><?= htmlspecialchars($settings['favicon_standard']) ?></small>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="favicon_standard_upload" accept="image/*">
                    <div class="hint">PNG recommended (96x96)</div>
                </div>
                
                <div class="form-group">
                    <label>Favicon SVG</label>
                    <?php if (!empty($settings['favicon_svg'])): ?>
                        <div style="margin-bottom:10px;">
                            <img src="../<?= htmlspecialchars($settings['favicon_svg']) ?>" style="width:48px;height:48px;background:#111;border:1px solid #333;padding:4px;">
                            <br>
                            <small><?= htmlspecialchars($settings['favicon_svg']) ?></small>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="favicon_svg_upload" accept=".svg,image/svg+xml">
                </div>
                
                <div class="form-group">
                    <label>Favicon ICO</label>
                    <?php if (!empty($settings['favicon_ico'])): ?>
                        <div style="margin-bottom:10px;">
                            <img src="../<?= htmlspecialchars($settings['favicon_ico']) ?>" style="width:32px;height:32px;border:1px solid #333;background:#111;padding:4px;" onerror="this.style.display='none';">
                            <br>
                            <a href="../<?= htmlspecialchars($settings['favicon_ico']) ?>" target="_blank" style="font-size:12px;color:#ff9f18;">ICO open</a>
                            <br>
                            <small><?= htmlspecialchars($settings['favicon_ico']) ?></small>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="favicon_ico_upload" accept=".ico">
                </div>
                
                <div class="form-group">
                    <label>Apple Touch Icon</label>
                    <?php if (!empty($settings['favicon_apple'])): ?>
                        <div style="margin-bottom:10px;">
                            <img src="../<?= htmlspecialchars($settings['favicon_apple']) ?>" style="max-height:64px;">
                            <br>
                            <small><?= htmlspecialchars($settings['favicon_apple']) ?></small>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="favicon_apple_upload" accept="image/*">
                </div>
            </div>
            
            <div class="form-group">
                <label>Webmanifest</label>
                <input type="text" name="favicon_manifest" value="<?= htmlspecialchars($settings['favicon_manifest']) ?>" placeholder="/assets/img/site.webmanifest">
            </div>
            
            <h3 style="color: var(--accent); font-family: Rajdhani; margin: 30px 0 20px;">Header Branding</h3>
            
            <div class="form-group">
                <label>Header Logo Upload</label>
                <?php if (!empty($settings['header_logo'])): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="../<?= htmlspecialchars($settings['header_logo']) ?>" style="max-height:80px;">
                        <br><small>Current Logo (Pfad: <?= htmlspecialchars($settings['header_logo']) ?>)</small>
                    </div>
                <?php endif; ?>
                <input type="file" name="header_logo_upload" accept="image/*">
                <div class="hint">Supported Formats: JPG, PNG, GIF, WEBP, SVG</div>
            </div>

            <div class="form-group">
                <label>Header Name / Brand</label>
                <input type="text" name="header_name" value="<?= htmlspecialchars($settings['header_name']) ?>" placeholder="" maxlength="50">
                <div class="hint">Displayed next to the logo in the header.</div>
            </div>

            <h3 style="color: var(--accent); font-family: Rajdhani; margin: 30px 0 20px;">Theme Settings</h3>

            <div class="form-group">
                <label>Color Scheme / Theme</label>
                <select name="active_theme" style="width: 100%; background: var(--panel2); color: var(--text); border: 1px solid rgba(var(--accent-rgb), .2); padding: 14px; font-size: 16px;">
                    <option value="grün" <?= ($settings['active_theme'] ?? 'grün') === 'grün' ? 'selected' : '' ?>>Green (Default) - Cyber/Neon Theme</option>
                    <option value="orange" <?= ($settings['active_theme'] ?? 'grün') === 'orange' ? 'selected' : '' ?>>Orange - Warm/Dystopian Theme</option>
                    <option value="blau" <?= ($settings['active_theme'] ?? 'grün') === 'blau' ? 'selected' : '' ?>>Blue</option>
                    <option value="darkred" <?= ($settings['active_theme'] ?? 'grün') === 'darkred' ? 'selected' : '' ?>>Dark Red - Blood/Hell Theme</option>
                </select>
                <div class="hint">Select the visual appearance for the entire website. The theme is applied immediately to the homepage and all subpages.</div>
            </div>

            <div class="form-group">
                <div class="preview-box" style="margin-top: 15px; padding: 20px; background: rgba(0,0,0,0.3); border: 1px solid var(--line); border-radius: 4px;">
                    <small style="color: var(--accent); display: block; margin-bottom: 10px;">🎨 Live Preview:</small>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                        <div style="background: var(--accent); width: 40px; height: 40px; border-radius: 4px;"></div>
                        <div style="background: var(--bg); padding: 8px 16px; border: 1px solid var(--accent); color: var(--text); font-family: monospace;">bg / text</div>
                        <div style="background: var(--panel); padding: 8px 16px; border: 1px solid var(--line); color: var(--text-light);">panel</div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="check-row">
                    <input type="checkbox" name="show_credit_link" id="show_credit_link" <?= ($settings['show_credit_link'] ?? false) ? 'checked' : '' ?>>
                    <label for="show_credit_link">Show “Powered by Game Development Showcase” in the footer</label>
                </div>
                <div class="hint">Supports the further development of the system. Can be deactivated at any time.</div>
            </div>

            <div class="actions" style="margin-top: 30px;">
                <button type="submit" name="save_general" class="save-btn">Save settings</button>
            </div>
        </form>

<h3 style="color: var(--accent); font-family: Rajdhani; margin: 30px 0 20px;">Security / Login Settings</h3>

            <?php if ($passwordSuccess): ?>
                <div class="save-success">✓ Login credentials successfully changed!</div>
            <?php elseif ($passwordError): ?>
                <div class="save-error">✗ Error: <?= htmlspecialchars($passwordErrorMessage) ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label>Current Username</label>
                <?php 
                    require_once 'auth.php';
                    $currentAuth = getAuthData();
                ?>
                <input type="text" value="<?= htmlspecialchars($currentAuth['username']) ?>" disabled style="opacity: 0.6; cursor: not-allowed;">
                <div class="hint">Your current admin username.</div>
            </div>

            <form method="post" class="settings-form" style="margin-top: 20px; padding: 20px; background: rgba(0,0,0,0.2); border: 1px solid var(--line); border-radius: 4px;">
                <h4 style="color: var(--accent); margin-bottom: 15px;">Change Login Credentials</h4>
                
                <div class="form-group">
                    <label>Current Password *</label>
                    <input type="password" name="current_password" required autocomplete="current-password">
                    <div class="hint">Enter your current password to verify your identity.</div>
                </div>

                <div class="form-group">
                    <label>New Username *</label>
                    <input type="text" name="new_username" value="<?= htmlspecialchars($currentAuth['username']) ?>" required minlength="3" autocomplete="username">
                    <div class="hint">Minimum 3 characters. This is your login name.</div>
                </div>

                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" required minlength="6" autocomplete="new-password">
                    <div class="hint">Minimum 6 characters. Use a strong password.</div>
                </div>

                <div class="form-group">
                    <label>Confirm New Password *</label>
                    <input type="password" name="confirm_password" required minlength="6" autocomplete="new-password">
                    <div class="hint">Re-enter your new password.</div>
                </div>

                <div class="actions" style="margin-top: 20px;">
                    <button type="submit" name="save_password" class="save-btn">Change Login Credentials</button>
                </div>
            </form>
			
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const themeSelect = document.querySelector('select[name="active_theme"]');
    const saveButton = document.querySelector('button[name="save_general"]');
    const previewBox = document.querySelector('.preview-box');
    
    if (!themeSelect) return;

    const themeFiles = {
        'orange': '../assets/css/frontend-orange.css',
        'blau': '../assets/css/frontend-blau.css',
        'darkred': '../assets/css/frontend-darkred.css',
        'grün': '../assets/css/frontend-grün.css'
    };

    function switchMainTheme(themeName) {
        const themeFile = themeFiles[themeName] || themeFiles['grün'];
        
        let mainCss = document.querySelector('link[rel="stylesheet"][href*="frontend-"]');
        
        if (mainCss) {
            mainCss.href = themeFile;
        } else {
            let newCss = document.createElement('link');
            newCss.rel = 'stylesheet';
            newCss.href = themeFile;
            document.head.appendChild(newCss);
        }
        
        document.body.style.transition = 'opacity 0.15s ease';
        document.body.style.opacity = '0.98';
        setTimeout(() => {
            document.body.style.opacity = '1';
        }, 100);
    }

    function switchThemePreview(themeName) {
        const themeFile = themeFiles[themeName] || themeFiles['grün'];
        
        let previewLink = document.getElementById('theme-preview');
        
        if (previewLink) {
            previewLink.href = themeFile;
        } else {
            previewLink = document.createElement('link');
            previewLink.id = 'theme-preview';
            previewLink.rel = 'stylesheet';
            previewLink.href = themeFile;
            document.head.appendChild(previewLink);
        }

        if (previewBox) {
            previewBox.style.transition = 'all 0.2s ease';
            previewBox.style.transform = 'scale(1.02)';
            setTimeout(() => {
                previewBox.style.transform = 'scale(1)';
            }, 200);
        }
    }

    themeSelect.addEventListener('change', function() {
        switchThemePreview(this.value);
    });

    if (saveButton) {
        saveButton.addEventListener('click', function(e) {
            const selectedTheme = themeSelect.value;

            switchMainTheme(selectedTheme);

            setTimeout(() => {
                const saveSuccessDiv = document.querySelector('.save-success');
                if (saveSuccessDiv) {
                    saveSuccessDiv.style.display = 'block';
                    setTimeout(() => {
                        saveSuccessDiv.style.opacity = '0';
                        setTimeout(() => {
                            saveSuccessDiv.style.display = 'none';
                            saveSuccessDiv.style.opacity = '1';
                        }, 500);
                    }, 2000);
                }
            }, 100);
        });
    }
    const currentTheme = themeSelect.value;
    switchMainTheme(currentTheme);
});
</script>

</body>
</html>