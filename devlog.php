<?php    /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later
	      * devlog.php
*/

$devlogFile = __DIR__ . '/data/devlog.json';

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

$items = file_exists($devlogFile)
    ? json_decode(file_get_contents($devlogFile), true)
    : [];

if (!is_array($items)) {
    $items = [];
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

$devlogSettingsFile = __DIR__ . '/data/devlog-settings.json';
$devlogSettings = file_exists($devlogSettingsFile)
    ? json_decode(file_get_contents($devlogSettingsFile), true)
    : [];

if (!is_array($devlogSettings)) {
    $devlogSettings = [];
}

$devlogHeroImage = !empty($devlogSettings['hero_image'])
    ? '/' . ltrim($devlogSettings['hero_image'], '/')
    : '/assets/img/heroimage.jpg';

$items = array_filter($items, function ($item) {
    return !empty($item['published']);
});

usort($items, function ($a, $b) {
    return strcmp($b['date'] ?? '', $a['date'] ?? '');
});

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function sz_lower($value) {
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

function sz_substr($value, $start, $length) {
    return function_exists('mb_substr') ? mb_substr($value, $start, $length) : substr($value, $start, $length);
}

function sz_strlen($value) {
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function excerpt($item) {
    if (!empty($item['excerpt'])) {
        return $item['excerpt'];
    }

    $text = strip_tags($item['body'] ?? '');
    return sz_substr($text, 0, 180) . (sz_strlen($text) > 180 ? '...' : '');
}

$categories = [];
foreach ($items as $item) {
    $cat = $item['category'] ?? 'General';
    $categories[$cat] = true;
}
$categories = array_keys($categories);
sort($categories);

$pagesFile = __DIR__ . '/data/pages.json';
$landingFile = __DIR__ . '/data/landing.json';

$navPages = file_exists($pagesFile)
    ? json_decode(file_get_contents($pagesFile), true)
    : [];

if (!is_array($navPages)) {
    $navPages = [];
}

$navPages = array_filter($navPages, function ($page) {
    return !empty($page['published']) &&
           !empty($page['slug']) &&
           !empty($page['title']);
});

$landingData = file_exists($landingFile)
    ? json_decode(file_get_contents($landingFile), true)
    : [];

$sections = $landingData['sections'] ?? [];
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

<main>
  <section class="hero hero--devlog" style="--hero:url('<?= e($devlogHeroImage) ?>')">
    <div class="hero-bg"></div>
    <div class="container hero-content">
      <p class="eyebrow">Development Archive</p>
      <h1>Devlog</h1>
      <p class="lead">Longer development notes, prototype progress, technical updates and behind-the-scenes material from Static Zone.</p>
      <br><br>
<center>
  <a href="#devlogList">
    <span style="
      display:inline-block;
      color:var(--accent);
      font-size:60pt;
      transform:rotate(-90deg);
    ">
      &laquo;
    </span>
  </a>
</center>
    </div>
  </section>

  <section class="container">
    <div class="tools">
      <input id="searchInput" type="search" placeholder="Search devlog...">
      <select id="categoryFilter">
        <option value="">All categories</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?= e($category) ?>"><?= e($category) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="devlog-list" id="devlogList">
      <?php foreach ($items as $item): ?>
        <article class="devlog-card"
          data-title="<?= e(sz_lower($item['title'] ?? '')) ?>"
          data-text="<?= e(sz_lower(($item['excerpt'] ?? '') . ' ' . ($item['body'] ?? ''))) ?>"
          data-category="<?= e($item['category'] ?? 'General') ?>">

          <a class="devlog-image" href="devlog-post.php?id=<?= urlencode($item['id']) ?>">
            <?php if (!empty($item['image'])): ?>
              <img src="<?= e($item['image']) ?>" alt="">
            <?php else: ?>
              <div class="devlog-placeholder">NO IMAGE</div>
            <?php endif; ?>
          </a>

          <div>
            <div class="meta">
              <time datetime="<?= e($item['date']) ?>"><?= e(date('d. F Y', strtotime($item['date'] ?? 'now'))) ?></time>
              <span><?= e($item['category'] ?? 'General') ?></span>
            </div>

            <h2>
              <a href="devlog-post.php?id=<?= urlencode($item['id']) ?>">
                <?= e($item['title']) ?>
              </a>
            </h2>

            <p><?= e(excerpt($item)) ?></p>

            <a class="read-more" href="devlog-post.php?id=<?= urlencode($item['id']) ?>">
              Read more →
            </a>
          </div>
        </article>
      <?php endforeach; ?>

      <?php if (count($items) === 0): ?>
        <div class="empty">No devlog entries published yet.</div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; sz_render_footer(); ?>

<script>
const searchInput = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
const cards = [...document.querySelectorAll('.devlog-card')];

function filterDevlog() {
  const search = searchInput.value.toLowerCase().trim();
  const category = categoryFilter.value;

  cards.forEach(card => {
    const matchesSearch =
      card.dataset.title.includes(search) ||
      card.dataset.text.includes(search);

    const matchesCategory =
      category === '' ||
      card.dataset.category === category;

    card.style.display =
      matchesSearch && matchesCategory ? 'grid' : 'none';
  });
}

searchInput.addEventListener('input', filterDevlog);
categoryFilter.addEventListener('change', filterDevlog);
</script>

</body>
</html>
