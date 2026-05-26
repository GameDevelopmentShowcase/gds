<?php    /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later
	      * gallery-section.php
*/

$pagesFile = __DIR__ . '/data/pages.json';
$pages = file_exists($pagesFile)
    ? json_decode(file_get_contents($pagesFile), true)
    : [];

	if (!is_array($pages)) {
		$pages = [];
	}
$navPages = array_filter($pages, function ($item) {
    return !empty($item['published'])
        && (($item['show_in_nav'] ?? true) !== false)
        && !empty($item['slug'])
        && !empty($item['title']);
});

$devlogFile = __DIR__ . '/data/devlog.json';

$items = file_exists($devlogFile)
    ? json_decode(file_get_contents($devlogFile), true)
    : [];

if (!is_array($items)) {
    $items = [];
}

$id = $_GET['id'] ?? '';
$post = null;

foreach ($items as $item) {
    if (($item['id'] ?? '') === $id && !empty($item['published'])) {
        $post = $item;
        break;
    }
}

$settingsFile = __DIR__ . '/data/settings.json';
$settings = [];
	if (file_exists($settingsFile)) {
		$settings = json_decode(file_get_contents($settingsFile), true);
	}
$activeTheme = $settings['active_theme'] ?? 'blau';
$themeFile = 'assets/css/frontend-' . $activeTheme . '.css';
	if (!file_exists(__DIR__ . '/' . $themeFile)) {
		$themeFile = 'assets/css/frontend-blau.css';
	}
$settings = array_merge($settings);

	
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function simple_markdown($text) {
    $text = htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    $text = preg_replace('~&lt;br\s*/?&gt;~i', '<br>', $text);
    $text = preg_replace("~<br>\s*\n~i", '<br>', $text);

    $text = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $text);

    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);

    $text = preg_replace('/!\[(.*?)\]\((.*?)\)/', '<img src="$2" alt="$1">', $text);
    $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $text);

    $blocks = preg_split("/\n\s*\n/", trim($text));
    $out = '';

    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') { continue; }

        $lines = preg_split("/\n/", $block);
        $listLines = array_filter($lines, function ($line) {
            return strpos(trim($line), '- ') === 0;
        });

        if (count($listLines) === count($lines)) {
            $out .= '<ul>';
            foreach ($lines as $line) {
                $out .= '<li>' . substr(trim($line), 2) . '</li>';
            }
            $out .= '</ul>';
            continue;
        }

        if (preg_match('/^<h[123]>.*<\/h[123]>$/s', $block)) {
            $out .= $block;
        } else {
            $out .= '<p>' . str_replace("\n", '<br>', $block) . '</p>';
        }
    }

    return $out;
}
?>
<!doctype html>
<html lang="de">

<?php require_once __DIR__ . '/includes/head.php'; ?>

<body>

<div class="scanlines" aria-hidden="true"></div>

<header class="site-header">
    <a class="brand" href="#top" aria-label="Static Zone Home">
      <img src="<?= htmlspecialchars($settings['header_logo']) ?>" style="width:60px; height:60px" alt="">
      <span><?= htmlspecialchars($settings['header_name']) ?></span>
    </a>
<nav class="nav">
  <a href="./">Start</a>

  <?php
  $landingFile = __DIR__ . '/data/landing.json';
  $landingData = file_exists($landingFile)
      ? json_decode(file_get_contents($landingFile), true)
      : [];

  $sections = $landingData['sections'] ?? [];
  ?>

  <?php foreach ($sections as $section): ?>
    <?php if (empty($section['enabled'])) continue; ?>
    <?php $sid = $section['id'] ?? ''; ?>

    <?php if ($sid === 'gallery'): ?>
      <a href="./#screens">Screenshots</a>
    <?php endif; ?>

    <?php if ($sid === 'news'): ?>
      <a href="./#news">News</a>
    <?php endif; ?>

    <?php if ($sid === 'media'): ?>
      <a href="./#media">Media</a>
    <?php endif; ?>

    <?php if ($sid === 'about-project'): ?>
      <a href="./#about-project">About</a>
    <?php endif; ?>
  <?php endforeach; ?>

  <a href="devlog.php">DevLog</a>

  <?php foreach ($navPages as $navPage): ?>
    <a href="page.php?p=<?= urlencode($navPage['slug']) ?>">
      <?= e($navPage['nav_title'] ?: $navPage['title']) ?>
    </a>
  <?php endforeach; ?>
</nav>
</header>

<?php if (!$post): ?>
  <main class="not-found container">
    <a class="back" href="devlog.php">← Back to Devlog</a>
    <h1>Entry not found</h1>
    <p class="excerpt">This devlog entry does not exist or is not published.</p>
  </main>
<?php else: ?>
  <main class="post">
    <article class="container">
      <a class="back" href="devlog.php"><h2>← Back to Devlog</h2></a>

      <div class="meta">
        <time datetime="<?= e($post['date']) ?>"><?= e(date('d. F Y', strtotime($post['date'] ?? 'now'))) ?></time>
        <span><?= e($post['category'] ?? 'General') ?></span>
      </div>

      <h1><?= e($post['title']) ?></h1>

      <?php if (!empty($post['excerpt'])): ?>
        <p class="excerpt"><?= e($post['excerpt']) ?></p>
      <?php endif; ?>

      <?php if (!empty($post['image'])): ?>
        <img class="hero-img" src="<?= e($post['image']) ?>" alt="">
      <?php endif; ?>

      <div class="body">
        <?= simple_markdown($post['body'] ?? '') ?>
      </div>
    </article>
  </main>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; sz_render_footer(); ?>

</body>
</html>
