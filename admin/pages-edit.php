<?php    /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later
	      * pages-edit.php
*/

require_once __DIR__ . '/common.php';

$pagesFile = __DIR__ . '/../data/pages.json';
$uploadDir = __DIR__ . '/../assets/pages/';

function clean_page_value($value) {
    return trim($value ?? '');
}

function make_page_id() {
    return 'page_' . str_replace('.', '', uniqid('', true));
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
        $image = clean_page_value($item['image'] ?? '');
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

function make_slug($value) {
    $value = trim($value ?? '');
    $value = strtolower($value);
    $value = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    $value = trim($value, '-');
    return $value !== '' ? $value : 'seite';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = [];
    $usedSlugs = [];
    $usedImages = [];
    $count = count($_POST['title'] ?? []);

    for ($i = 0; $i < $count; $i++) {
        $title = clean_page_value($_POST['title'][$i] ?? '');

        if ($title === '') {
            continue;
        }

        $slug = make_slug($_POST['slug'][$i] ?? $title);
        $baseSlug = $slug;
        $suffix = 2;

        while (isset($usedSlugs[$slug])) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        $usedSlugs[$slug] = true;

        $oldImage = clean_page_value($_POST['old_image'][$i] ?? '');
        $image = $oldImage;

        $screenshotImage = clean_page_value($_POST['screenshot_image'][$i] ?? '');
        if ($screenshotImage !== '') {
            if (!empty($image) && is_uploaded_asset($image, 'pages')) {
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
                $newName = 'page_' . time() . '_' . $i . '.' . $ext;
                if (move_uploaded_file($tmp, $uploadDir . $newName)) {
                    if (!empty($oldImage) && is_uploaded_asset($oldImage, 'pages')) {
                        $oldFile = __DIR__ . '/../' . ltrim($oldImage, '/');
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                    $image = 'assets/pages/' . $newName;
                }
            }
        }

        if ($image && is_uploaded_asset($image, 'pages')) {
            $usedImages[] = $image;
        }

        $items[] = [
            'id' => clean_page_value($_POST['id'][$i] ?? make_page_id()),
            'title' => $title,
            'nav_title' => clean_page_value($_POST['nav_title'][$i] ?? $title),
            'slug' => $slug,
            'eyebrow' => clean_page_value($_POST['eyebrow'][$i] ?? ''),
            'excerpt' => clean_page_value($_POST['excerpt'][$i] ?? ''),
            'body' => clean_page_value($_POST['body'][$i] ?? ''),
            'image' => $image,
            'published' => isset($_POST['published'][$i]),
            'show_in_nav' => isset($_POST['show_in_nav'][$i])
        ];
    }

    $existingFiles = glob($uploadDir . 'page_*.*');
    foreach ($existingFiles as $file) {
        $relativePath = 'assets/pages/' . basename($file);
        if (!in_array($relativePath, $usedImages)) {
            unlink($file);
        }
    }

    file_put_contents(
        $pagesFile,
        json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    echo json_encode(['saved' => true]);
    exit;
}

$pages = file_exists($pagesFile)
    ? json_decode(file_get_contents($pagesFile), true)
    : [];

if (!is_array($pages)) {
    $pages = [];
}

$screenshotOptions = load_screenshot_options();

if (count($pages) === 0) {
    $pages[] = [
        'id' => make_page_id(),
        'title' => '',
        'nav_title' => '',
        'slug' => '',
        'eyebrow' => '',
        'excerpt' => '',
        'body' => '',
        'image' => '',
        'published' => true,
		'show_in_nav' => true
    ];
}
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
            <h1>Pages Editor</h1>
        </div>

        <button type="button" class="add-btn" onclick="addPage()">+ New Page</button>
    </div>

    <div id="status" class="status" >Ready.</div>

    <form id="pagesForm" method="post" enctype="multipart/form-data">

  <div class="quicksavediv">
	  <div class="quicksavediv2"><p class="hint"></p></div>
	  <div class="quicksavediv3"><button class="save-btn" type="submit" height="20px">Save</button></div>
	  <div class="quicksavediv4"></div>
  </div><br>
  
        <div id="pagesList">

        <?php foreach ($pages as $i => $page): ?>
            <div class="devlog-box" draggable="true">

                <div class="devlog-top">
                    <div class="drag-handle">

				<div class="move-icon">
				  <span class="horizontal">↔</span>
				  <span class="vertical">↕</span>
				</div>					
					
					</div>
                    <button type="button" class="delete-btn" onclick="deletePage(this)">Delete</button>
                </div>

                <input type="hidden" name="id[]" value="<?= htmlspecialchars($page['id'] ?? make_page_id()) ?>">
                <input type="hidden" name="old_image[]" value="<?= htmlspecialchars($page['image'] ?? '') ?>">
				<input type="hidden" name="delete_image[]" value="0">
                <div class="editor-grid">

                    <div>
                        <div class="small-grid">
                            <div>
                                <label>Title</label>
                                <input type="text" name="title[]" value="<?= htmlspecialchars($page['title'] ?? '') ?>" oninput="syncSlug(this); updateAllPreviews(); autosaveLocal();">
                            </div>

                            <div>
                                <label>Navgation Title</label>
                                <input type="text" name="nav_title[]" value="<?= htmlspecialchars($page['nav_title'] ?? '') ?>" placeholder="optional kürzer" oninput="updateAllPreviews(); autosaveLocal();">
                            </div>
                        </div>

                        <label>URL Slug</label>
                        <input type="text" name="slug[]" value="<?= htmlspecialchars($page['slug'] ?? '') ?>" placeholder="ShorText" oninput="updateAllPreviews(); autosaveLocal();">
                        <label>Eyebrow (kleiner Text über der Headline)</label>
                        <input type="text" name="eyebrow[]" value="<?= htmlspecialchars($page['eyebrow'] ?? '') ?>" placeholder="z.B. Making Of, Lore, Update..." oninput="updateAllPreviews(); autosaveLocal();">
                        <label>Brief Excerpt / Meta Description</label>
                        <input type="text" name="excerpt[]" value="<?= htmlspecialchars($page['excerpt'] ?? '') ?>" oninput="updateAllPreviews(); autosaveLocal();">

                        <label>Hero Image Upload</label>
                        <input type="file" name="image[]" accept="image/*" onchange="updateAllPreviews(); autosaveLocal();">

                        <label>Or choose a hero from screenshots</label>
                        <select name="screenshot_image[]" onchange="selectScreenshotImage(this); updateAllPreviews(); autosaveLocal();">
                            <option value="">— Do not change / No screenshot —</option>
                            <?php foreach ($screenshotOptions as $shot): ?>
                                <option value="<?= htmlspecialchars($shot['image']) ?>" <?= (($page['image'] ?? '') === ($shot['image'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($shot['label'] ?? basename($shot['image'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

						<?php if (!empty($page['image'])): ?>
							<div class="hero-image-box">
								<img class="preview-img" src="../<?= htmlspecialchars($page['image']) ?>" alt="">

								<button type="button" class="delete-btn" onclick="removeHeroImage(this)">Delete Hero Image</button></div><br><br>
						<?php endif; ?>

                        <div class="check-row">
                            <input type="checkbox" data-type="published" name="published[<?= $i ?>]" <?= !empty($page['published']) ? 'checked' : '' ?> onchange="autosaveLocal();">
                            <label style="margin:0;">Published</label>
                        </div>

                        <div class="check-row">
                            <input type="checkbox" data-type="show_in_nav" name="show_in_nav[<?= $i ?>]" <?= (($page['show_in_nav'] ?? true) ? 'checked' : '') ?> onchange="autosaveLocal();">
                            <label style="margin:0;">Show in navigation</label>
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
                        <textarea name="body[]" placeholder="Write your entry here..." oninput="updateAllPreviews(); autosaveLocal();"><?= htmlspecialchars($page['body'] ?? '') ?></textarea>
                    </div>

                    <div class="preview">
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
            <button class="save-btn" type="submit">Save Page</button>
            <button class="tool-btn" type="button" onclick="clearDraft()">Delete AutoSave Draft</button>
        </div>

    </form>

    <div class="footer-space"></div>
</div>

<script>
const form = document.getElementById('pagesForm');
const list = document.getElementById('pagesList');
const statusBox = document.getElementById('status');
const screenshotOptionsHtml = `<?php foreach ($screenshotOptions as $shot): ?><option value="<?= htmlspecialchars($shot['image'], ENT_QUOTES) ?>"><?= htmlspecialchars($shot['label'] ?? basename($shot['image'] ?? ''), ENT_QUOTES) ?></option><?php endforeach; ?>`;

let draggedItem = null;

function slugify(value) {
    return (value || '')
        .toLowerCase()
        .replaceAll('ä', 'ae')
        .replaceAll('ö', 'oe')
        .replaceAll('ü', 'ue')
        .replaceAll('ß', 'ss')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function syncSlug(input) {
    const box = input.closest('.devlog-box');
    const slugInput = box.querySelector('input[name="slug[]"]');
    if (!slugInput.value.trim()) {
        slugInput.value = slugify(input.value);
    }
}

function updateCheckboxNames() {
    document.querySelectorAll('.devlog-box').forEach((box, index) => {
        const published = box.querySelector('input[data-type="published"]');
        const nav = box.querySelector('input[data-type="show_in_nav"]');

        if (published) {
            published.name = 'published[' + index + ']';
        }

        if (nav) {
            nav.name = 'show_in_nav[' + index + ']';
        }
    });
}

function updateAllPreviews() {
    document.querySelectorAll('.devlog-box').forEach(box => {
        const title = box.querySelector('input[name="title[]"]').value;
        const navTitle = box.querySelector('input[name="nav_title[]"]').value;
        const slug = box.querySelector('input[name="slug[]"]').value;
        const eyebrow = box.querySelector('input[name="eyebrow[]"]').value; // NEU
        const excerpt = box.querySelector('input[name="excerpt[]"]').value;
        const body = box.querySelector('textarea[name="body[]"]').value;
        const oldImage = box.querySelector('input[name="old_image[]"]').value;
        const deleteImage = box.querySelector('input[name="delete_image[]"]')?.value === '1';

        box.querySelector('.preview-category').textContent = 'page.php?p=' + (slug || slugify(title) || 'seite');
        box.querySelector('.preview h2').textContent = title || 'Untitled Page';
        box.querySelector('.preview-excerpt').textContent = navTitle ? 'Navi: ' + navTitle + ' — ' + excerpt : excerpt;
        box.querySelector('.preview-body').innerHTML = marked.parse(body || '');

        const imageHolder = box.querySelector('.preview-image-holder');
        imageHolder.innerHTML = '';

const fileInput = box.querySelector('input[type="file"]');

if (fileInput.files && fileInput.files[0]) {

    const img = document.createElement('img');
    img.src = URL.createObjectURL(fileInput.files[0]);
    img.className = 'preview-img';

    imageHolder.appendChild(img);

} else if (oldImage && !deleteImage) {

    const img = document.createElement('img');
    img.src = '../' + oldImage;
    img.className = 'preview-img';

    imageHolder.appendChild(img);
}
    });

    updateCheckboxNames();
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

function addPage() {
    const id = 'page_' + Date.now();

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
            <button type="button" class="delete-btn" onclick="deletePage(this)">Delete</button>
        </div>

        <input type="hidden" name="id[]" value="${id}">
        <input type="hidden" name="old_image[]" value="">
        <input type="hidden" name="delete_image[]" value="0">

        <div class="editor-grid">
            <div>
                <div class="small-grid">
                    <div>
                        <label>Title</label>
                        <input type="text" name="title[]" value="" oninput="syncSlug(this); updateAllPreviews(); autosaveLocal();">
                    </div>
                    <div>
                        <label>Navi-Title</label>
                        <input type="text" name="nav_title[]" value="" placeholder="optional kürzer" oninput="updateAllPreviews(); autosaveLocal();">
                    </div>
                </div>

                <label>URL Slug</label>
                <input type="text" name="slug[]" value="" placeholder="z-b-lore" oninput="updateAllPreviews(); autosaveLocal();">

                <label>Eyebrow (kleiner Text über der Headline)</label>
                <input type="text" name="eyebrow[]" value="" placeholder="z.B. Making Of" oninput="updateAllPreviews(); autosaveLocal();">
				
                <label>Brief Excerpt / Meta Description</label>
                <input type="text" name="excerpt[]" value="" oninput="updateAllPreviews(); autosaveLocal();">

                <label>Hero Image Upload</label>
                <input type="file" name="image[]" accept="image/*" onchange="updateAllPreviews(); autosaveLocal();">

                <label>Or choose a hero from screenshots</label>
                <select name="screenshot_image[]" onchange="selectScreenshotImage(this); updateAllPreviews(); autosaveLocal();">
                    <option value="">— Do not change / No screenshot —</option>${screenshotOptionsHtml}
                </select>

                <div class="check-row">
                    <input type="checkbox" data-type="published" checked onchange="autosaveLocal();">
                    <label style="margin:0;">Published</label>
                </div>

                <div class="check-row">
                    <input type="checkbox" data-type="show_in_nav" checked onchange="autosaveLocal();">
                    <label style="margin:0;">Show in navigation</label>
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

function deletePage(button) {
    if (!confirm('Diese Seite wirklich löschen?')) return;
    button.closest('.devlog-box').remove();
    updateAllPreviews();
    autosaveLocal();
}

function autosaveLocal() {
    const data = [];

    document.querySelectorAll('.devlog-box').forEach(box => {
        data.push({
            id: box.querySelector('input[name="id[]"]').value,
            title: box.querySelector('input[name="title[]"]').value,
            nav_title: box.querySelector('input[name="nav_title[]"]').value,
            slug: box.querySelector('input[name="slug[]"]').value,
    eyebrow: box.querySelector('input[name="eyebrow[]"]').value, // NEU
            excerpt: box.querySelector('input[name="excerpt[]"]').value,
            body: box.querySelector('textarea[name="body[]"]').value,
            image: box.querySelector('input[name="old_image[]"]').value,
            published: box.querySelector('input[data-type="published"]')?.checked ?? false,
            show_in_nav: box.querySelector('input[data-type="show_in_nav"]')?.checked ?? true
        });
    });

    localStorage.setItem('static_zone_pages_draft', JSON.stringify(data));
    statusBox.textContent = 'AutoSave lokal gespeichert.';
}

function clearDraft() {
    localStorage.removeItem('static_zone_pages_draft');
    statusBox.textContent = 'AutoSave Draft gelöscht.';
}

function removeHeroImage(button) {
    if (!confirm('Really delete hero image?')) return;

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
    localStorage.removeItem('gds_pages_draft');
    statusBox.textContent = 'Saved. Reloading...';

    setTimeout(() => {
        window.location.reload();
    }, 500);
    
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
