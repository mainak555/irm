<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/page_renderer.php';

require_auth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SERVER['HTTP_X_FETCH'])) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid request']);
    exit;
}

$body  = (string)file_get_contents('php://input');
$input = json_decode($body, true);

if (!is_array($input) || !isset($input['layout'])) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid layout']);
    exit;
}

$layout    = $input['layout'];
$page_data = [
    'layout_id' => (string)($layout['layout_id'] ?? ''),
    'slots'     => is_array($layout['slots'] ?? null) ? $layout['slots'] : [],
];

ob_start();
render_page($page_data);
$html = ob_get_clean();

echo json_encode(['ok' => true, 'html' => $html]);
