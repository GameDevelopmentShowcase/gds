<?php    /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later
	      * landing-edit.php
*/

require_once __DIR__ . '/common.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST erhalten. Size: ' . strlen(print_r($_POST, true)));
    error_log('FILES: ' . print_r($_FILES, true));
}
$landingFile = __DIR__ . '/../data/landing.json';
$uploadDir = __DIR__ . '/../assets/landing/';

function clean_landing_value($value) {
    return trim($value ?? '');
}

function make_landing_id($title = '') {
    $slug = strtolower(trim($title));
    $slug = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return ($slug !== '' ? $slug : 'section') . '-' . substr(str_replace('.', '', uniqid('', true)), -6);
}


function load_screenshot_options() {
    $galleryFile = __DIR__ . '/../data/gallery.json';
    $items = file_exists($galleryFile) ? json_decode(file_get_contents($galleryFile), true) : [];
    if (!is_array($items)) {
        $items = [];
    }

    $options = [];
    $seen = [];

    foreach ($items as $item) {
        $image = clean_landing_value($item['image'] ?? '');
        if ($image === '' || isset($seen[$image])) {
            continue;
        }
        $seen[$image] = true;
        $options[] = [
            'image' => $image,
            'label' => basename($image)
        ];
    }

    return $options;
}

function is_uploaded_asset($path, $folder) {
    $path = ltrim($path ?? '', '/');
    return strpos($path, 'assets/' . $folder . '/') === 0;
}

