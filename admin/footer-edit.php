<?php    /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later
	      * footer-edit.php
*/

require_once __DIR__ . '/common.php';

require_once __DIR__ . '/../includes/footer.php';
$footerFile = __DIR__ . '/../data/footer.json';
if (!is_dir(dirname($footerFile))) { mkdir(dirname($footerFile), 0775, true); }

$socialLabels = sz_footer_social_labels();

function footer_clean($value) { return trim($value ?? ''); }
function footer_url($value) {
    $value = trim($value ?? '');
    if ($value === '') { return ''; }
    if (preg_match('~^https?://~i', $value) || preg_match('~^mailto:[^\s@]+@[^\s@]+\.[^\s@]+~i', $value)) { return $value; }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $legalLinks = [];
    $legalLabels = $_POST['legal_label'] ?? [];
    $legalUrls = $_POST['legal_url'] ?? [];
    $legalCount = max(count($legalLabels), count($legalUrls));
    for ($i = 0; $i < $legalCount; $i++) {
        $label = footer_clean($legalLabels[$i] ?? '');
        $url = footer_url($legalUrls[$i] ?? '');
        if ($label !== '' && $url !== '') { $legalLinks[] = ['label' => $label, 'url' => $url]; }
    }

    $socials = [];
    foreach ($socialLabels as $key => $label) {
        $socials[$key] = footer_url($_POST['socials'][$key] ?? '');
    }

    $footer = [
        'enabled' => isset($_POST['enabled']),
        'brand_title' => footer_clean($_POST['brand_title'] ?? 'STATIC ZONE'),
        'tagline' => footer_clean($_POST['tagline'] ?? ''),
        'description' => footer_clean($_POST['description'] ?? ''),
        'copyright_name' => footer_clean($_POST['copyright_name'] ?? 'Static Zone'),
        'author' => footer_clean($_POST['author'] ?? ''),
        'contact_email' => filter_var(footer_clean($_POST['contact_email'] ?? ''), FILTER_VALIDATE_EMAIL) ? footer_clean($_POST['contact_email']) : '',
        'links_title' => footer_clean($_POST['links_title'] ?? 'Navigation'),
        'show_navigation' => isset($_POST['show_navigation']),
        'legal_title' => footer_clean($_POST['legal_title'] ?? 'Legal'),
        'legal_links' => $legalLinks,
        'social_title' => footer_clean($_POST['social_title'] ?? 'Follow'),
        'socials' => $socials
    ];

file_put_contents($footerFile, json_encode($footer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['saved' => true]);
    exit;
}

header('Location: footer-edit.php?saved=1');
exit;
}

$footer = sz_footer_data();
$legalLinks = is_array($footer['legal_links'] ?? null) ? $footer['legal_links'] : [];
?>

<!doctype html>
<html lang="de">

<?php require_once '../includes/head.php'; ?>

<body>
<div class="scanlines"></div>
<div class="header"><div class="logo"><?= htmlspecialchars($settings['header_name']) ?></div><a href="edit.php">back to admin</a></div>
<div class="container">
  <div class="page-title"><div><h1>Footer Editor</h1></div></div>
  <div id="status" class="status"><?= isset($_GET['saved']) ? 'Footer gespeichert.' : 'Ready.' ?></div>
  <p class="hint">Social media links are displayed only if a valid URL is entered. Empty fields automatically remain invisible in the footer.</p>

  <form method="post">
    <div class="devlog-box">
      <div class="check-row"><input type="checkbox" name="enabled" <?= !empty($footer['enabled']) ? 'checked' : '' ?>><label style="margin:0;">Show Footer</label></div>
      <div class="editor-grid">
        <div>
          <label>Footer Title / Brand</label>
          <input type="text" name="brand_title" value="<?= htmlspecialchars($footer['brand_title'] ?? '') ?>">
          <label>Tagline</label>
          <input type="text" name="tagline" value="<?= htmlspecialchars($footer['tagline'] ?? '') ?>">
          <label>Description</label>
          <textarea class="small-textarea" name="description"><?= htmlspecialchars($footer['description'] ?? '') ?></textarea>
          <label>contact Mail</label>
          <input type="email" name="contact_email" value="<?= htmlspecialchars($footer['contact_email'] ?? '') ?>" placeholder="mail@example.com">
        </div>
        <div>
          <label>Copyright Name</label>
          <input type="text" name="copyright_name" value="<?= htmlspecialchars($footer['copyright_name'] ?? '') ?>">
          <label>Autor / Studio</label>
          <input type="text" name="author" value="<?= htmlspecialchars($footer['author'] ?? '') ?>">
          <label>Navigation Title</label>
          <input type="text" name="links_title" value="<?= htmlspecialchars($footer['links_title'] ?? 'Navigation') ?>">
          <div class="check-row"><input type="checkbox" name="show_navigation" <?= !empty($footer['show_navigation']) ? 'checked' : '' ?>><label style="margin:0;">Show navigation in footer</label></div>
        </div>
      </div>
    </div>

    <div class="devlog-box">
      <div class="devlog-top"><div><small>Social Media</small><h2>Social Links</h2></div></div>
      <label>Social Title</label>
      <input type="text" name="social_title" value="<?= htmlspecialchars($footer['social_title'] ?? 'Follow') ?>">
      <div class="small-grid">
        <?php foreach ($socialLabels as $key => $label): ?>
          <div>
            <label><?= htmlspecialchars($label) ?></label>
            <input type="url" name="socials[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($footer['socials'][$key] ?? '') ?>" placeholder="https://...">
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="devlog-box">
      <div class="devlog-top"><div><small>Legal / Extra Links</small><h2>Additional Links</h2></div><button type="button" class="add-btn" onclick="addLegalLink()">+ Link</button></div>
      <label>Additional Link Title</label>
      <input type="text" name="legal_title" value="<?= htmlspecialchars($footer['legal_title'] ?? 'Legal') ?>">
      <div id="legalLinks">
        <?php foreach ($legalLinks as $link): ?>
          <div class="repeat-row">
            <input type="text" name="legal_label[]" value="<?= htmlspecialchars($link['label'] ?? '') ?>" placeholder="Label, z.B. Impressum">
            <input type="url" name="legal_url[]" value="<?= htmlspecialchars($link['url'] ?? '') ?>" placeholder="https://... oder mailto:...">
            <button type="button" class="delete-btn" onclick="this.closest('.repeat-row').remove()">Delete</button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="actions"><button class="save-btn" type="submit">Footer speichern</button></div>
  </form>
</div>
<script>
function addLegalLink() {
  const wrap = document.getElementById('legalLinks');
  const row = document.createElement('div');
  row.className = 'repeat-row';
  row.innerHTML = '<input type="text" name="legal_label[]" placeholder="Label, z.B. Impressum"><input type="url" name="legal_url[]" placeholder="https://... oder mailto:..."><button type="button" class="delete-btn" onclick="this.closest(\'.repeat-row\').remove()">Delete</button>';
  wrap.appendChild(row);
}

const form = document.querySelector('form');
const statusBox = document.getElementById('status');

form.addEventListener('submit', async e => {
  e.preventDefault();

  statusBox.textContent = 'Saving...';

  const response = await fetch(window.location.href, {
    method: 'POST',
    body: new FormData(form),
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  });

  statusBox.textContent = response.ok
    ? 'Footer saved.'
    : 'Error saving.';

  statusBox.scrollIntoView({
    behavior: 'smooth',
    block: 'center'
  });
});
</script>
</body>
</html>
