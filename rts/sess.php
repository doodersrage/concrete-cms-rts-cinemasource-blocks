<?php

session_start();

require __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

switch ($_POST['method'] ?? '') {
    case 'set':
        $_SESSION['data'] = $_POST['data'] ?? null;
        echo json_encode(['status' => 'saved']);
        break;
    case 'get':
        if (is_array($_SESSION['data'] ?? null)) {
            $_SESSION['data'] = json_encode($_SESSION['data']);
        }
        echo $_SESSION['data'] ?? 'null';
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid method']);
        break;
}
