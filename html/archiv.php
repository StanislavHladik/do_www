<?php
// Resolve machine parameters (same pattern as rest of the app)
if (isset($_GET['cisloStroj']) && isset($_GET['nazevStroj']) && isset($_GET['popisStroj'])) {
    $cisloStroj = htmlspecialchars($_GET['cisloStroj']);
    $nazevStroj = htmlspecialchars($_GET['nazevStroj']);
    $popisStroj = htmlspecialchars($_GET['popisStroj']);
} else {
    $cisloStroj = "1";
    $nazevStroj = "testovaci_pracoviste";
    $popisStroj = "Kontrola svárů - Flídr Metal s.r.o.";
}

include 'header.php';
?>

<style>
/* ── Layout ────────────────────────────────────────────────── */
.archiv-layout {
    display: flex;
    min-height: calc(100vh - 160px);
}
.archiv-sidebar {
    width: 230px;
    min-width: 230px;
    background: #fff;
    border-right: 1px solid #ddd;
    padding: 14px 12px;
    overflow-y: auto;
    max-height: calc(100vh - 160px);
    position: sticky;
    top: 0;
    align-self: flex-start;
}
.archiv-sidebar h3 {
    margin: 0 0 10px;
    font-size: 12px;
    text-transform: uppercase;
    color: #888;
    letter-spacing: 0.6px;
}
.batch-item {
    padding: 8px 10px;
    margin-bottom: 3px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    color: #333;
    transition: background 0.15s;
    user-select: none;
}
.batch-item:hover { background: #f0f4f8; }
.batch-item.active { background: #2c3e50; color: #fff; }
.batch-count {
    background: #e9ecef;
    color: #666;
    border-radius: 12px;
    padding: 1px 8px;
    font-size: 11px;
    min-width: 28px;
    text-align: center;
}
.batch-item.active .batch-count { background: rgba(255,255,255,.2); color: #fff; }

/* ── Main area ─────────────────────────────────────────────── */
.archiv-main {
    flex: 1;
    padding: 16px 20px;
    overflow-x: hidden;
    min-width: 0;
}
.archiv-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}
#batch-title  { font-size: 16px; font-weight: 600; color: #2c3e50; }
#image-count  { font-size: 13px; color: #888; }

/* ── Image grid ────────────────────────────────────────────── */
.archiv-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}
.img-card {
    border-radius: 6px;
    overflow: hidden;
    cursor: pointer;
    background: #fff;
    border: 1px solid #e0e0e0;
    box-shadow: 0 1px 3px rgba(0,0,0,.07);
    transition: box-shadow 0.15s, transform 0.15s;
    display: flex;
    flex-direction: column;
}
.img-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
    transform: translateY(-2px);
}
.gallery-img-wrap {
    aspect-ratio: 4/3;
    overflow: hidden;
    position: relative;
    background: #ddd;
}
.gallery-img-wrap img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.2s;
}
.img-card:hover .gallery-img-wrap img { transform: scale(1.04); }
.img-date {
    padding: 5px 8px;
    font-size: 12px;
    color: #444;
    background: #f8f9fa;
    border-top: 1px solid #eee;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.2px;
}
.archiv-placeholder {
    color: #aaa; text-align: center;
    padding: 60px 20px; font-size: 16px;
    grid-column: 1 / -1;
}