function default_landing_sections() {
    return [
        ['id' => 'hero', 'type' => 'fixed', 'title' => 'Hero', 'enabled' => true],
        ['id' => 'gallery', 'type' => 'fixed', 'title' => 'Screenshots / Gallery', 'enabled' => true],
        ['id' => 'news', 'type' => 'fixed', 'title' => 'News', 'enabled' => true],
        ['id' => 'media', 'type' => 'media', 'title' => 'Media', 'enabled' => true, 'eyebrow' => 'Video Material', 'heading' => 'Media', 'main_video' => 'https://www.youtube.com/watch?v=BgTXzBC9fas', 'videos' => []],
        ['id' => 'about-project', 'type' => 'fixed', 'title' => 'About Static Zone', 'enabled' => true],
        ['id' => 'system-requirements', 'type' => 'fixed', 'title' => 'System requirements', 'enabled' => true]
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sections = [];
    $usedImages = [];
    $count = count($_POST['id'] ?? []);

    for ($i = 0; $i < $count; $i++) {
        $type = clean_landing_value($_POST['type'][$i] ?? 'fixed');
        $title = clean_landing_value($_POST['title'][$i] ?? 'Section');
        $id = clean_landing_value($_POST['id'][$i] ?? '');
        if ($id === '') {
            $id = make_landing_id($title);
        }

        $section = [
            'id' => $id,
            'type' => in_array($type, ['fixed', 'media', 'custom'], true) ? $type : 'fixed',
            'title' => $title !== '' ? $title : 'Section',
            'enabled' => isset($_POST['enabled'][$i])
        ];

        if ($section['type'] === 'media') {
            $section['eyebrow'] = clean_landing_value($_POST['eyebrow'][$i] ?? 'Video Material');
            $section['heading'] = clean_landing_value($_POST['heading'][$i] ?? 'Media');
            $section['main_video'] = clean_landing_value($_POST['main_video'][$i] ?? '');
            $section['videos'] = [];

            $videoTitles = $_POST['video_title'][$i] ?? [];
            $videoUrls = $_POST['video_url'][$i] ?? [];
            $videoCount = max(count($videoTitles), count($videoUrls));
            for ($v = 0; $v < $videoCount; $v++) {
                $url = clean_landing_value($videoUrls[$v] ?? '');
                $videoTitle = clean_landing_value($videoTitles[$v] ?? '');
                if ($url === '') {
                    continue;
                }
                $section['videos'][] = ['title' => $videoTitle, 'url' => $url];
            }
        }

        if ($section['type'] === 'custom') {
            $section['eyebrow'] = clean_landing_value($_POST['eyebrow'][$i] ?? '');
            $section['heading'] = clean_landing_value($_POST['heading'][$i] ?? $section['title']);
            $section['body'] = clean_landing_value($_POST['body'][$i] ?? '');

            $oldImage = clean_landing_value($_POST['old_image'][$i] ?? '');
            $image = $oldImage;

            $screenshotImage = clean_landing_value($_POST['screenshot_image'][$i] ?? '');
            if ($screenshotImage !== '') {
                if (!empty($image) && is_uploaded_asset($image, 'landing')) {
                    $oldFile = __DIR__ . '/../' . ltrim($image, '/');
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
                $image = $screenshotImage;
            }

            if (!empty($_FILES['image']['name'][$i])) {
                $tmp = $_FILES['image']['tmp_name'][$i];
                $name = basename($_FILES['image']['name'][$i]);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                if (is_uploaded_file($tmp) && in_array($ext, $allowed, true)) {
                    $newName = 'landing_custom_' . time() . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($tmp, $uploadDir . $newName)) {
                        if (!empty($oldImage) && is_uploaded_asset($oldImage, 'landing')) {
                            $oldFile = __DIR__ . '/../' . ltrim($oldImage, '/');
                            if (file_exists($oldFile)) {
                                unlink($oldFile);
                            }
                        }
                        $image = 'assets/landing/' . $newName;
                    }
                }
            }

            if ($image && is_uploaded_asset($image, 'landing')) {
                $usedImages[] = $image;
            }

            $section['image'] = $image;
        }

        if ($section['type'] === 'fixed') {
            $section['eyebrow'] = clean_landing_value($_POST['eyebrow'][$i] ?? '');
            $section['heading'] = clean_landing_value($_POST['heading'][$i] ?? '');
            $section['body'] = clean_landing_value($_POST['body'][$i] ?? '');
            $section['hero_title_1'] = clean_landing_value($_POST['hero_title_1'][$i] ?? '');
            $section['hero_title_2'] = clean_landing_value($_POST['hero_title_2'][$i] ?? '');
            $section['lead'] = clean_landing_value($_POST['lead'][$i] ?? '');
            $section['button_text'] = clean_landing_value($_POST['button_text'][$i] ?? '');
            $section['button_link'] = clean_landing_value($_POST['button_link'][$i] ?? '#screens');
            $section['status_title'] = clean_landing_value($_POST['status_title'][$i] ?? '');
            $section['status_items'] = clean_landing_value($_POST['status_items'][$i] ?? '');

            if ($id === 'hero') {
                $oldImage = clean_landing_value($_POST['old_image'][$i] ?? '');
                $image = $oldImage;

                $screenshotImage = clean_landing_value($_POST['screenshot_image'][$i] ?? '');
                if ($screenshotImage !== '') {
                    if (!empty($image) && is_uploaded_asset($image, 'landing')) {
                        $oldFile = __DIR__ . '/../' . ltrim($image, '/');
                        if (file_exists($oldFile)) { 
                            unlink($oldFile); 
                        }
                    }
                    $image = $screenshotImage;
                }

                if (!empty($_FILES['image']['name'][$i])) {
                    $tmp = $_FILES['image']['tmp_name'][$i];
                    $name = basename($_FILES['image']['name'][$i]);
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                    if (is_uploaded_file($tmp) && in_array($ext, $allowed, true)) {
                        $newName = 'landing_hero_' . time() . '_' . $i . '.' . $ext;
                        if (move_uploaded_file($tmp, $uploadDir . $newName)) {
                            if (!empty($oldImage) && is_uploaded_asset($oldImage, 'landing')) {
                                $oldFile = __DIR__ . '/../' . ltrim($oldImage, '/');
                                if (file_exists($oldFile)) {
                                    unlink($oldFile);
                                }
                            }
                            $image = 'assets/landing/' . $newName;
                        }
                    }
                }

                if ($image && is_uploaded_asset($image, 'landing')) {
                    $usedImages[] = $image;
                }

                $section['image'] = $image;
            }
        }

        $sections[] = $section;
    }

    $existingFiles = glob($uploadDir . 'landing_*.*');
    foreach ($existingFiles as $file) {
        $relativePath = 'assets/landing/' . basename($file);
        if (!in_array($relativePath, $usedImages)) {
            unlink($file);
        }
    }

    file_put_contents($landingFile, json_encode(['sections' => $sections], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['saved' => true]);
        exit;
    }

    header('Location: landing-edit.php?saved=1');
    exit;
}

$data = file_exists($landingFile) ? json_decode(file_get_contents($landingFile), true) : [];
$sections = $data['sections'] ?? default_landing_sections();
if (!is_array($sections) || count($sections) === 0) {
    $sections = default_landing_sections();
}

$screenshotOptions = load_screenshot_options();
?>

<!doctype html>
<html lang="de">
<?php require_once '../includes/head.php'; ?>

<body>
<div class="scanlines"></div>
<div class="header"><div class="logo"><?= htmlspecialchars($settings['header_name']) ?></div><a href="edit.php">back to admin</a></div>
<div class="container">
  <div class="page-title">
    <div><h1>Landing Editor</h1></div>
    <div class="title-actions"><button type="button" class="tool-btn" onclick="collapseAllSections()">Collapse all</button><button type="button" class="tool-btn" onclick="expandAllSections()">Expand all</button><button type="button" class="tool-btn" onclick="addTemplateSection('feature')">+ Add Feature</button><button type="button" class="tool-btn" onclick="addTemplateSection('faq')">+ Add FAQ</button><button type="button" class="tool-btn" onclick="addTemplateSection('roadmap')">+ Add Roadmap</button><button type="button" class="add-btn" onclick="addCustomSection()">+ Add Section</button></div>
  </div>
  <div id="status" class="status"><?= isset($_GET['saved']) ? ' Landig Page Saved.' : 'Ready.' ?></div>
  <form id="landingForm" method="post" enctype="multipart/form-data">
  <div class="quicksavediv">
	  <div class="quicksavediv2"><p class="hint">You can sort sections using the arrow buttons or by drag and drop. For better clarity, large sections can be collapsed.</p></div>
	  <div class="quicksavediv3"><button class="save-btn" type="submit" height="20px">Save</button></div>
	  <div class="quicksavediv4"></div>
  </div>
    <div id="sectionList">
      <?php foreach ($sections as $i => $section): ?>
        <?php
          $type = $section['type'] ?? 'fixed';
          $videos = $section['videos'] ?? [];
          if (!is_array($videos)) { $videos = []; }
        ?>
        <div class="devlog-box <?= empty($section['enabled']) ? 'section-disabled' : '' ?>" draggable="true">
          <div class="devlog-top">
            <button type="button" class="collapse-toggle" onclick="toggleSection(this)" aria-label="Section ein- oder ausklappen" title="Collapse">▾</button>
            <div class="drag-handle" title="Drag to move">
				<div class="move-icon">
				  <span class="horizontal">↔</span>
				  <span class="vertical">↕</span>
				</div>
			</div>
            <div class="section-summary">
              <div class="summary-title"><?= htmlspecialchars($section['title'] ?? 'Section') ?></div>
              <div class="summary-meta"><?= htmlspecialchars(strtoupper($type)) ?> · <?= !empty($section['enabled']) ? 'Active' : 'Deactivated' ?></div>
            </div>
            <div class="move-buttons">
              <button type="button" class="tool-btn compact-btn" onclick="moveSection(this, -1)" title="Move Up">↑</button>
              <button type="button" class="tool-btn compact-btn" onclick="moveSection(this, 1)" title="Move Down">↓</button>
            </div>
            <?php if ($type === 'custom'): ?><button type="button" class="delete-btn" onclick="deleteSection(this)">Delete</button><?php endif; ?>
          </div>

          <input type="hidden" name="id[]" value="<?= htmlspecialchars($section['id'] ?? '') ?>">
          <input type="hidden" name="type[]" value="<?= htmlspecialchars($type) ?>">
          <input type="hidden" name="old_image[]" value="<?= htmlspecialchars($section['image'] ?? '') ?>">
          <input type="hidden" name="delete_image[]" value="0">

          <div class="section-content">
          <div class="editor-grid">
            <div>
              <div class="landing-type"><?= htmlspecialchars(strtoupper($type)) ?> SECTION</div>
              <label>Internal Name</label>
              <input type="text" name="title[]" value="<?= htmlspecialchars($section['title'] ?? '') ?>" oninput="updateAllPreviews(); autosaveLocal();" <?= $type === 'fixed' ? 'readonly' : '' ?>>

              <div class="check-row">
                <input type="checkbox" name="enabled[<?= $i ?>]" <?= !empty($section['enabled']) ? 'checked' : '' ?> onchange="updateBoxState(this); autosaveLocal();">
                <label style="margin:0;">Show Active</label>
              </div>

<?php if ($type === 'fixed'): ?>
    <div class="fixed-note">This fixed section can now be edited here. Empty fields automatically use the default text.</div>
    <?php if (($section['id'] ?? '') === 'hero'): ?>
        <label>Eyebrow</label>
        <input type="text" name="eyebrow[<?= $i ?>]" value="<?= htmlspecialchars(trim($section['eyebrow'] ?? '') ?: 'Small Text') ?>" oninput="updateAllPreviews(); autosaveLocal();">
        <label>Hero Titel Zeile 1</label>
        <input type="text" name="hero_title_1[<?= $i ?>]" value="<?= htmlspecialchars(trim($section['hero_title_1'] ?? '') ?: 'Hero Titel Zeile 1') ?>" oninput="updateAllPreviews(); autosaveLocal();">
        <label>Hero Titel Zeile 2</label>
        <input type="text" name="hero_title_2[<?= $i ?>]" value="<?= htmlspecialchars(trim($section['hero_title_2'] ?? '') ?: 'Hero Titel Zeile 2') ?>" oninput="updateAllPreviews(); autosaveLocal();">
        <label>Lead Text</label>
        <input type="text" name="lead[<?= $i ?>]" value="<?= htmlspecialchars(trim($section['lead'] ?? '') ?: 'Lead Text') ?>" oninput="updateAllPreviews(); autosaveLocal();">
        <label>Button Text</label>
        <input type="text" name="button_text[<?= $i ?>]" value="<?= htmlspecialchars(trim($section['button_text'] ?? '') ?: 'Button Text') ?>" oninput="updateAllPreviews(); autosaveLocal();">
        <label>Button Link (Section)</label>
        <select name="button_link[<?= $i ?>]" onchange="updateAllPreviews(); autosaveLocal();">
		<option value="#screens" <?= (($section['button_link'] ?? '#screens') == '#screens') ? 'selected' : '' ?>>Screenshots (Gallery)</option>
		<option value="#news" <?= (($section['button_link'] ?? '#screens') == '#news') ? 'selected' : '' ?>>News</option>
		<option value="#media" <?= (($section['button_link'] ?? '#screens') == '#media') ? 'selected' : '' ?>>Media</option>
		<option value="#about-project" <?= (($section['button_link'] ?? '#screens') == '#about-project') ? 'selected' : '' ?>>About</option>
		<option value="#system-requirements" <?= (($section['button_link'] ?? '#screens') == '#system-requirements') ? 'selected' : '' ?>>System Requirements</option>
			<?php

			global $sections;
			foreach ($sections as $sec):
				if (($sec['type'] ?? '') === 'custom' && !empty($sec['id'])):
					$customId = '#' . $sec['id'];
			?>
					<option value="<?= htmlspecialchars($customId) ?>" <?= (($section['button_link'] ?? '') == $customId) ? 'selected' : '' ?>><?= htmlspecialchars($sec['title'] ?? 'Custom Section') ?></option>
			<?php
				endif;
			endforeach;
			?>
		</select>
                  <label>Hero Image Upload</label>
                  <input type="file" name="image[<?= $i ?>]" accept="image/*" onchange="updateAllPreviews(); autosaveLocal();">
                  <label>Or choose a hero from screenshots</label>
                  <select name="screenshot_image[<?= $i ?>]" onchange="selectScreenshotImage(this); updateAllPreviews(); autosaveLocal();">
                    <option value="">— Do not change / No screenshot —</option>
                    <?php foreach ($screenshotOptions as $shot): ?>
                      <option value="<?= htmlspecialchars($shot['image']) ?>" <?= (($section['image'] ?? '') === ($shot['image'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($shot['label'] ?? basename($shot['image'] ?? '')) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php if (!empty($section['image'])): ?>
                    <div class="hero-image-box">
                      <img class="preview-img" src="../<?= htmlspecialchars($section['image']) ?>" alt="">
                      <button type="button" class="delete-btn" onclick="removeHeroImage(this)">Delete Hero Image</button>
                    </div><br><br>
                  <?php endif; ?>
                  <label>Status Titel</label>
                  <input type="text" name="status_title[<?= $i ?>]" value="<?= htmlspecialchars(trim($section['status_title'] ?? '') ?: 'Development Status') ?>" oninput="updateAllPreviews(); autosaveLocal();">
                  <label>Status Values <small>(One line per entry: Label: Value)</small></label>
                  <textarea name="status_items[<?= $i ?>]" oninput="updateAllPreviews(); autosaveLocal();"><?= htmlspecialchars(trim($section['status_items'] ?? '') ?:"
Genre: xxx
Engine: xxx
Project beginning: xx.xxx.xx
Version: xxx
				") ?></textarea>
                <?php elseif (($section['id'] ?? '') === 'news'): ?>
                  <label>Eyebrow</label>
                  <input type="text" name="eyebrow[<?= $i ?>]" value="<?= htmlspecialchars(!empty($section['eyebrow']) ? trim($section['eyebrow']) : 'Small Text') ?>" oninput="updateAllPreviews(); autosaveLocal();">
                  <label>Title</label>
                  <input type="text" name="heading[<?= $i ?>]" value="<?= htmlspecialchars(!empty($section['heading']) ? trim($section['heading']) : 'News') ?>" oninput="updateAllPreviews(); autosaveLocal();">
                <?php elseif (($section['id'] ?? '') === 'gallery'): ?>
                  <label>Eyebrow</label>
                  <input type="text" name="eyebrow[<?= $i ?>]" value="<?= htmlspecialchars(!empty(trim($section['eyebrow'] ?? '')) ? trim($section['eyebrow']) : 'Small Text') ?>" oninput="updateAllPreviews(); autosaveLocal();">
                  <label>Title</label>
                  <input type="text" name="heading[<?= $i ?>]" value="<?= htmlspecialchars(!empty($section['heading']) ? trim($section['heading']) : 'Gallery') ?>" oninput="updateAllPreviews(); autosaveLocal();">
                  <div class="fixed-note">Note: The gallery images themselves continue to be managed via the gallery editor.</div>
                <?php else: ?>
                  <label>Eyebrow</label>
                  <input type="text" name="eyebrow[<?= $i ?>]" value="<?= htmlspecialchars(!empty(trim($section['eyebrow'] ?? '')) ? trim($section['eyebrow']) : (($section['id'] ?? '') === 'about-project' ? 'Small Text' : '')) ?>" oninput="updateAllPreviews(); autosaveLocal();">
                  <label>Title</label>
                  <input type="text" name="heading[<?= $i ?>]" value="<?= htmlspecialchars(!empty(trim($section['heading'] ?? '')) ? trim($section['heading']) : (($section['id'] ?? '') === 'about-project' ? 'About Static Zone' : 'System requirements')) ?>" oninput="updateAllPreviews(); autosaveLocal();">
                  <label>Text / Markdown</label>
<textarea name="body[<?= $i ?>]" oninput="updateAllPreviews(); autosaveLocal();"><?= htmlspecialchars(
    !empty(trim($section['body'] ?? '')) 
        ? trim($section['body']) 
        : (($section['id'] ?? '') === 'about-project' 
            ? 'About Text' 
            : 'OS: xxx

Processor: xxx

Memory: xxx

Graphics: xxx

DirectX: xxx

Storage: xxx')
) ?></textarea>
                <?php endif; ?>
              <?php endif; ?>

              <?php if ($type === 'media'): ?>
                <label>Eyebrow</label>
                <input type="text" name="eyebrow[<?= $i ?>]" value="<?= htmlspecialchars(trim($section['eyebrow'] ?? '') ?: 'Video Material') ?>" oninput="updateAllPreviews(); autosaveLocal();">
                <label>Title</label>
                <input type="text" name="heading[<?= $i ?>]" value="<?= htmlspecialchars(trim($section['heading'] ?? '') ?: 'Media') ?>" oninput="updateAllPreviews(); autosaveLocal();">
                <label>Big Video / YouTube Link</label>
                <input type="text" name="main_video[<?= $i ?>]" value="<?= htmlspecialchars($section['main_video'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=..." oninput="updateAllPreviews(); autosaveLocal();">
                <label>Small videos below</label>
                <div class="video-list">
                  <?php foreach ($videos as $video): ?>
                    <div class="video-item">
                      <input type="text" data-video-title name="video_title[<?= $i ?>][]" value="<?= htmlspecialchars($video['title'] ?? '') ?>" placeholder="Videotitel" oninput="autosaveLocal();">
                      <input type="text" data-video-url name="video_url[<?= $i ?>][]" value="<?= htmlspecialchars($video['url'] ?? '') ?>" placeholder="YouTube Link" oninput="autosaveLocal();">
                      <button type="button" class="tool-btn" onclick="this.closest('.video-item').remove(); autosaveLocal();">Remove video</button>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="mini-actions"><button type="button" class="tool-btn" onclick="addVideo(this)">+ Add Video</button></div>
              <?php endif; ?>

              <?php if ($type === 'custom'): ?>
                <label>Eyebrow</label>
                <input type="text" name="eyebrow[<?= $i ?>]" value="<?= htmlspecialchars($section['eyebrow'] ?? '') ?>" oninput="updateAllPreviews(); autosaveLocal();">
                <label>Title</label>
                <input type="text" name="heading[<?= $i ?>]" value="<?= htmlspecialchars($section['heading'] ?? '') ?>" oninput="updateAllPreviews(); autosaveLocal();">
<label>Header Bild Upload</label>
<input type="file"
       name="image[<?= $i ?>]"
       accept="image/*"
       onchange="updateAllPreviews(); autosaveLocal();">

<label>Oder Screenshot auswählen</label>
	<select name="screenshot_image[<?= $i ?>]"
			onchange="selectScreenshotImage(this); updateAllPreviews(); autosaveLocal();">

		<option value="">— Kein Screenshot —</option>

		<?php foreach ($screenshotOptions as $shot): ?>
			<option value="<?= htmlspecialchars($shot['image']) ?>"
				<?= (($section['image'] ?? '') === ($shot['image'] ?? '')) ? 'selected' : '' ?>>
				<?= htmlspecialchars($shot['label'] ?? basename($shot['image'] ?? '')) ?>
			</option>
		<?php endforeach; ?>
	</select>

		<?php if (!empty($section['image'])): ?>
			<div class="hero-image-box">
				<img class="preview-img"
					 src="../<?= htmlspecialchars($section['image']) ?>"
					 alt=""><button type="button" class="delete-btn" onclick="removeHeroImage(this)">Delete Image</button></div>
		<?php endif; ?> 
				<div class="toolbar">
                  <button type="button" class="tool-btn" onclick="wrapText(this,'**','**')">Bold</button>
                  <button type="button" class="tool-btn" onclick="wrapText(this,'*','*')">Italic</button>
                  <button type="button" class="tool-btn" onclick="wrapText(this,'## ','')">H2</button>
				  <button type="button" class="tool-btn" onclick="wrapText(this,'### ','')">H3</button>
				  <button type="button" class="tool-btn" onclick="wrapText(this,'#### ','')">H4</button>
                  <button type="button" class="tool-btn" onclick="wrapText(this,'- ','')">List</button>
                  <button type="button" class="tool-btn" onclick="wrapText(this,'[Linktext](',')')">Link</button>
                </div>
                <label>Markdown Text</label>
                <textarea name="body[<?= $i ?>]" oninput="updateAllPreviews(); autosaveLocal();"><?= htmlspecialchars($section['body'] ?? '') ?></textarea>
              <?php endif; ?>
            </div>
            <div class="preview">
              <div class="preview-category"></div>
              <h2></h2>
              <div class="preview-excerpt"></div>
              <div class="preview-image-holder"></div>
              <div class="preview-body"></div>
            </div>
          </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="actions"><button class="save-btn" type="submit">Save Landing</button><button class="tool-btn" type="button" onclick="clearDraft()">Delete AutoSave Draft</button></div>
  </form>
</div>
<script>
const form = document.getElementById('landingForm');
const list = document.getElementById('sectionList');
const statusBox = document.getElementById('status');
const screenshotOptionsHtml = `<?php foreach ($screenshotOptions as $shot): ?><option value="<?= htmlspecialchars($shot['image'], ENT_QUOTES) ?>"><?= htmlspecialchars($shot['label'] ?? basename($shot['image'] ?? ''), ENT_QUOTES) ?></option><?php endforeach; ?>`;
let draggedItem = null;
let autoScrollFrame = null;

function getSectionTitle(box) {
  return box.querySelector('input[name="title[]"]')?.value || 'Section';
}

function updateSectionSummary(box) {
  const type = box.querySelector('input[name="type[]"]')?.value || 'section';
  const enabled = box.querySelector('input[type="checkbox"]')?.checked;
  const title = getSectionTitle(box);

  let displayTitle = title;
  if (type === 'fixed') {
    const headingInput = box.querySelector('[name^="heading["]');
    if (headingInput && headingInput.value.trim()) {
      displayTitle = headingInput.value;
    }
  }
  
  const titleEl = box.querySelector('.summary-title');
  const metaEl = box.querySelector('.summary-meta');
  if (titleEl) titleEl.textContent = displayTitle;
  if (metaEl) metaEl.textContent = type.toUpperCase() + ' · ' + (enabled ? 'aktiv' : 'Deactivated');
}

function toggleSection(button) {
  const box = button.closest('.devlog-box');
  box.classList.toggle('collapsed');
  button.textContent = box.classList.contains('collapsed') ? '▸' : '▾';
}

function collapseAllSections() {
  document.querySelectorAll('.devlog-box').forEach(box => {
    box.classList.add('collapsed');
    const button = box.querySelector('.collapse-toggle');
    if (button) button.textContent = '▸';
  });
}

function expandAllSections() {
  document.querySelectorAll('.devlog-box').forEach(box => {
    box.classList.remove('collapsed');
    const button = box.querySelector('.collapse-toggle');
    if (button) button.textContent = '▾';
  });
}

function moveSection(button, direction) {
  const box = button.closest('.devlog-box');
  const target = direction < 0 ? box.previousElementSibling : box.nextElementSibling;
  if (!target) return;
  if (direction < 0) target.before(box); else target.after(box);
  updateAllPreviews();
  autosaveLocal();
  box.scrollIntoView({block:'center', behavior:'smooth'});
}

function autoScrollWhileDragging(e) {
  const edge = 110;
  const speed = 18;
  if (e.clientY < edge) window.scrollBy(0, -speed);
  if (window.innerHeight - e.clientY < edge) window.scrollBy(0, speed);
}

function reindexNames() {
  document.querySelectorAll('.devlog-box').forEach((box, index) => {
    const enabled = box.querySelector('input[type="checkbox"]');
    if (enabled) enabled.name = 'enabled[' + index + ']';
    box.querySelectorAll('[data-dynamic-name]').forEach(field => {
      field.name = field.dataset.dynamicName.replace('__INDEX__', index);
    });
    const type = box.querySelector('input[name="type[]"]').value;
    ['eyebrow','heading','body','main_video','hero_title_1','hero_title_2','lead','button_text','status_title','status_items'].forEach(name => {
      const field = box.querySelector(`[name^="${name}["]`);
      if (field) field.name = name + '[' + index + ']';
    });
    box.querySelectorAll('.video-item').forEach(item => {
      const title = item.querySelector('input[data-video-title]');
      const url = item.querySelector('input[data-video-url]');
      if (title) title.name = 'video_title[' + index + '][]';
      if (url) url.name = 'video_url[' + index + '][]';
    });
  });
}

function updateBoxState(input) {
  input.closest('.devlog-box').classList.toggle('section-disabled', !input.checked);
  updateAllPreviews();
}

function updateAllPreviews() {
  reindexNames();
  document.querySelectorAll('.devlog-box').forEach(box => {
    updateSectionSummary(box);
    const type = box.querySelector('input[name="type[]"]').value;
    const title = box.querySelector('input[name="title[]"]').value;
    const enabled = box.querySelector('input[type="checkbox"]').checked;
    const eyebrow = box.querySelector('[name^="eyebrow["]')?.value || '';
    const heading = box.querySelector('[name^="heading["]')?.value || title;
    const body = box.querySelector('[name^="body["]')?.value || '';
    const oldImage = box.querySelector('input[name="old_image[]"]')?.value || '';
    const deleteImage = box.querySelector('input[name="delete_image[]"]')?.value === '1';
    const videoCount = box.querySelectorAll('.video-item input[data-video-url]').length;
    box.querySelector('.preview-category').textContent = (enabled ? 'Aktiv' : 'Deaktiviert') + ' — ' + type;
    box.querySelector('.preview h2').textContent = heading || title || 'Section';
    box.querySelector('.preview-excerpt').textContent = eyebrow;
    const imageHolder = box.querySelector('.preview-image-holder');
    if (imageHolder) {
      imageHolder.innerHTML = '';
      if (oldImage && !deleteImage) {
        const img = document.createElement('img');
        img.src = '../' + oldImage;
        img.className = 'preview-img';
        imageHolder.appendChild(img);
      }
    }
    if (type === 'custom') {
      box.querySelector('.preview-body').innerHTML = marked.parse(body || '');
    } else if (type === 'media') {
      box.querySelector('.preview-body').textContent = 'Large video plus' + videoCount + ' small video rows.';
    } else {
      const hero1 = box.querySelector('[name^="hero_title_1["]')?.value || '';
      const hero2 = box.querySelector('[name^="hero_title_2["]')?.value || '';
      const lead = box.querySelector('[name^="lead["]')?.value || '';
if (hero1) {
  box.querySelector('.preview h2').innerHTML = hero1 + '<br>' + hero2;
} else {
  box.querySelector('.preview h2').textContent = heading || title || 'Section';
}
      box.querySelector('.preview-body').innerHTML = marked.parse(body || lead || 'Fixed website section. Content is rendered in index.php.');
    }
  });
}

function addVideo(button) {
  const box = button.closest('.devlog-box');
  const holder = box.querySelector('.video-list');
  const item = document.createElement('div');
  item.className = 'video-item';
  item.innerHTML = `<input type="text" data-video-title placeholder="Videotitel" oninput="autosaveLocal();"><input type="text" data-video-url placeholder="YouTube Link" oninput="autosaveLocal();"><button type="button" class="tool-btn" onclick="this.closest('.video-item').remove(); updateAllPreviews(); autosaveLocal();">Video entfernen</button>`;
  holder.appendChild(item);
  updateAllPreviews();
  autosaveLocal();
}


function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
}

function addTemplateSection(template) {
  const presets = {
    feature: {
      title: 'Feature Section',
      eyebrow: 'Gameplay Feature',
      heading: 'Feature Highlight',
      body: `**Kurze Beschreibung des Features.**

- Punkt eins
- Punkt zwei
- Punkt drei`
    },
    faq: {
      title: 'FAQ Section',
      eyebrow: 'Questions',
      heading: 'FAQ',
      body: `## Frage 1
Antwort auf die erste Frage.

## Frage 2
Antwort auf die zweite Frage.`
    },
    roadmap: {
      title: 'Roadmap Section',
      eyebrow: 'Next Steps',
      heading: 'Roadmap',
      body: `- Neue Area
- Gegner-Verhalten erweitern
- Waffen-Feedback verbessern
- Sound und Atmosphäre polishen`
    }
  };
  const preset = presets[template] || presets.feature;
  addCustomSection(preset);
  const first = document.querySelector('.devlog-box');
  if (first) first.scrollIntoView({block:'center', behavior:'smooth'});
}

function addCustomSection(preset = null) {
  const id = 'section-' + Date.now();
  const div = document.createElement('div');
  div.className = 'devlog-box';
  div.draggable = true;
  div.innerHTML = `
    <div class="devlog-top"><button type="button" class="collapse-toggle" onclick="toggleSection(this)" aria-label="Section ein- oder ausklappen">▾</button><div class="drag-handle" title="Zum Verschieben ziehen">
					<div class="move-icon">
				  <span class="horizontal">↔</span>
				  <span class="vertical">↕</span>
				</div>
	</div><div class="section-summary"><div class="summary-title">${escapeHtml(preset?.title || 'New Section')}</div><div class="summary-meta">CUSTOM · aktiv</div></div><div class="move-buttons"><button type="button" class="tool-btn compact-btn" onclick="moveSection(this, -1)">↑</button><button type="button" class="tool-btn compact-btn" onclick="moveSection(this, 1)">↓</button></div><button type="button" class="delete-btn" onclick="deleteSection(this)">Delete</button></div>
<input type="hidden" name="id[]" value="${id}">
<input type="hidden" name="type[]" value="custom">

<input type="hidden" name="old_image[]" value="">
<input type="hidden" name="delete_image[]" value="0">
<div class="section-content">
  <div class="editor-grid">
    <div>
      <div class="landing-type">CUSTOM SECTION</div>
      <label>Interner Name</label><input type="text" name="title[]" value="${escapeHtml(preset?.title || 'New Section')}" oninput="updateAllPreviews(); autosaveLocal();">
      <div class="check-row"><input type="checkbox" checked onchange="updateBoxState(this); autosaveLocal();"><label style="margin:0;">Show Active</label></div>
      <label>Eyebrow</label><input type="text" name="eyebrow[0]" value="<?= htmlspecialchars(!empty($section['eyebrow']) ? trim($section['eyebrow']) : 'Small Text') ?>" oninput="updateAllPreviews(); autosaveLocal();" oninput="updateAllPreviews(); autosaveLocal();">
      <label>Tile</label><input type="text" name="heading[0]" value="${escapeHtml(preset?.heading || 'New Section')}" oninput="updateAllPreviews(); autosaveLocal();">
<label>Header Bild Upload</label>
<input type="file"
       name="image[0]"
       accept="image/*"
       onchange="updateAllPreviews(); autosaveLocal();">

<label>Oder Screenshot auswählen</label>
	<select name="screenshot_image[0]"
			onchange="selectScreenshotImage(this); updateAllPreviews(); autosaveLocal();">

		<option value="">— Kein Screenshot —</option>
		${screenshotOptionsHtml}
	</select>
	<div class="hero-image-box">
		<div class="hero-image-box" style="display:none;">
			<img class="preview-img" src="" alt=""><button type="button" class="delete-btn" onclick="removeHeroImage(this)">Delete Image</button>
		</div>
		  <div class="toolbar">
			<button type="button" class="tool-btn" onclick="wrapText(this,'**','**')">Bold</button>
			<button type="button" class="tool-btn" onclick="wrapText(this,'*','*')">Italic</button>
			<button type="button" class="tool-btn" onclick="wrapText(this,'## ','')">H2</button>
			<button type="button" class="tool-btn" onclick="wrapText(this,'### ','')">H3</button>
			<button type="button" class="tool-btn" onclick="wrapText(this,'#### ','')">H4</button>
			<button type="button" class="tool-btn" onclick="wrapText(this,'- ','')">List</button>
			<button type="button" class="tool-btn" onclick="wrapText(this,'[Linktext](',')')">Link</button>
		  </div>
      <label>Markdown Text</label>
	  <textarea name="body[0]" placeholder="your entry..." oninput="updateAllPreviews(); autosaveLocal();">${escapeHtml(preset?.body || '')}</textarea>
    </div>
		<div class="preview">
			<div class="preview-category"></div>
			<h2></h2>
			<div class="preview-excerpt"></div>
			<div class="preview-body"></div>
		</div>
		</div>
		</div>`;
  list.prepend(div);
  initDragAndDrop();
  updateAllPreviews();
  autosaveLocal();
}

function deleteSection(button) {
  if (!confirm('Diese Section wirklich löschen?')) return;
  button.closest('.devlog-box').remove();
  updateAllPreviews();
  autosaveLocal();
}


function selectScreenshotImage(select) {
  const box = select.closest('.devlog-box');
  const oldInput = box.querySelector('input[name="old_image[]"]');
  const deleteInput = box.querySelector('input[name="delete_image[]"]');
  const fileInput = box.querySelector('input[type="file"]');
  if (select.value) {
    oldInput.value = select.value;
    if (deleteInput) deleteInput.value = '0';
    if (fileInput) fileInput.value = '';
    const imageBox = box.querySelector('.hero-image-box');
    if (imageBox) imageBox.remove();
  }
}

function removeHeroImage(button) {
  if (!confirm('Hero Bild wirklich löschen?')) return;

  const box = button.closest('.devlog-box');

  const deleteInput = box.querySelector('input[name="delete_image[]"]');
  if (deleteInput) {
    deleteInput.value = '1';
  }

  const screenshotSelect = box.querySelector('select[name^="screenshot_image"]');
  if (screenshotSelect) {
    screenshotSelect.value = '';
  }

  const oldImage = box.querySelector('input[name="old_image[]"]');
  if (oldImage) {
    oldImage.value = '';
  }

  const imageBox = button.closest('.hero-image-box');
  if (imageBox) {
    imageBox.remove();
  }

  updateAllPreviews();
  autosaveLocal();
}

function wrapText(button, before, after) {
  const box = button.closest('.devlog-box');
  const textarea = box.querySelector('textarea');
  if (!textarea) return;
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const selected = textarea.value.substring(start, end);
  textarea.value = textarea.value.substring(0, start) + before + selected + after + textarea.value.substring(end);
  textarea.focus();
  updateAllPreviews();
  autosaveLocal();
}

function autosaveLocal() {
  statusBox.textContent = 'AutoSave lokal gespeichert.';
}
function clearDraft() { statusBox.textContent = 'AutoSave Draft gelöscht.'; }

function initDragAndDrop() {
  document.querySelectorAll('.devlog-box').forEach(box => {
    if (box.dataset.dragReady) return;
    box.dataset.dragReady = '1';

    const handle = box.querySelector('.drag-handle');
    box.draggable = false;
    if (handle) {
      handle.addEventListener('mousedown', () => { box.draggable = true; });
      handle.addEventListener('mouseup', () => { box.draggable = false; });
    }

    box.addEventListener('dragstart', e => {
      if (!e.target.classList.contains('devlog-box')) return;
      draggedItem = box;
      box.classList.add('is-dragging');
    });
    box.addEventListener('dragend', () => {
      draggedItem = null;
      box.classList.remove('is-dragging');
      box.draggable = false;
      updateAllPreviews();
      autosaveLocal();
    });
    box.addEventListener('dragover', e => {
      e.preventDefault();
      autoScrollWhileDragging(e);
      const current = e.currentTarget;
      if (current !== draggedItem && draggedItem) {
        const rect = current.getBoundingClientRect();
        const placeAfter = e.clientY > rect.top + rect.height / 2;
        if (placeAfter) current.after(draggedItem); else current.before(draggedItem);
      }
    });
  });
}

form.addEventListener('submit', async e => {
  e.preventDefault();
  const response = await fetch(window.location.href, {
    method:'POST',
    body:new FormData(form),
    headers:{'X-Requested-With':'XMLHttpRequest'}
  });
  
  if (response.ok) {
    statusBox.textContent = 'Gespeichert. Wird neu geladen...';

    setTimeout(() => {
      window.location.reload();
    }, 500);
    
  } else {
    statusBox.textContent = 'Fehler beim Speichern.';
    
    statusBox.scrollIntoView({
      behavior: 'smooth',
      block: 'center'
    });
  }
});

initDragAndDrop();
updateAllPreviews();
</script>
</body>
</html>
