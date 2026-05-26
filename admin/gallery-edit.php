<?php    /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later
	      * gallerie-edit.php
*/

require_once __DIR__ . '/common.php';

$galleryFile = __DIR__ . '/../data/gallery.json';
$uploadDir = __DIR__ . '/../assets/screenshots/';

function clean_value($value) {
    return trim($value ?? '');
}

function make_id() {
    return 'shot_' . str_replace('.', '', uniqid('', true));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = [];
    $count = count($_POST['alt'] ?? []);

    for ($i = 0; $i < $count; $i++) {
        $alt = clean_value($_POST['alt'][$i] ?? '');
        $image = clean_value($_POST['old_image'][$i] ?? '');

        if (!empty($_FILES['image']['name'][$i])) {
            $tmp = $_FILES['image']['tmp_name'][$i];
            $name = basename($_FILES['image']['name'][$i]);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (is_uploaded_file($tmp) && in_array($ext, $allowed, true)) {
                $newName = 'shot_' . time() . '_' . $i . '.' . $ext;

                if (move_uploaded_file($tmp, $uploadDir . $newName)) {
                    $image = 'assets/screenshots/' . $newName;
                }
            }
        }

        if ($image === '') {
            continue;
        }

        $items[] = [
            'id' => clean_value($_POST['id'][$i] ?? make_id()),
            'image' => $image,
            'alt' => $alt !== '' ? $alt : 'screenshot',
            'featured' => isset($_POST['featured'][$i])
        ];
    }

    if (count($items) > 0) {
        $hasFeatured = false;

        foreach ($items as $item) {
            if (!empty($item['featured'])) {
                $hasFeatured = true;
                break;
            }
        }

        if (!$hasFeatured) {
            $items[0]['featured'] = true;
        }
    }

$oldItems = file_exists($galleryFile)
    ? json_decode(file_get_contents($galleryFile), true)
    : [];

if (!is_array($oldItems)) {
    $oldItems = [];
}
$oldImages = [];

foreach ($oldItems as $oldItem) {
    if (!empty($oldItem['image'])) {
        $oldImages[] = $oldItem['image'];
    }
}

$newImages = [];

foreach ($items as $newItem) {
    if (!empty($newItem['image'])) {
        $newImages[] = $newItem['image'];
    }
}

$deletedImages = array_diff($oldImages, $newImages);

foreach ($deletedImages as $imagePath) {

    $fullPath = __DIR__ . '/../' . $imagePath;

    if (
        file_exists($fullPath) &&
        strpos(realpath($fullPath), realpath($uploadDir)) === 0
    ) {
        unlink($fullPath);
    }
}
    file_put_contents(
        $galleryFile,
        json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    echo json_encode(['saved' => true]);
    exit;
}

$galleryItems = file_exists($galleryFile)
    ? json_decode(file_get_contents($galleryFile), true)
    : [];

if (!is_array($galleryItems)) {
    $galleryItems = [];
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
            <h1>Gallery Editor</h1>
        </div>

        <button type="button" class="add-btn" onclick="addImage()">+ Add Bild</button>
    </div>

    <div id="status" class="status">Redy.</div>

    <form id="galleryForm" method="post" enctype="multipart/form-data">

	  <div class="quicksavediv">
		  <div class="quicksavediv2"><p class="hint">Images deleted from the gallery are also deleted from the folder.</p></div>
		  <div class="quicksavediv3"><button class="save-btn" type="submit" height="20px">Save</button></div>
		  <div class="quicksavediv4"></div>
	  </div><br>

        <div id="galleryList" class="gallery-list">

            <?php foreach ($galleryItems as $i => $item): ?>
                <div class="gallery-box" draggable="true">
                    <input type="hidden" name="id[]" value="<?= htmlspecialchars($item['id'] ?? make_id()) ?>">
                    <input type="hidden" name="old_image[]" value="<?= htmlspecialchars($item['image'] ?? '') ?>">

                    <div class="gallery-row">
                        <div>
                            <div class="drag-handle">
							
							<div class="move-icon">
							  <span class="horizontal">↔</span>
							  <span class="vertical">↕</span>
							</div>
							</div><br><br>
                            <div class="thumb">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="../<?= htmlspecialchars($item['image']) ?>" alt="">
                                <?php else: ?>
                                    <div class="placeholder">NO IMAGE</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div><br><br>
                            <label>Image Upload</label>
                            <input type="file" name="image[]" accept="image/*" onchange="previewSelectedImage(this); autosaveLocal();">

                            <label>Alt Text</label>
                            <input type="text" name="alt[]" value="<?= htmlspecialchars($item['alt'] ?? '') ?>" oninput="autosaveLocal();">

                            <div class="check-row">
                                <input type="checkbox" name="featured[<?= $i ?>]" <?= !empty($item['featured']) ? 'checked' : '' ?> onchange="updateFeatured(this); autosaveLocal();">
                                <label style="margin:0;">Big first image</label>
                            </div>
                        </div>

                        <div>
                            <button type="button" class="delete-btn" onclick="deleteImage(this)">Delete</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>

        <div class="actions">
            <button class="save-btn" type="submit">Save Gallery</button>
            <button class="tool-btn" type="button" onclick="clearDraft()">delete AutoSave Draft</button>
        </div>
    </form>

    <div class="footer-space"></div>
</div>

<script>
const form = document.getElementById('galleryForm');
const list = document.getElementById('galleryList');
const statusBox = document.getElementById('status');

let draggedItem = null;

function updateCheckboxNames() {
    document.querySelectorAll('.gallery-box').forEach((box, index) => {
        const checkbox = box.querySelector('input[type="checkbox"]');
        checkbox.name = 'featured[' + index + ']';
    });
}

function updateFeatured(activeCheckbox) {
    if (!activeCheckbox.checked) {
        return;
    }

    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        if (checkbox !== activeCheckbox) {
            checkbox.checked = false;
        }
    });
}