/* ── Pagination ────────────────────────────────────────────── */
.archiv-pagination {
    margin-top: 20px;
    display: flex; gap: 5px; flex-wrap: wrap; justify-content: center;
}
.page-btn {
    padding: 5px 11px;
    border: 1px solid #ddd; background: #fff;
    border-radius: 4px; cursor: pointer;
    font-size: 14px; color: #333;
}
.page-btn:hover { background: #f0f4f8; }
.page-btn.active { background: #2c3e50; color: #fff; border-color: #2c3e50; }

/* ── Lightbox ──────────────────────────────────────────────── */
#lightbox {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.92);
    z-index: 9999;
    justify-content: center; align-items: center; flex-direction: column;
}
#lightbox.open { display: flex; }
#lightbox-img {
    max-width: 90vw; max-height: 85vh;
    border-radius: 4px;
    object-fit: contain;
    box-shadow: 0 4px 30px rgba(0,0,0,.5);
}
.lightbox-close {
    position: absolute; top: 14px; right: 18px;
    background: none; border: none; color: #fff;
    font-size: 28px; cursor: pointer; line-height: 1; padding: 4px 8px;
}
.lightbox-nav {
    position: absolute; top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,.15); border: none; color: #fff;
    font-size: 26px; padding: 12px 16px; cursor: pointer;
    border-radius: 4px; line-height: 1;
}
.lightbox-nav:hover { background: rgba(255,255,255,.3); }
#lb-prev { left: 14px; }
#lb-next { right: 14px; }
.lightbox-caption {
    color: #ccc; font-size: 13px; margin-top: 10px;
    text-align: center; max-width: 80vw;
}
.loading-text { color: #aaa; font-size: 13px; }

/* ── Search bar ───────────────────────────────────────────── */
.archiv-search {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    padding: 10px 12px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    font-size: 13px;
    color: #444;
}
.archiv-search label { white-space: nowrap; font-weight: 500; }
.archiv-search input[type="datetime-local"] {
    padding: 4px 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 13px;
    color: #333;
    background: #fff;
}
.archiv-search input[type="datetime-local"]:focus {
    outline: none;
    border-color: #2c3e50;
    box-shadow: 0 0 0 2px rgba(44,62,80,.15);
}
.search-btn {
    padding: 4px 14px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
}
.search-btn-apply { background: #2c3e50; color: #fff; }
.search-btn-apply:hover { background: #3d5166; }
.search-btn-clear  { background: #e9ecef; color: #555; }
.search-btn-clear:hover  { background: #dee2e6; }
.search-badge {
    background: #e8f4fd; color: #1a6fa0;
    border: 1px solid #bee3f8;
    border-radius: 12px; padding: 2px 10px;
    font-size: 12px; display: none;
}
    .archiv-layout    { flex-direction: column; }
    .archiv-sidebar   { width: 100%; min-width: 0; max-height: 180px; }
    .archiv-gallery   { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); }
}
</style>

<main>
    <div class="archiv-layout">

        <!-- ── Sidebar: batch list ── -->
        <aside class="archiv-sidebar">
            <h3>Sběry</h3>
            <div id="batch-list">
                <p class="loading-text">Načítání…</p>
            </div>
        </aside>

        <!-- ── Main: gallery ── -->
        <section class="archiv-main">
            <!-- Search bar -->
            <div class="archiv-search">
                <label for="search-from">Od:</label>
                <input type="datetime-local" id="search-from">
                <label for="search-to">Do:</label>
                <input type="datetime-local" id="search-to">
                <button class="search-btn search-btn-apply" onclick="applySearch()">Hledat</button>
                <button class="search-btn search-btn-clear"  onclick="clearSearch()">Vymazat</button>
                <span class="search-badge" id="search-badge"></span>
            </div>

            <div class="archiv-toolbar">
                <span id="batch-title">Vyberte sběr</span>
                <span id="image-count"></span>
            </div>

            <div class="archiv-gallery" id="archiv-gallery">
                <p class="archiv-placeholder">← Vyberte sběr ze seznamu vlevo</p>
            </div>

            <div class="archiv-pagination" id="archiv-pagination"></div>
        </section>

    </div>

    <!-- ── Lightbox ── -->
    <div id="lightbox">
        <button class="lightbox-close" onclick="closeLightbox()">&#10005;</button>
        <button class="lightbox-nav" id="lb-prev" onclick="navigateLightbox(-1)">&#10094;</button>
        <img id="lightbox-img" src="" alt="">
        <button class="lightbox-nav" id="lb-next"  onclick="navigateLightbox(1)">&#10095;</button>
        <div class="lightbox-caption" id="lightbox-caption"></div>
    </div>
</main>

<script>
const MACHINE = <?php echo json_encode($cisloStroj); ?>;
let currentBatch  = null;
let currentPage   = 1;
let currentImages = [];   // filenames on the current page
let lbIndex       = 0;    // index within currentImages for lightbox
let activeSearch  = { dateFrom: '', dateTo: '' };

// ── Init ──────────────────────────────────────────────────────
loadBatches();

// ── Batch list ────────────────────────────────────────────────
function loadBatches() {
    fetch('archiv_api.php?action=batches&cisloStroj=' + encodeURIComponent(MACHINE))
        .then(r => r.json())
        .then(data => {
            renderBatchList(data.batches || []);
            if (data.batches && data.batches.length > 0) {
                selectBatch(data.batches[0].name);
            }
        })
        .catch(() => {
            document.getElementById('batch-list').innerHTML =
                '<p class="loading-text">Chyba při načítání sběrů</p>';
        });
}

function renderBatchList(batches) {
    const el = document.getElementById('batch-list');
    if (!batches.length) {
        el.innerHTML = '<p class="loading-text">Žádné sběry</p>';
        return;
    }
    el.innerHTML = batches.map(b =>
        `<div class="batch-item" id="batch-${b.name}" onclick="selectBatch('${b.name}')">
            <span>${b.name}</span>
            <span class="batch-count">${b.count}</span>
         </div>`
    ).join('');
}

function selectBatch(name) {
    if (currentBatch) {
        const prev = document.getElementById('batch-' + currentBatch);
        if (prev) prev.classList.remove('active');
    }
    currentBatch = name;
    currentPage  = 1;
    // Clear search when switching batches
    clearSearch(false);
    const el = document.getElementById('batch-' + name);
    if (el) el.classList.add('active');
    document.getElementById('batch-title').textContent = 'Sběr: ' + name;
    loadImages(name, 1);
}

// ── Search ───────────────────────────────────────────────────
function applySearch() {
    activeSearch.dateFrom = document.getElementById('search-from').value;
    activeSearch.dateTo   = document.getElementById('search-to').value;
    currentPage = 1;
    if (currentBatch) loadImages(currentBatch, 1);
}

function clearSearch(resetInputs = true) {
    activeSearch = { dateFrom: '', dateTo: '' };
    if (resetInputs) {
        document.getElementById('search-from').value = '';
        document.getElementById('search-to').value   = '';
    }
    updateSearchBadge();
    if (currentBatch) loadImages(currentBatch, 1);
}

function updateSearchBadge() {
    const badge = document.getElementById('search-badge');
    const active = activeSearch.dateFrom || activeSearch.dateTo;
    badge.style.display = active ? 'inline-block' : 'none';
    if (active) {
        const parts = [];
        if (activeSearch.dateFrom) parts.push('od ' + activeSearch.dateFrom.replace('T', ' '));
        if (activeSearch.dateTo)   parts.push('do ' + activeSearch.dateTo.replace('T', ' '));
        badge.textContent = 'Filtr: ' + parts.join(' — ');
    }
}

// Allow pressing Enter in datetime inputs to trigger search
document.getElementById('search-from').addEventListener('keydown', e => { if (e.key === 'Enter') applySearch(); });
document.getElementById('search-to').addEventListener('keydown',   e => { if (e.key === 'Enter') applySearch(); });

// ── Images ───────────────────────────────────────────────────
function loadImages(batch, page) {
    const gallery = document.getElementById('archiv-gallery');
    gallery.innerHTML = '<p class="archiv-placeholder">Načítání snímků…</p>';
    document.getElementById('archiv-pagination').innerHTML = '';

    const url = 'archiv_api.php?action=images'
        + '&cisloStroj=' + encodeURIComponent(MACHINE)
        + '&batch='      + encodeURIComponent(batch)
        + '&page='       + page
        + (activeSearch.dateFrom ? '&dateFrom=' + encodeURIComponent(activeSearch.dateFrom) : '')
        + (activeSearch.dateTo   ? '&dateTo='   + encodeURIComponent(activeSearch.dateTo)   : '');

    fetch(url)
        .then(r => r.json())
        .then(data => {
            currentImages = data.images || [];
            currentPage   = data.page;
            const countLabel = data.filtered
                ? `${data.total} snímků (filtrováno)`
                : `${data.total} snímků`;
            document.getElementById('image-count').textContent = countLabel;
            updateSearchBadge();
            renderGallery(currentImages, batch);
            renderPagination(data.pages, data.page, batch);
        })
        .catch(() => {
            gallery.innerHTML =
                '<p class="archiv-placeholder">Chyba při načítání snímků</p>';
        });
}

// Parse date from filenames like 2026_02_28__20_16_00_867269.jpg
// or 1_2026_02_28__20_16_00_867269.jpg
function parseImageDate(filename) {
    const m = filename.match(/(\d{4})_(\d{2})_(\d{2})__(\d{2})_(\d{2})_(\d{2})/);
    if (!m) return filename;
    return `${m[1]}-${m[2]}-${m[3]}  ${m[4]}:${m[5]}:${m[6]}`;
}

function imgUrl(batch, filename) {
    return 'archiv_image.php'
        + '?cisloStroj=' + encodeURIComponent(MACHINE)
        + '&batch='      + encodeURIComponent(batch)
        + '&img='        + encodeURIComponent(filename);
}

function renderGallery(images, batch) {
    const gallery = document.getElementById('archiv-gallery');
    if (!images.length) {
        gallery.innerHTML =
            '<p class="archiv-placeholder">Žádné snímky v tomto sběru</p>';
        return;
    }
    gallery.innerHTML = images.map((img, idx) =>
        `<div class="img-card" onclick="openLightbox(${idx})">
            <div class="gallery-img-wrap">
                <img src="${imgUrl(batch, img)}" alt="${img}" loading="lazy">
            </div>
            <div class="img-date">${parseImageDate(img)}</div>
         </div>`
    ).join('');
}

// ── Pagination ────────────────────────────────────────────────
function renderPagination(pages, cur, batch) {
    const el = document.getElementById('archiv-pagination');
    if (pages <= 1) { el.innerHTML = ''; return; }

    const btn = (p, label, active) =>
        `<button class="page-btn${active ? ' active' : ''}"
                 onclick="loadImages('${batch}', ${p})">${label}</button>`;

    let html = '';
    if (cur > 1) html += btn(cur - 1, '‹');
    for (let p = 1; p <= pages; p++) {
        if (pages > 10 && p > 2 && p < pages - 1 && Math.abs(p - cur) > 2) {
            if (p === 3 || p === pages - 2)
                html += '<span style="padding:5px 3px;color:#aaa">…</span>';
            continue;
        }
        html += btn(p, p, p === cur);
    }
    if (cur < pages) html += btn(cur + 1, '›');
    el.innerHTML = html;
}

// ── Lightbox ──────────────────────────────────────────────────
function openLightbox(idx) {
    lbIndex = idx;
    const img = currentImages[idx];
    document.getElementById('lightbox-img').src = imgUrl(currentBatch, img);
    document.getElementById('lightbox-caption').textContent =
        img + '  (' + (idx + 1) + ' / ' + currentImages.length + ')';
    document.getElementById('lightbox').classList.add('open');
}

function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
    document.getElementById('lightbox-img').src = '';
}

function navigateLightbox(dir) {
    lbIndex = (lbIndex + dir + currentImages.length) % currentImages.length;
    openLightbox(lbIndex);
}

// Click on backdrop to close
document.getElementById('lightbox').addEventListener('click', function (e) {
    if (e.target === this) closeLightbox();
});

// Keyboard navigation
document.addEventListener('keydown', function (e) {
    if (!document.getElementById('lightbox').classList.contains('open')) return;
    if (e.key === 'ArrowLeft')  navigateLightbox(-1);
    if (e.key === 'ArrowRight') navigateLightbox(1);
    if (e.key === 'Escape')     closeLightbox();
});
</script>

</body>
</html>
