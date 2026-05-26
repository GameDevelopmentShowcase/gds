<?php    /* Copyright (C) 2026 Michael Rhein <michael@gamedevshow.de> - SPDX-License-Identifier: GPL-3.0-or-later
	      * news-edit.php
*/

require_once __DIR__ . '/common.php';

$newsFile = __DIR__ . '/../data/news.json';
$uploadDir = __DIR__ . '/../assets/news/';
$uploadUrl = '../assets/news/';

function clean($value) {
    return trim($value ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = [];
    $count = count($_POST['title'] ?? []);
    $usedImages = [];

    for ($i = 0; $i < $count; $i++) {
        $title = clean($_POST['title'][$i] ?? '');

        if ($title === '') {
            continue;
        }

        $oldImage = clean($_POST['old_image'][$i] ?? '');
        $image = $oldImage;

        if (!empty($_FILES['image']['name'][$i])) {
            $tmp = $_FILES['image']['tmp_name'][$i];
            $name = basename($_FILES['image']['name'][$i]);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($ext, $allowed, true)) {
                $newName = 'news_' . time() . '_' . $i . '.' . $ext;
                
                if ($oldImage && file_exists(__DIR__ . '/../' . $oldImage)) {
                    unlink(__DIR__ . '/../' . $oldImage);
                }
                
                move_uploaded_file($tmp, $uploadDir . $newName);
                $image = 'assets/news/' . $newName;
            }
        }

        if ($image) {
            $usedImages[] = $image;
        }

        $items[] = [
            'id' => clean($_POST['id'][$i] ?? uniqid('news_', true)),
            'date' => clean($_POST['date'][$i] ?? date('Y-m-d')),
            'title' => $title,
            'body' => clean($_POST['body'][$i] ?? ''),
            'image' => $image
        ];
    }

    $existingFiles = glob($uploadDir . 'news_*.*');
    foreach ($existingFiles as $file) {
        $relativePath = 'assets/news/' . basename($file);
        if (!in_array($relativePath, $usedImages)) {
            unlink($file);
        }
    }

    file_put_contents(
        $newsFile,
        json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    echo json_encode(['saved' => true]);
    exit;
}

$newsItems = file_exists($newsFile)
    ? json_decode(file_get_contents($newsFile), true)
    : [];

if (!is_array($newsItems)) {
    $newsItems = [];
}

if (count($newsItems) === 0) {
    $newsItems[] = [
        'id' => uniqid('news_', true),
        'date' => date('Y-m-d'),
        'title' => '',
        'body' => '',
        'image' => ''
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
        <h1>News Editor</h1>
    </div>

    <div class="title-actions">
        <button type="button" class="tool-btn" onclick="collapseAllSections()">Collapse all</button>
        <button type="button" class="tool-btn" onclick="expandAllSections()">Expand all</button>
        <button type="button" class="add-btn" onclick="addNews()">+ New News</button>
    </div>
</div>

    <div id="status" class="status">Ready.</div>

    <form id="newsForm" method="post" enctype="multipart/form-data">

        <div id="newsList">

        <?php foreach ($newsItems as $i => $news): ?>
            <div class="news-box">

				<div class="news-top">
					<div class="news-left" style="display:flex;">
						<button type="button" class="collapse-toggle" onclick="toggleSection(this)" aria-label="Collapse" title="Collapse">
							▾
						</button>
						<div class="drag-handle" style="margin-left:20px;">
							<div class="move-icon">
								<span class="horizontal">↔</span>
								<span class="vertical">↕</span>
							</div>
						</div>
						<div class="news-summary" style="margin-left:20px; padding-top:10px;">
							<span class="news-summary-title" style="margin-right:10px;"><?= htmlspecialchars($news['title'] ?? 'Untitled News') ?></span>|<span class="news-summary-date" style="margin-left:10px;"><?= htmlspecialchars($news['date'] ?? '') ?></span>
						</div>
					</div>

					<button type="button" class="delete-btn" onclick="deleteNews(this)">Delete</button>
				</div>

                <input type="hidden" name="id[]" value="<?= htmlspecialchars($news['id'] ?? uniqid('news_', true)) ?>">
                <input type="hidden" name="old_image[]" value="<?= htmlspecialchars($news['image'] ?? '') ?>">

				<div class="section-content">
					<div class="editor-grid">

                    <div>
                        <label>Date</label>
                        <input type="date" name="date[]" value="<?= htmlspecialchars($news['date'] ?? date('Y-m-d')) ?>" oninput="updateAllPreviews(); autosaveLocal();">

                        <label>Title</label>
                        <input type="text" name="title[]" value="<?= htmlspecialchars($news['title'] ?? '') ?>" oninput="updateAllPreviews(); autosaveLocal();">

                        <label>Image Upload</label>
                        <input type="file" name="image[]" accept="image/*" onchange="updateAllPreviews(); autosaveLocal();">

							<?php if (!empty($news['image'])): ?>
								<img class="preview-img" src="../<?= htmlspecialchars($news['image']) ?>" alt="">
							<?php endif; ?>

						<?php if (!empty($news['image'])): ?>
							<button type="button" class="delete-btn" onclick="removeImage(this)">Delete Image</button>
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
                        <textarea name="body[]" placeholder="Write your news here..." oninput="updateAllPreviews(); autosaveLocal();"><?= htmlspecialchars($news['body'] ?? '') ?></textarea>
                    </div>

                    <div class="preview">
                        <time></time>
                        <h2></h2>
                        <div class="preview-image-holder"></div>
                        <div class="preview-body"></div>
                    </div>

                </div>
            </div>
			</div>
        <?php endforeach; ?>
        </div>

        <div class="actions">
            <button class="save-btn" type="submit">Save News</button>
            <button class="tool-btn" type="button" onclick="clearDraft()">Delete AutoSave Draft</button>
        </div>

    </form>

    <div class="footer-space"></div>
</div>

<script>
const form = document.getElementById('newsForm');
const list = document.getElementById('newsList');
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

function removeImage(button) {
    if (!confirm('Really delete this image?')) return;

    const box = button.closest('.news-box');
    box.querySelector('input[name="old_image[]"]').value = '';
    const previewImg = box.querySelector('.preview-img');
    if (previewImg) {
        previewImg.remove();
    }
    const holder = box.querySelector('.preview-image-holder');
    if (holder) {
        holder.innerHTML = '';
    }
    button.remove();

    autosaveLocal();
}

function updateAllPreviews() {
    document.querySelectorAll('.news-box').forEach(box => {
        const date = box.querySelector('input[name="date[]"]').value;
        const title = box.querySelector('input[name="title[]"]').value;
        const body = box.querySelector('textarea[name="body[]"]').value;
        const oldImage = box.querySelector('input[name="old_image[]"]').value;

        box.querySelector('.preview time').textContent = formatDate(date);
        box.querySelector('.preview h2').textContent = title || 'Untitled Transmission';
        box.querySelector('.preview-body').innerHTML = marked.parse(body || '');

        const imageHolder = box.querySelector('.preview-image-holder');
        imageHolder.innerHTML = '';

        if (oldImage) {
            const img = document.createElement('img');
            img.src = '../' + oldImage;
            img.className = 'preview-img';
            imageHolder.appendChild(img);
        }
    });
}

function wrapText(button, before, after) {
    const box = button.closest('.news-box');
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

function addNews() {
    const id = 'news_' + Date.now();

    const div = document.createElement('div');
    div.className = 'news-box';
    div.draggable = true;

    div.innerHTML = `
<div class="news-top">

    <button
        type="button"
        class="collapse-toggle"
        onclick="toggleSection(this)"
        aria-label="Collapse"
        title="Collapse">
        ▾
    </button>

    <div class="drag-handle">
				<div class="move-icon">
				  <span class="horizontal">↔</span>
				  <span class="vertical">↕</span>
				</div>
			
			</div>
            <button type="button" class="delete-btn" onclick="deleteNews(this)">Delete</button>
        </div>

        <input type="hidden" name="id[]" value="${id}">
        <input type="hidden" name="old_image[]" value="">

<div class="section-content">
<div class="editor-grid">
            <div>
                <label>Date</label>
                <input type="date" name="date[]" value="<?= date('Y-m-d') ?>" oninput="updateAllPreviews(); autosaveLocal();">

                <label>Title</label>
                <input type="text" name="title[]" value="" oninput="updateAllPreviews(); autosaveLocal();">

                <label>Image Upload</label>
                <input type="file" name="image[]" accept="image/*" onchange="updateAllPreviews(); autosaveLocal();">
                        <?php if (!empty($news['image'])): ?>
                            <img class="preview-img" src="../<?= htmlspecialchars($news['image']) ?>" alt="">
                        <?php endif; ?>

						<?php if (!empty($news['image'])): ?>
							<button type="button" class="delete-btn" onclick="removeImage(this)"> Delete Image</button>
						<?php endif; ?>

                <div class="toolbar">
                  <button type="button" class="tool-btn" onclick="wrapText(this,'**','**')">Bold</button>
                  <button type="button" class="tool-btn" onclick="wrapText(this,'*','*')">Italic</button>
                  <button type="button" class="tool-btn" onclick="wrapText(this,'## ','')">H2</button>
				  <button type="button" class="tool-btn" onclick="wrapText(this,'### ','')">H3</button>
				  <button type="button" class="tool-btn" onclick="wrapText(this,'#### ','')">H4</button>
                  <button type="button" class="tool-btn" onclick="wrapText(this,'- ','')">List</button>
                  <button type="button" class="tool-btn" onclick="wrapText(this,'[Linktext](',')')">Link</button>e</button>
                </div>

                <label>Markdown Text</label>
                <textarea name="body[]" oninput="updateAllPreviews(); autosaveLocal();"></textarea>
            </div>

            <div class="preview">
                <time></time>
                <h2></h2>
                <div class="preview-image-holder"></div>
                <div class="preview-body"></div>
            </div>
        </div>
</div>
    `;

    list.prepend(div);
    initDragAndDrop();
    updateAllPreviews();
    autosaveLocal();
}

function toggleSection(button) {
    const box = button.closest('.news-box');

    box.classList.toggle('collapsed');

    button.textContent =
        box.classList.contains('collapsed')
            ? '▸'
            : '▾';
}

function collapseAllSections() {
    document.querySelectorAll('.news-box').forEach(box => {
        box.classList.add('collapsed');

        const button = box.querySelector('.collapse-toggle');

        if (button) {
            button.textContent = '▸';
        }
    });
}

function expandAllSections() {
    document.querySelectorAll('.news-box').forEach(box => {
        box.classList.remove('collapsed');

        const button = box.querySelector('.collapse-toggle');

        if (button) {
            button.textContent = '▾';
        }
    });
}

function deleteNews(button) {
    if (!confirm('Really delete this news item?')) return;
    button.closest('.news-box').remove();
    updateAllPreviews();
    autosaveLocal();
}

function autosaveLocal() {
    const data = [];

    document.querySelectorAll('.news-box').forEach(box => {
        data.push({
            id: box.querySelector('input[name="id[]"]').value,
            date: box.querySelector('input[name="date[]"]').value,
            title: box.querySelector('input[name="title[]"]').value,
            body: box.querySelector('textarea[name="body[]"]').value,
            image: box.querySelector('input[name="old_image[]"]').value
        });
    });

    localStorage.setItem('gds_news_draft', JSON.stringify(data));
    statusBox.textContent = 'Autosave saved locally.';
}

function clearDraft() {
    localStorage.removeItem('gds_news_draft');
    statusBox.textContent = 'AutoSave draft deleted.';
}

function initDragAndDrop() {
    document.querySelectorAll('.news-box').forEach(box => {

        const handle = box.querySelector('.drag-handle');

        handle.draggable = true;

        handle.addEventListener('dragstart', e => {
            draggedItem = box;
            box.style.opacity = '.45';

            e.dataTransfer.effectAllowed = 'move';
        });

        handle.addEventListener('dragend', () => {
            draggedItem = null;
            box.style.opacity = '1';
            autosaveLocal();
        });

        box.addEventListener('dragover', e => {
            e.preventDefault();

            const current = e.currentTarget;

            if (current !== draggedItem) {

                const boxes = [...list.querySelectorAll('.news-box')];

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

    statusBox.textContent = 'Saving...';

    const formData = new FormData(form);

    const response = await fetch(window.location.href, {
        method:'POST',
        body:formData
    });

if (response.ok) {
    localStorage.removeItem('gds_news_draft');
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
