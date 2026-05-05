<?php
header('Content-Type: application/json');

// --- Input validation ---
$cisloStroj = isset($_GET['cisloStroj']) ? $_GET['cisloStroj'] : '1';
if (!preg_match('/^\d+$/', $cisloStroj)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid machine number']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'batches';
$baseArchiv = '/media/archiv/yolo/' . $cisloStroj . '/sber';

if (!is_dir($baseArchiv)) {
    echo json_encode(['error' => 'Archive directory not found', 'path' => $baseArchiv, 'batches' => []]);
    exit;
}

// --- Action: list batches ---
if ($action === 'batches') {
    $entries = array_diff(scandir($baseArchiv), ['.', '..']);
    $batchList = [];
    foreach ($entries as $entry) {
        // Only allow alphanumeric/underscore batch names (prevents path traversal)
        if (is_dir($baseArchiv . '/' . $entry) && preg_match('/^\w+$/', $entry)) {
            $images = glob($baseArchiv . '/' . $entry . '/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
            $count = $images ? count($images) : 0;
            $batchList[] = ['name' => $entry, 'count' => $count];
        }
    }
    // Sort numerically descending — newest/largest ID first
    usort($batchList, function ($a, $b) {
        return intval($b['name']) <=> intval($a['name']);
    });
    echo json_encode(['batches' => $batchList, 'machine' => $cisloStroj]);
    exit;
}

// --- Action: list images in a batch (paginated) ---
if ($action === 'images') {
    $batch = isset($_GET['batch']) ? $_GET['batch'] : '';
    if (!preg_match('/^\w+$/', $batch)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid batch name']);
        exit;
    }

    $batchDir = $baseArchiv . '/' . $batch;
    // Verify resolved path stays inside allowed base (defence against symlink attacks)
    $realBatch = realpath($batchDir);
    $realBase  = realpath($baseArchiv);
    if ($realBatch === false || $realBase === false || strpos($realBatch, $realBase . '/') !== 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    $perPage = 50;
    $page    = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

    // --- Optional date range filter (format: YYYY-MM-DDTHH:MM or YYYY-MM-DD) ---
    $dateFrom = isset($_GET['dateFrom']) ? trim($_GET['dateFrom']) : '';
    $dateTo   = isset($_GET['dateTo'])   ? trim($_GET['dateTo'])   : '';

    // Parse a datetime string into a Unix timestamp; accept YYYY-MM-DDTHH:MM or YYYY-MM-DD
    $tsFrom = null;
    $tsTo   = null;
    if ($dateFrom !== '') {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $dateFrom)
           ?: DateTime::createFromFormat('Y-m-d', $dateFrom);
        if ($dt) $tsFrom = $dt->getTimestamp();
    }
    if ($dateTo !== '') {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $dateTo)
           ?: DateTime::createFromFormat('Y-m-d', $dateTo);
        if ($dt) {
            // If only a date (no time) was supplied, extend to end of that day
            if (strlen($dateTo) <= 10) $dt->setTime(23, 59, 59);
            $tsTo = $dt->getTimestamp();
        }
    }

    // Extract timestamp from filename: 2026_02_28__20_16_00_… or 1_2026_02_28__20_16_00_…
    function filenameToTimestamp($basename) {
        if (preg_match('/(\d{4})_(\d{2})_(\d{2})__(\d{2})_(\d{2})_(\d{2})/', $basename, $m)) {
            $dt = DateTime::createFromFormat('Y m d H i s', "{$m[1]} {$m[2]} {$m[3]} {$m[4]} {$m[5]} {$m[6]}");
            return $dt ? $dt->getTimestamp() : null;
        }
        return null;
    }

    $images = glob($batchDir . '/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
    if ($images === false) $images = [];
    rsort($images);

    // Apply date filter if requested
    if ($tsFrom !== null || $tsTo !== null) {
        $images = array_values(array_filter($images, function ($path) use ($tsFrom, $tsTo) {
            $ts = filenameToTimestamp(basename($path));
            if ($ts === null) return false;
            if ($tsFrom !== null && $ts < $tsFrom) return false;
            if ($tsTo   !== null && $ts > $tsTo)   return false;
            return true;
        }));
    }

    $totalAll = isset($_GET['dateFrom']) || isset($_GET['dateTo'])
        ? null   // filtered — no meaningful "total without filter" to return separately
        : count($images);

    $total  = count($images);
    $pages  = $total > 0 ? (int) ceil($total / $perPage) : 1;
    $page   = min($page, $pages);
    $offset = ($page - 1) * $perPage;
    $slice  = array_slice($images, $offset, $perPage);

    echo json_encode([
        'images'    => array_values(array_map('basename', $slice)),
        'total'     => $total,
        'page'      => $page,
        'pages'     => $pages,
        'per_page'  => $perPage,
        'batch'     => $batch,
        'machine'   => $cisloStroj,
        'filtered'  => ($tsFrom !== null || $tsTo !== null),
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
