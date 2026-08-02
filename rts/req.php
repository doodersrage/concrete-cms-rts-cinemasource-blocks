<?php

require __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

if (empty($_POST['req'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing request payload']);
    exit;
}

$config = rts_load_config();
$data = rts_post_xml((string) $_POST['req'], $config);
$parsed = simplexml_load_string($data);

if ($parsed === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid RTS response']);
    exit;
}

echo json_encode($parsed);
