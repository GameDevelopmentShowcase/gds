<?php    /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later
	      * page.php
*/

$pagesFile = __DIR__ . '/data/pages.json';
$pages = file_exists($pagesFile)
    ? json_decode(file_get_contents($pagesFile), true)
    : [];

	if (!is_array($pages)) {
		$pages = [];
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

$slug = $_GET['p'] ?? '';
$page = null;

	foreach ($pages as $item) {
		if (($item['slug'] ?? '') === $slug && !empty($item['published'])) {
			$page = $item;
			break;
		}
	}

$navPages = array_filter($pages, function ($item) {
    return !empty($item['published']) && (($item['show_in_nav'] ?? true) !== false) && !empty($item['slug']) && !empty($item['title']);
	});
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

<?php if (!$page): ?>
  <main class="not-found container">
<a class="back" href="./">← Back</a>
    <h1>Seite nicht gefunden</h1>
    <p class="excerpt">Diese Seite existiert nicht oder ist nicht veröffentlicht.</p>
  </main>
<?php else: ?>
<?php
$heroImage = !empty($page['image'])
    ? '/' . ltrim($page['image'], '/')
    : '/assets/img/default.jpg';
?>
<section class="hero hero--page"
    style="--hero:url('<?= e($heroImage) ?>')">

    <div class="hero-bg"></div>

    <div class="container hero-content">

        <!---    <a class="back" href="./">← Back</a>--->

        <p class="eyebrow"><p class="eyebrow"><?= e($page['eyebrow']) ?></p>

        <h1><?= e($page['title']) ?></h1>

        <?php if (!empty($page['excerpt'])): ?>
            <p class="excerpt"><?= e($page['excerpt']) ?></p>
        <?php endif; ?>
		<center><a href="#site"><span style="display:inline-block; color:var(--accent); font-size:60pt; transform:rotate(-90deg);">&laquo;</span></a></center>
    </div>
</section>

<main class="post" id="site">
    <article class="container">

      <div class="body">
        <?= simple_markdown($page['body'] ?? '') ?>
      </div>

    </article>
</main>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; sz_render_footer($navPages); ?>

</body>
</html>
