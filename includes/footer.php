<?php
$settingsFile = __DIR__ . '/../data/settings.json';
$settings = [];
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
}
$defaultSettings = [
    'show_credit_link' => false
];
$settings = array_merge($defaultSettings, $settings);

if (!function_exists('sz_footer_e')) {
    function sz_footer_e($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sz_footer_text')) {
    function sz_footer_text($text) {
        $text = htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
        $text = preg_replace('~&lt;br\s*/?&gt;~i', '<br>', $text);
        $blocks = preg_split("/\n\s*\n/", trim($text));
        $out = '';
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') { continue; }
            $out .= '<p>' . str_replace("\n", '<br>', $block) . '</p>';
        }
        return $out;
    }
}

if (!function_exists('sz_footer_default')) {
    function sz_footer_default() {
        return [
            'enabled' => true,
            'brand_title' => 'Game Development Showcase',
            'tagline' => 'games, showcare, development, cms',
            'description' => 'This is a system designed to make the development of a game available to the public.',
            'copyright_name' => 'Game Development Showcase',
            'author' => 'Michael Rhein',
            'contact_email' => 'michael@gamedevshow.de',
            'links_title' => 'Navigation',
            'show_navigation' => true,
            'legal_title' => 'Legal',
            'legal_links' => [],
            'social_title' => 'Follow',
            'socials' => [
                'youtube' => '', 'x' => '', 'instagram' => '', 'facebook' => '', 'tiktok' => '',
                'discord' => '', 'steam' => '', 'itch' => '', 'github' => '', 'twitch' => ''
            ]
        ];
    }
}

if (!function_exists('sz_footer_data')) {
    function sz_footer_data() {
        $file = __DIR__ . '/../data/footer.json';
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        if (!is_array($data)) { $data = []; }
        return array_replace_recursive(sz_footer_default(), $data);
    }
}

if (!function_exists('sz_footer_social_labels')) {
    function sz_footer_social_labels() {
        return [
            'youtube' => 'YouTube', 'x' => 'X / Twitter', 'instagram' => 'Instagram', 'facebook' => 'Facebook',
            'tiktok' => 'TikTok', 'discord' => 'Discord', 'steam' => 'Steam', 'itch' => 'itch.io',
            'github' => 'GitHub', 'twitch' => 'Twitch'
        ];
    }
}

if (!function_exists('sz_footer_is_safe_url')) {
    function sz_footer_is_safe_url($url) {
        $url = trim($url ?? '');
        return $url !== '' && (preg_match('~^https?://~i', $url) || preg_match('~^mailto:[^\s@]+@[^\s@]+\.[^\s@]+~i', $url));
    }
}

if (!function_exists('sz_render_footer')) {
    function sz_render_footer($navPages = [], $sections = []) {
        global $settings;
        
        $footer = sz_footer_data();
        if (empty($footer['enabled'])) { return; }
        
        $socialLabels = sz_footer_social_labels();
        $socials = is_array($footer['socials'] ?? null) ? $footer['socials'] : [];
        $activeSocials = array_filter($socials, function ($url) { return sz_footer_is_safe_url($url); });
        $legalLinks = is_array($footer['legal_links'] ?? null) ? $footer['legal_links'] : [];
        ?>
<footer class="footer site-footer">
  <div class="container footer-grid">
    <section class="footer-brand">
      <div class="footer-logo"><?= sz_footer_e($footer['brand_title']) ?></div>
      <?php if (!empty($footer['tagline'])): ?><p class="footer-tagline"><?= sz_footer_e($footer['tagline']) ?></p><?php endif; ?>
      <?php if (!empty($footer['description'])): ?><?= sz_footer_text($footer['description']) ?><?php endif; ?>
      <?php if (!empty($footer['contact_email'])): ?><a class="footer-contact" href="mailto:<?= sz_footer_e($footer['contact_email']) ?>"><?= sz_footer_e($footer['contact_email']) ?></a><?php endif; ?>
    </section>

    <?php if (!empty($footer['show_navigation'])): ?>
    <section class="footer-column">
      <h3><?= sz_footer_e($footer['links_title'] ?: 'Navigation') ?></h3>
      <nav class="footer-links" aria-label="Footer Navigation">
        <a href="./">Start</a>
        <a href="./#screens">Screenshots</a>
        <a href="./#news">News</a>
        <a href="devlog.php">DevLog</a>
        <?php foreach ($navPages as $navPage): if (empty($navPage['published']) || empty($navPage['slug']) || empty($navPage['title'])) continue; ?>
          <a href="page.php?p=<?= urlencode($navPage['slug']) ?>"><?= sz_footer_e($navPage['nav_title'] ?: $navPage['title']) ?></a>
        <?php endforeach; ?>
      </nav>
    </section>
    <?php endif; ?>

    <?php if (!empty($activeSocials)): ?>
    <section class="footer-column">
      <h3><?= sz_footer_e($footer['social_title'] ?: 'Follow') ?></h3>
      <div class="footer-socials">
        <?php foreach ($socialLabels as $key => $label): if (empty($activeSocials[$key])) continue; ?>
          <a href="<?= sz_footer_e($activeSocials[$key]) ?>" target="_blank" rel="noopener noreferrer"><?= sz_footer_e($label) ?></a>
        <?php endforeach; ?>
      </div>

    </section>
    <?php endif; ?>

    <?php if (!empty($legalLinks)): ?>
    <section class="footer-column">

	  <p style="height:20px;">
		  <h3><?= sz_footer_e($footer['legal_title'] ?: 'Legal') ?></h3>
		  <nav class="footer-links" aria-label="Legal Links">
			<?php foreach ($legalLinks as $link): if (empty($link['label']) || !sz_footer_is_safe_url($link['url'] ?? '')) continue; ?>
			  <a href="<?= sz_footer_e($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= sz_footer_e($link['label']) ?></a>
			<?php endforeach; ?>
		  </nav>
	  </p>
    <?php endif; ?>
  </div>

  <div class="container footer-bottom">
    <span>&copy; <?= date('Y') ?> <?= sz_footer_e($footer['copyright_name'] ?: 'Static Zone') ?><?php if (!empty($footer['author'])): ?> <small>(<?= sz_footer_e($footer['author']) ?>)</small><?php endif; ?></span>
    <span class="footer-made"><?php if (!empty($settings['show_credit_link'])): ?><a href="https://gds.staticzone.de/" target="_blank" rel="noopener">Game Development Showcase</a><?php endif; ?></span>
  </div>

</footer>
<?php
    }
}
?>