function previewSelectedImage(input) {
    const box = input.closest('.gallery-box');
    const thumb = box.querySelector('.thumb');

    if (!input.files || !input.files[0]) {
        return;
    }

    const reader = new FileReader();
    reader.onload = e => {
        thumb.innerHTML = '';
        const img = document.createElement('img');
        img.src = e.target.result;
        thumb.appendChild(img);
    };
    reader.readAsDataURL(input.files[0]);
}

function addImage() {
    const id = 'shot_' + Date.now();

    const div = document.createElement('div');
    div.className = 'gallery-box';
    div.draggable = true;

    div.innerHTML = `
        <input type="hidden" name="id[]" value="${id}">
        <input type="hidden" name="old_image[]" value="">

        <div class="gallery-row">
            <div>
                <div class="drag-handle">
				
				<div class="move-icon">
				  <span class="horizontal">↔</span>
				  <span class="vertical">↕</span>
				</div>
				
				</div><br><br>
                <div class="thumb">
                    <div class="placeholder">NO IMAGE</div>
                </div>
            </div>

            <div><br><br>
                <label>Image Upload</label>
                <input type="file" name="image[]" accept="image/*" onchange="previewSelectedImage(this); autosaveLocal();">

                <label>Alt Text</label>
                <input type="text" name="alt[]" value="" oninput="autosaveLocal();">

                <div class="check-row">
                    <input type="checkbox" onchange="updateFeatured(this); autosaveLocal();">
                    <label style="margin:0;">Großes erstes Bild</label>
                </div>
            </div>

            <div>
                <button type="button" class="delete-btn" onclick="deleteImage(this)">Delete</button>
            </div>
        </div>
    `;

    list.prepend(div);
    initDragAndDrop();
    updateCheckboxNames();
    autosaveLocal();
}

function deleteImage(button) {
    if (!confirm('Really delete this image?')) return;
    button.closest('.gallery-box').remove();
    updateCheckboxNames();
    autosaveLocal();
}

function autosaveLocal() {
    const data = [];

    document.querySelectorAll('.gallery-box').forEach(box => {
        data.push({
            id: box.querySelector('input[name="id[]"]').value,
            image: box.querySelector('input[name="old_image[]"]').value,
            alt: box.querySelector('input[name="alt[]"]').value,
            featured: box.querySelector('input[type="checkbox"]').checked
        });
    });

    localStorage.setItem('gds_gallery_draft', JSON.stringify(data));
    statusBox.textContent = 'Autosave saved locally.';
}

function clearDraft() {
    localStorage.removeItem('gds_gallery_draft');
    statusBox.textContent = 'Autosave draft deleted.';
}

function initDragAndDrop() {
    document.querySelectorAll('.gallery-box').forEach(box => {
        box.addEventListener('dragstart', () => {
            draggedItem = box;
            box.style.opacity = '.45';
        });

        box.addEventListener('dragend', () => {
            draggedItem = null;
            box.style.opacity = '1';
            updateCheckboxNames();
            autosaveLocal();
        });

        box.addEventListener('dragover', e => {
            e.preventDefault();
            const current = e.currentTarget;

            if (current !== draggedItem) {
                const boxes = [...list.querySelectorAll('.gallery-box')];
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
        localStorage.removeItem('gds_gallery_draft');
        statusBox.textContent = 'Saved.';
    } else {
        statusBox.textContent = 'Error saving.';
    }
});

initDragAndDrop();
updateCheckboxNames();
</script>

</body>
</html>
