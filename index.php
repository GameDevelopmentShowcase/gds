<?php    /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later
	      * index.php
*/

$pagesFile = __DIR__ . '/data/pages.json';
$landingFile = __DIR__ . '/data/landing.json';
$settingsFile = __DIR__ . '/data/settings.json';

$settings = [];
	if (file_exists($settingsFile)) {
		$settings = json_decode(file_get_contents($settingsFile), true);
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
    'header_name' => 'Game Development Showcase'
];
$settings = array_merge($defaultSettings, $settings);
$activeTheme = $settings['active_theme'] ?? 'blau';
$themeFile = 'assets/css/frontend-' . $activeTheme . '.css';
	if (!file_exists(__DIR__ . '/' . $themeFile)) {
		$themeFile = 'assets/css/frontend-blau.css';
	}
$navPages = file_exists($pagesFile) ? json_decode(file_get_contents($pagesFile), true) : [];
	if (!is_array($navPages)) {
		$navPages = [];
	}
$navPages = array_filter($navPages, function ($page) {
    return !empty($page['published']) && (($page['show_in_nav'] ?? true) !== false) && !empty($page['slug']) && !empty($page['title']);
	});

	function nav_e($value) {
		return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
	}
	function landing_e($value) {
		return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
	}
	function landing_markdown($text) {
		$text = htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
		$text = preg_replace('~&lt;br\s*/?&gt;~i', '<br>', $text);
		$text = preg_replace("~<br>\s*\n~i", '<br>', $text);
		$text = preg_replace('/^#### (.*)$/m', '<h4>$1</h4>', $text);
		$text = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $text);
		$text = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $text);
		$text = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $text);
		$text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
		$text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
		$text = preg_replace('/!\[(.*?)\]\((.*?)\)/', '<img src="$2" alt="$1">', $text);
		$text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $text);
		$text = preg_replace_callback('/^((?:[-*] .+\n?)+)/m', function($matches) {
			$items = preg_replace('/^[-*] (.+)$/m', '<li>$1</li>', $matches[1]);
			return '<ul>' . $items . '</ul>';
		}, $text);

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

	function youtube_embed_url($url) {
		$url = trim($url ?? '');
		if ($url === '') { return ''; }
		$id = '';
		if (preg_match('~youtu\.be/([A-Za-z0-9_-]{6,})~', $url, $m)) {
			$id = $m[1];
		} elseif (preg_match('~youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{6,})~', $url, $m)) {
			$id = $m[1];
		} elseif (preg_match('~[?&]v=([A-Za-z0-9_-]{6,})~', $url, $m)) {
			$id = $m[1];
		}
		if ($id === '') { return ''; }
		return 'https://www.youtube-nocookie.com/embed/' . rawurlencode($id);
	}

$defaultSections = [
    ['id' => 'hero', 'type' => 'fixed', 'title' => 'Hero', 'enabled' => true],
    ['id' => 'gallery', 'type' => 'fixed', 'title' => 'Screenshots / Gallery', 'enabled' => true],
    ['id' => 'news', 'type' => 'fixed', 'title' => 'News', 'enabled' => true],
    ['id' => 'media', 'type' => 'media', 'title' => 'Media', 'enabled' => true, 'eyebrow' => 'Video Material', 'heading' => 'Media', 'main_video' => 'https://www.youtube.com/watch?v=BgTXzBC9fas', 'videos' => []],
    ['id' => 'about-project', 'type' => 'fixed', 'title' => 'About Static Zone', 'enabled' => true],
    ['id' => 'system-requirements', 'type' => 'fixed', 'title' => 'System requirements', 'enabled' => true]
];

