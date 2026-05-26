<?php    /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later
	      * devlog-edit.php
*/

require_once __DIR__ . '/common.php';

$devlogFile = __DIR__ . '/../data/devlog.json';
$devlogSettingsFile = __DIR__ . '/../data/devlog-settings.json';
$uploadDir = __DIR__ . '/../assets/devlog/';

function clean_value($value) {
    return trim($value ?? '');
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
        $image = clean_value($item['image'] ?? '');
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

function is_devlog_upload($path) {
    $path = ltrim($path ?? '', '/');
    return strpos($path, 'assets/devlog/') === 0;
}

function make_id() {
    return 'devlog_' . str_replace('.', '', uniqid('', true));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $devlogSettings = file_exists($devlogSettingsFile)
        ? json_decode(file_get_contents($devlogSettingsFile), true)
        : [];

    if (!is_array($devlogSettings)) {
        $devlogSettings = [];
    }

    $usedImages = [];

    $devlogHeroImage = clean_value($_POST['old_devlog_hero_image'] ?? ($devlogSettings['hero_image'] ?? ''));
    $deleteDevlogHeroImage = ($_POST['delete_devlog_hero_image'] ?? '0') === '1';

    if ($deleteDevlogHeroImage && $devlogHeroImage !== '') {
        if (is_devlog_upload($devlogHeroImage)) {
            $oldFile = __DIR__ . '/../' . ltrim($devlogHeroImage, '/');
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
        $devlogHeroImage = '';
    }

    $screenshotHero = clean_value($_POST['devlog_hero_screenshot'] ?? '');
    if ($screenshotHero !== '') {
        if ($devlogHeroImage !== '' && is_devlog_upload($devlogHeroImage)) {
            $oldFile = __DIR__ . '/../' . ltrim($devlogHeroImage, '/');
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
        $devlogHeroImage = $screenshotHero;
    }

    if (!empty($_FILES['devlog_hero_image']['name'])) {
        $tmp = $_FILES['devlog_hero_image']['tmp_name'];
        $name = basename($_FILES['devlog_hero_image']['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (is_uploaded_file($tmp) && in_array($ext, $allowed, true)) {
            $newName = 'devlog_hero_' . time() . '.' . $ext;
            if (move_uploaded_file($tmp, $uploadDir . $newName)) {
                if ($devlogHeroImage !== '' && is_devlog_upload($devlogHeroImage)) {
                    $oldFile = __DIR__ . '/../' . ltrim($devlogHeroImage, '/');
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
                $devlogHeroImage = 'assets/devlog/' . $newName;
            }
        }
    }

    if ($devlogHeroImage && is_devlog_upload($devlogHeroImage)) {
        $usedImages[] = $devlogHeroImage;
    }

    $devlogSettings['hero_image'] = $devlogHeroImage;
    file_put_contents(
        $devlogSettingsFile,
        json_encode($devlogSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    $items = [];
    $count = count($_POST['title'] ?? []);

    for ($i = 0; $i < $count; $i++) {
        $title = clean_value($_POST['title'][$i] ?? '');

        if ($title === '') {
            continue;
        }

        $oldImage = clean_value($_POST['old_image'][$i] ?? '');
        $image = $oldImage;

        if (!empty($_FILES['image']['name'][$i])) {
            $tmp = $_FILES['image']['tmp_name'][$i];
            $name = basename($_FILES['image']['name'][$i]);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (is_uploaded_file($tmp) && in_array($ext, $allowed, true)) {
                $newName = 'devlog_' . time() . '_' . $i . '.' . $ext;
                if (move_uploaded_file($tmp, $uploadDir . $newName)) {
                    if ($oldImage && file_exists(__DIR__ . '/../' . ltrim($oldImage, '/'))) {
                        unlink(__DIR__ . '/../' . ltrim($oldImage, '/'));
                    }
                    $image = 'assets/devlog/' . $newName;
                }
            }
        }

        if ($image) {
            $usedImages[] = $image;
        }

        $items[] = [
            'id' => clean_value($_POST['id'][$i] ?? make_id()),
            'date' => clean_value($_POST['date'][$i] ?? date('Y-m-d')),
            'title' => $title,
            'category' => clean_value($_POST['category'][$i] ?? 'General'),
            'excerpt' => clean_value($_POST['excerpt'][$i] ?? ''),
            'body' => clean_value($_POST['body'][$i] ?? ''),
            'image' => $image,
            'published' => isset($_POST['published'][$i])
        ];
    }

    $existingFiles = glob($uploadDir . 'devlog_*.*');
    foreach ($existingFiles as $file) {
        $relativePath = 'assets/devlog/' . basename($file);
        if (!in_array($relativePath, $usedImages)) {
            unlink($file);
        }
    }

    file_put_contents(
        $devlogFile,
        json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    echo json_encode(['saved' => true]);
    exit;
}

$devlogItems = file_exists($devlogFile)
    ? json_decode(file_get_contents($devlogFile), true)
    : [];

if (!is_array($devlogItems)) {
    $devlogItems = [];
}

$devlogSettings = file_exists($devlogSettingsFile)
    ? json_decode(file_get_contents($devlogSettingsFile), true)
    : [];

if (!is_array($devlogSettings)) {
    $devlogSettings = [];
}

$screenshotOptions = load_screenshot_options();

if (count($devlogItems) === 0) {
    $devlogItems[] = [
        'id' => make_id(),
        'date' => date('Y-m-d'),
        'title' => '',
        'category' => 'General',
        'excerpt' => '',
        'body' => '',
        'image' => '',
        'published' => true
    ];
}

$categories = ['Prototype', 'Gameplay', 'Level Design', 'Graphics', 'Audio', 'Technical', 'General'];
?>

<!doctype html>
<html lang="de">
<?php require_once '../includes/head.php'; ?>
<body>
<div class="scanlines"></div>

<div class="header">
    <div class="logo"><?= htmlspecialchars($settings['header_name']) ?></div>
    <a href="edit.php">back to admin</a>
</div>
<div class="container">

    <div class="page-title">
        <div>
            <h1>Devlog Editor</h1>
        </div>

        <button type="button" class="add-btn" onclick="addDevlog()">+ New Entry</button>
    </div>

    <div id="status" class="status">Ready.</div>

    <form id="devlogForm" method="post" enctype="multipart/form-data">
  
        <input type="hidden" name="old_devlog_hero_image" value="<?= htmlspecialchars($devlogSettings['hero_image'] ?? '') ?>">
        <input type="hidden" name="delete_devlog_hero_image" value="0">

        <label>Hero Bild Upload</label>
        <input type="file" name="devlog_hero_image" accept="image/*" onchange="autosaveLocal();">

        <label>Or choose a hero from screenshots</label>
        <select name="devlog_hero_screenshot" onchange="selectDevlogHeroScreenshot(this); autosaveLocal();">
            <option value="">— Do not change / No screenshot —</option>
            <?php foreach ($screenshotOptions as $shot): ?>
                <option value="<?= htmlspecialchars($shot['image']) ?>" <?= (($devlogSettings['hero_image'] ?? '') === ($shot['image'] ?? '')) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($shot['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if (!empty($devlogSettings['hero_image'])): ?>
            <div class="hero-image-box">
                <img class="preview-img" src="../<?= htmlspecialchars($devlogSettings['hero_image']) ?>" alt="">
                <button type="button" class="delete-btn" onclick="removeDevlogHeroImage(this)">Delete Hero Image</button>
            </div><br><br>
        <?php endif; ?>

        <div id="devlogList">
  
  <div class="quicksavediv">
	  <div class="quicksavediv2"><p class="hint"></p></div>
	  <div class="quicksavediv3"><button class="save-btn" type="submit" height="20px">Save</button></div>
	  <div class="quicksavediv4"></div>
  </div><br>

        <?php foreach ($devlogItems as $i => $item): ?>
            <div class="devlog-box" draggable="true">

                <div class="devlog-top">
                    <div class="drag-handle">

				<div class="move-icon">
				  <span class="horizontal">↔</span>
				  <span class="vertical">↕</span>
				</div>
					
					</div>
                    <button type="button" class="delete-btn" onclick="deleteDevlog(this)">Delete</button>
                </div>

                <input type="hidden" name="id[]" value="<?= htmlspecialchars($item['id'] ?? make_id()) ?>">
                <input type="hidden" name="old_image[]" value="<?= htmlspecialchars($item['image'] ?? '') ?>">
                <input type="hidden" name="delete_image[]" value="0">

                <div class="editor-grid">

                    <div>
                        <div class="small-grid">
                            <div>
                                <label>Date</label>
                                <input type="date" name="date[]" value="<?= htmlspecialchars($item['date'] ?? date('Y-m-d')) ?>" oninput="updateAllPreviews(); autosaveLocal();">
                            </div>

                            <div>
                                <label>Category</label>
                                <select name="category[]" oninput="updateAllPreviews(); autosaveLocal();">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category) ?>" <?= (($item['category'] ?? 'General') === $category) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <label>Title</label>
                        <input type="text" name="title[]" value="<?= htmlspecialchars(trim($item['title'] ?? '') ?: 'Title') ?>" oninput="updateAllPreviews(); autosaveLocal();">

                        <label>Brief Excerpt</label>
                        <input type="text" name="excerpt[]" value="<?= htmlspecialchars(trim($item['excerpt'] ?? '') ?: 'No excerpt provided') ?>" oninput="updateAllPreviews(); autosaveLocal();">

                        <label>Image Upload</label>
                        <input type="file" name="image[]" accept="image/*" onchange="updateAllPreviews(); autosaveLocal();">

                        <?php if (!empty($item['image'])): ?>
                            <div class="hero-image-box">
                                <img class="preview-img" src="../<?= htmlspecialchars($item['image']) ?>" alt="">
                                <button type="button" class="delete-btn" onclick="removeHeroImage(this)">Delete image</button>
                            </div><br><br>
                        <?php endif; ?>

                        <div class="check-row">
                            <input type="checkbox" name="published[<?= $i ?>]" <?= !empty($item['published']) ? 'checked' : '' ?> onchange="autosaveLocal();">
                            <label style="margin:0;">Published</label>
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
                        <textarea name="body[]" placeholder="Write your devlog entry here..." oninput="updateAllPreviews(); autosaveLocal();"><?= htmlspecialchars(!empty(trim($item['body'] ?? '')) ? trim($item['body']) : '') ?></textarea>
                    </div>

                    <div class="preview">
                        <time></time>
                        <div class="preview-category"></div>
                        <h2></h2>
                        <div class="preview-image-holder"></div>
                        <div class="preview-excerpt"></div>
                        <div class="preview-body"></div>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>

        </div>

        <div class="actions">
            <button class="save-btn" type="submit">Save Devlog</button>
            <button class="tool-btn" type="button" onclick="clearDraft()">Delete AutoSave Draft</button>
        </div>

    </form>

    <div class="footer-space"></div>
</div>

<script>
const form = document.getElementById('devlogForm');
const list = document.getElementById('devlogList');
const statusBox = document.getElementById('status');

let draggedItem = null;

function formatDate(value) {
    if (!value) return '';
    const d = new Date(value + 'T00:00:00');
    return d.toLocaleDateString('de-DE', {
        day:'2-digit',
        month:'long',
        year:'numeric'
    });
}

function updateCheckboxNames() {
    document.querySelectorAll('.devlog-box').forEach((box, index) => {
        const checkbox = box.querySelector('input[type="checkbox"]');
        checkbox.name = 'published[' + index + ']';
    });
}

function updateAllPreviews() {
    document.querySelectorAll('.devlog-box').forEach(box => {
        const date = box.querySelector('input[name="date[]"]').value;
        const title = box.querySelector('input[name="title[]"]').value;
        const category = box.querySelector('select[name="category[]"]').value;
        const excerpt = box.querySelector('input[name="excerpt[]"]').value;
        const body = box.querySelector('textarea[name="body[]"]').value;
        const oldImage = box.querySelector('input[name="old_image[]"]').value;
        const deleteImage = box.querySelector('input[name="delete_image[]"]')?.value === '1';

        box.querySelector('.preview time').textContent = formatDate(date);
        box.querySelector('.preview-category').textContent = category;
        box.querySelector('.preview h2').textContent = title || 'Untitled Devlog';
        box.querySelector('.preview-excerpt').textContent = excerpt || '';
        box.querySelector('.preview-body').innerHTML = marked.parse(body || '');

        const imageHolder = box.querySelector('.preview-image-holder');
        imageHolder.innerHTML = '';

        if (oldImage && !deleteImage) {
            const img = document.createElement('img');
            img.src = '../' + oldImage;
            img.className = 'preview-img';
            imageHolder.appendChild(img);
        }
    });

    updateCheckboxNames();
}

function wrapText(button, before, after) {
    const box = button.closest('.devlog-box');
    const textarea = box.querySelector('textarea[name="body[]"]');

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.substring(start, end);

    textarea.value =
        textarea.value.substring(0, start) +
        before + selected + after +
        textarea.value.substring(end);

    textarea.focus();
    updateAllPreviews();
    autosaveLocal();
}

function addDevlog() {
    const id = 'devlog_' + Date.now();

    const div = document.createElement('div');
    div.className = 'devlog-box';
    div.draggable = true;

    div.innerHTML = `
        <div class="devlog-top">
            <div class="drag-handle">

				<div class="move-icon">
				  <span class="horizontal">↔</span>
				  <span class="vertical">↕</span>
				</div>
			
			</div>
            <button type="button" class="delete-btn" onclick="deleteDevlog(this)">Delete</button>
        </div>

        <input type="hidden" name="id[]" value="${id}">
        <input type="hidden" name="old_image[]" value="">
        <input type="hidden" name="delete_image[]" value="0">

        <div class="editor-grid">
            <div>
                <div class="small-grid">
                    <div>
                        <label>Date</label>
                        <input type="date" name="date[]" value="<?= date('Y-m-d') ?>" oninput="updateAllPreviews(); autosaveLocal();">
                    </div>
                    <div>
                        <label>Categorie</label>
                        <select name="category[]" oninput="updateAllPreviews(); autosaveLocal();">
                            <option>Prototype</option>
                            <option>Gameplay</option>
                            <option>Level Design</option>
                            <option>Graphics</option>
                            <option>Audio</option>
                            <option>Technical</option>
                            <option selected>General</option>
                        </select>
                    </div>
                </div>

                <label>Title</label>
                <input type="text" name="title[]" value="" oninput="updateAllPreviews(); autosaveLocal();">

                <label>Brief Excerpt</label>
                <input type="text" name="excerpt[]" value="" oninput="updateAllPreviews(); autosaveLocal();">

                <label>Image Upload</label>
                <input type="file" name="image[]" accept="image/*" onchange="updateAllPreviews(); autosaveLocal();">

                <div class="check-row">
                    <input type="checkbox" checked onchange="autosaveLocal();">
                    <label style="margin:0;">Published</label>
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
                <textarea name="body[]" oninput="updateAllPreviews(); autosaveLocal();"></textarea>
            </div>

            <div class="preview">
                <time></time>
                <div class="preview-category"></div>
                <h2></h2>
                <div class="preview-image-holder"></div>
                <div class="preview-excerpt"></div>
                <div class="preview-body"></div>
            </div>
        </div>
    `;

    list.prepend(div);
    initDragAndDrop();
    updateAllPreviews();
    autosaveLocal();
}

function deleteDevlog(button) {
    if (!confirm('Really delete this devlog entry?')) return;
    button.closest('.devlog-box').remove();
    updateAllPreviews();
    autosaveLocal();
}

function autosaveLocal() {
    const data = [];

    document.querySelectorAll('.devlog-box').forEach(box => {
        data.push({
            id: box.querySelector('input[name="id[]"]').value,
            date: box.querySelector('input[name="date[]"]').value,
            title: box.querySelector('input[name="title[]"]').value,
            category: box.querySelector('select[name="category[]"]').value,
            excerpt: box.querySelector('input[name="excerpt[]"]').value,
            body: box.querySelector('textarea[name="body[]"]').value,
            image: box.querySelector('input[name="old_image[]"]').value,
            published: box.querySelector('input[type="checkbox"]').checked
        });
    });

    localStorage.setItem('gds_devlog_draft', JSON.stringify(data));
    statusBox.textContent = 'Autosave saved locally.';
}

function clearDraft() {
    localStorage.removeItem('gds_devlog_draft');
    statusBox.textContent = 'Autosave draft deleted.';
}


function selectDevlogHeroScreenshot(select) {
    const oldInput = document.querySelector('input[name="old_devlog_hero_image"]');
    const deleteInput = document.querySelector('input[name="delete_devlog_hero_image"]');
    const fileInput = document.querySelector('input[name="devlog_hero_image"]');

    if (select.value && oldInput) {
        oldInput.value = select.value;
        if (deleteInput) deleteInput.value = '0';
        if (fileInput) fileInput.value = '';
    }
}

function removeDevlogHeroImage(button) {
    if (!confirm('Really delete hero image?')) return;

    const deleteInput = document.querySelector('input[name="delete_devlog_hero_image"]');
    const oldInput = document.querySelector('input[name="old_devlog_hero_image"]');
    const select = document.querySelector('select[name="devlog_hero_screenshot"]');

    if (deleteInput) deleteInput.value = '1';
    if (oldInput) oldInput.value = '';
    if (select) select.value = '';

    const imageBox = button.closest('.hero-image-box');
    if (imageBox) imageBox.remove();

    autosaveLocal();
}

function removeHeroImage(button) {
    if (!confirm('Really delete this image?')) return;

    const box = button.closest('.devlog-box');
    box.querySelector('input[name="old_image[]"]').value = '';
    
    const imageBox = button.closest('.hero-image-box');
    if (imageBox) {
        imageBox.remove();
    }

    const holder = box.querySelector('.preview-image-holder');
    if (holder) {
        holder.innerHTML = '';
    }

    updateAllPreviews();
    autosaveLocal();
}

function initDragAndDrop() {
    document.querySelectorAll('.devlog-box').forEach(box => {
        box.addEventListener('dragstart', () => {
            draggedItem = box;
            box.style.opacity = '.45';
        });

        box.addEventListener('dragend', () => {
            draggedItem = null;
            box.style.opacity = '1';
            updateAllPreviews();
            autosaveLocal();
        });

        box.addEventListener('dragover', e => {
            e.preventDefault();
            const current = e.currentTarget;

            if (current !== draggedItem) {
                const boxes = [...list.querySelectorAll('.devlog-box')];
                const draggedIndex = boxes.indexOf(draggedItem);
                const currentIndex = boxes.indexOf(current);

                if (draggedIndex < currentIndex) {
                    current.after(draggedItem);
                } else {
                    current.before(draggedItem);
                }
            }
        });
    });
}

form.addEventListener('submit', async e => {
    e.preventDefault();
    updateCheckboxNames();

    statusBox.textContent = 'Saving...';

    const formData = new FormData(form);

    const response = await fetch(window.location.href, {
        method:'POST',
        body:formData
    });

if (response.ok) {
    localStorage.removeItem('gds_devlog_draft');
    statusBox.textContent = 'Saved.';

    statusBox.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });

} else {
    statusBox.textContent = 'Error saving.';

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
