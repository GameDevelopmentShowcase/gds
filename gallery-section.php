<?php /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later */

$galleryFile = __DIR__ . '/data/gallery.json';
$galleryItems = file_exists($galleryFile)
    ? json_decode(file_get_contents($galleryFile), true)
    : [];

if (!is_array($galleryItems)) {
    $galleryItems = [];
}
?>

<section class="section gallery" id="screens">
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow"><?= landing_e($section['eyebrow'] ?? 'Visual Archive') ?></p>
      <h2><?= landing_e($section['heading'] ?? 'Screenshots') ?></h2>
    </div>

    <div class="gallery-grid">
      <?php foreach ($galleryItems as $image): ?>
        <?php if (empty($image['image'])) continue; ?>

        <a
          href="<?= htmlspecialchars($image['image'], ENT_QUOTES, 'UTF-8') ?>"
          target="_blank"
          class="<?= !empty($image['featured']) ? 'gallery-featured' : '' ?>"
        >
          <img
            src="<?= htmlspecialchars($image['image'], ENT_QUOTES, 'UTF-8') ?>"
            alt="<?= htmlspecialchars($image['alt'] ?? 'Static Zone screenshot', ENT_QUOTES, 'UTF-8') ?>"
          >
        </a>

      <?php endforeach; ?>
    </div>
  </div>
</section>