$landingData = file_exists($landingFile) ? json_decode(file_get_contents($landingFile), true) : [];
$sections = $landingData['sections'] ?? $defaultSections;
	if (!is_array($sections) || count($sections) === 0) { $sections = $defaultSections; }

	function render_landing_section($section) {
		$id = $section['id'] ?? '';
		$type = $section['type'] ?? 'fixed';

	if ($type === 'custom') {

		$sectionId = preg_replace('/[^a-z0-9\-_]/i', '-', $id ?: ('section-' . uniqid()));
		$image = trim($section['image'] ?? '');

		$style = '';

		if (!empty($image)) {
			$style = "background-image:url('" . landing_e($image) . "');";
		}
?>
    
    <section class="hero section custom-section" id="<?= landing_e($sectionId) ?>" style="<?= $style ?>">
      <div class="container split">
        <div class="section-title reveal">
          <?php if (!empty($section['eyebrow'])): ?>
            <p class="eyebrow">
              <?= landing_e($section['eyebrow']) ?>
            </p>
          <?php endif; ?>
          <h2>
            <?= landing_e($section['heading'] ?? $section['title'] ?? 'Section') ?>
          </h2>
        </div>
        <div class="body-copy reveal delay-1">
          <?= landing_markdown($section['body'] ?? '') ?>
        </div>
      </div>
    </section>

    <?php
    return;
}

    if ($type === 'media' || $id === 'media') {
        $mainEmbed = youtube_embed_url($section['main_video'] ?? '');
        ?>
        <section class="section media-section" id="media">
          <div class="container">
            <div class="section-head reveal">
              <p class="eyebrow"><?= landing_e($section['eyebrow'] ?? 'Video Material') ?></p>
              <h2><?= landing_e($section['heading'] ?? 'Media') ?></h2>
            </div>

            <?php if ($mainEmbed): ?>
              <div class="video-wrapper reveal delay-1">
                <iframe src="<?= landing_e($mainEmbed) ?>" title="Static Zone Video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="origin" allowfullscreen></iframe>
              </div>
            <?php endif; ?>

            <?php $videos = array_filter($section['videos'] ?? [], function ($video) { return !empty($video['url']); }); ?>
            <?php if (!empty($videos)): ?>
              <div class="small-video-grid reveal delay-1">
                <?php foreach ($videos as $video): $embed = youtube_embed_url($video['url'] ?? ''); if (!$embed) continue; ?>
                  <article class="small-video-card">
                    <div class="small-video-frame">
                      <iframe src="<?= landing_e($embed) ?>" title="<?= landing_e($video['title'] ?? 'Static Zone Video') ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="origin" allowfullscreen></iframe>
                    </div>
                    <?php if (!empty($video['title'])): ?><h3><?= landing_e($video['title']) ?></h3><?php endif; ?>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </section>
        <?php
        return;
    }

    switch ($id) {
        case 'hero': ?>
          <?php $heroImage = !empty($section['image']) ? '/' . ltrim($section['image'], '/') : '/assets/img/heroimage.jpg'; ?>
          <section class="hero hero--landing" style="--hero:url('<?= landing_e($heroImage) ?>')">
            <div class="hero-bg"></div>
            <div class="container hero-grid">
              <div class="hero-copy reveal">
                <p class="eyebrow"><?= landing_e($section['eyebrow'] ?? 'Sector 01 // Contact Lost') ?></p>
                <h1><?= landing_e($section['hero_title_1'] ?? 'Prototype') ?></h1><h2><?= landing_e($section['hero_title_2'] ?? 'Showcase') ?></h2>
                <p class="lead"><?= landing_e($section['lead'] ?? 'A modern take on the classic old-school shooter') ?></p>
                <div class="hero-actions"><a class="btn primary" href="<?= landing_e($section['button_link'] ?? '#screens') ?>"><?= landing_e($section['button_text'] ?? 'View screenshots') ?></a></div>	
              </div>
              <aside class="status-card reveal delay-1">
                <div class="status-title"><span class="status-dot"></span><h2><?= landing_e($section['status_title'] ?? 'Development Status') ?></h2></div>
                <dl>
                  <?php
                    $statusItems = trim($section['status_items'] ?? "Genre: Horror / Sci-Fi FPS\nEngine: Unity (6.2)\nProject beginning: Nov. 2024\nVersion: Prototype (v 0.0.3)");
                    foreach (preg_split('/\r?\n/', $statusItems) as $statusLine):
                      if (strpos($statusLine, ':') === false) { continue; }
                      [$statusLabel, $statusValue] = array_map('trim', explode(':', $statusLine, 2));
                  ?>
                    <div><dt><?= landing_e($statusLabel) ?></dt><dd><?= landing_e($statusValue) ?></dd></div>
                  <?php endforeach; ?>
                </dl>
              </aside>
            </div>
          </section>
        <?php break;
        case 'gallery':
            include __DIR__ . '/gallery-section.php';
            break;
        case 'news': ?>
          <section class="section news-list-section" id="news">
            <div class="container">
              <div class="section-head reveal"><p class="eyebrow"><?= landing_e($section['eyebrow'] ?? 'Transmission Log') ?></p><h2><?= landing_e($section['heading'] ?? 'News') ?></h2></div>
              <div class="news-list">
                <?php
                $newsFile = __DIR__ . '/data/news.json';
                $newsItems = file_exists($newsFile) ? json_decode(file_get_contents($newsFile), true) : [];
                if (!is_array($newsItems)) { $newsItems = []; }
                usort($newsItems, function($a, $b) { return strtotime($b['date'] ?? '') - strtotime($a['date'] ?? ''); });
                foreach ($newsItems as $news):
                  $date = htmlspecialchars($news['date'] ?? '');
                  $title = htmlspecialchars($news['title'] ?? '');
                  $body = $news['body'] ?? '';
                  $preview = strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $body));
                  $preview = function_exists('mb_strlen') ? (mb_strlen($preview) > 190 ? mb_substr($preview, 0, 190) . '...' : $preview) : (strlen($preview) > 190 ? substr($preview, 0, 190) . '...' : $preview);
                ?>
                  <article class="news-entry reveal">
                    <time datetime="<?= $date ?>"><?= $date ? date('d.m.Y', strtotime($date)) : '' ?></time>
                    <div class="news-entry-body">
                      <h3><?= $title ?></h3>
                      <p class="news-preview"><?= htmlspecialchars($preview) ?><button class="news-toggle" type="button">klick for read more</button></p>
                      <div class="news-full">
<div class="news-cardfront">
    <?php if (!empty($news['image'])): ?>
        <img src="../<?= htmlspecialchars($news['image']) ?>" alt="">
    <?php endif; ?>
</div>
						<br><?= landing_markdown($body) ?><p><button class="news-toggle news-close" type="button">close</button></p></div>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            </div>
          </section>
        <?php break;
        case 'about-project': ?>
          <section class="section about-project" id="about-project">
            <div class="container split">
              <div class="section-title reveal"><p class="eyebrow"><?= landing_e($section['eyebrow'] ?? 'Project Briefing') ?></p><h2><?= nl2br(landing_e($section['heading'] ?? 'About Static Zone')) ?></h2></div>
              <div class="body-copy reveal delay-1"><?= landing_markdown($section['body'] ?? "**Static Zone** is a horror sci-fi first-person shooter prototype developed in Unity.\n\nDevelopment began in November 2024. The game is currently still in an experimental prototype phase and is continuously being expanded with new areas, gameplay features, enemies, weapons, and story elements.") ?></div>
            </div>
          </section>
        <?php break;
        case 'system-requirements': ?>
          <section class="section" id="system-requirements">
            <div class="container split">
              <div class="section-title reveal"><p class="eyebrow"><?= landing_e($section['eyebrow'] ?? 'No signal. No rescue.') ?></p><h2><?= nl2br(landing_e($section['heading'] ?? 'System requirements')) ?></h2></div>
              <div class="body-copy reveal delay-1"><?= landing_markdown($section['body'] ?? "OS: Windows 10/11\nProcessor: Intel Core i7-4790K / AMD Ryzen 5 3600\nMemory: 16 GB RAM\nGraphics: NVIDIA GeForce GTX 1080 / AMD Radeon RX 6600\nDirectX: Version 11\nStorage: 15 GB available space") ?></div>
            </div>
          </section>
        <?php break;
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title><?= htmlspecialchars($settings['site_title']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($settings['site_description']) ?>" />

    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?= htmlspecialchars($settings['site_title']) ?>" />
    <meta property="og:description" content="<?= htmlspecialchars($settings['site_description']) ?>" />
    <meta property="og:image" content="<?= htmlspecialchars($settings['favicon_standard']) ?>" />
    <meta property="og:url" content="<?= 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?= htmlspecialchars($settings['site_title']) ?>" />
    <meta name="twitter:description" content="<?= htmlspecialchars($settings['site_description']) ?>" />
    <meta name="twitter:image" content="<?= htmlspecialchars($settings['favicon_standard']) ?>" />
    
    <link rel="canonical" href="<?= 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>" />
    
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($settings['favicon_standard']) ?>" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($settings['favicon_svg']) ?>" />
    <link rel="shortcut icon" href="<?= htmlspecialchars($settings['favicon_ico']) ?>" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars($settings['favicon_apple']) ?>" />
    <meta name="apple-mobile-web-app-title" content="gds" />
    <link rel="manifest" href="<?= htmlspecialchars($settings['favicon_manifest']) ?>" />
    
    <meta name="referrer" content="strict-origin-when-cross-origin"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <link rel="stylesheet" href="<?= $themeFile ?>">

</head>
<body>
  <div class="scanlines" aria-hidden="true"></div>
  <header class="site-header">
    <a class="brand" href="#top" aria-label="Static Zone Home">
      <img src="<?= htmlspecialchars($settings['header_logo']) ?>" style="width:60px; height:60px" alt="">
      <span><?= htmlspecialchars($settings['header_name']) ?></span>
    </a>
    <nav class="nav">
      <a href="./">Start</a>
      <?php foreach ($sections as $section): if (empty($section['enabled'])) continue; $sid = $section['id'] ?? ''; ?>
        <?php if ($sid === 'gallery'): ?><a href="#screens">Screenshots</a><?php endif; ?>
        <?php if ($sid === 'news'): ?><a href="#news">News</a><?php endif; ?>
        <?php if ($sid === 'media'): ?><a href="#media">Media</a><?php endif; ?>
        <?php if ($sid === 'about-project'): ?><a href="#about-project">About</a><?php endif; ?>
      <?php endforeach; ?>
      <a href="devlog.php">DevLog</a>
      <?php foreach ($navPages as $navPage): ?>
        <a href="page.php?p=<?= urlencode($navPage['slug']) ?>"><?= nav_e($navPage['nav_title'] ?: $navPage['title']) ?></a>
      <?php endforeach; ?>
    </nav>
  </header>

  <main id="top">
    <?php foreach ($sections as $section): ?>
      <?php if (!empty($section['enabled'])) { render_landing_section($section); } ?>
    <?php endforeach; ?>
  </main>

    <?php require_once __DIR__ . '/includes/footer.php'; sz_render_footer($navPages, $sections); ?>
  <script src="assets/js/main.js"></script>
</body>
</html>