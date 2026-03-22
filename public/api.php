<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$path = $_GET['path'] ?? 'dashboard';
$method = $_SERVER['REQUEST_METHOD'];
$repository = repository();
$authRepository = authRepository();

try {
    if ($method === 'POST' && $path === 'login') {
        $payload = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        $user = $authRepository->login((string) ($payload['username'] ?? ''), (string) ($payload['password'] ?? ''));
        loginUser($user);
        echo json_encode(['user' => $user], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST' && $path === 'logout') {
        logoutUser();
        echo json_encode(['status' => 'ok'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $user = currentUser();
    if ($user === null) {
        http_response_code(401);
        echo json_encode(['error' => 'Please login first.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'GET' && $path === 'me') {
        echo json_encode(['user' => $user], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'GET' && $path === 'dashboard') {
        echo json_encode($repository->dashboard(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'GET' && $path === 'products') {
        echo json_encode(['products' => $repository->products()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'GET' && $path === 'ledger') {
        echo json_encode(['ledger' => $repository->ledger()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $payload = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

    if ($method === 'POST' && $path === 'products') {
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Only admin can add or update products and price.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(201);
        echo json_encode(['product' => $repository->createProduct($payload)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST' && $path === 'transactions') {
        http_response_code(201);
        echo json_encode(['transaction' => $repository->createTransaction($payload)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST' && $path === 'expenses') {
        http_response_code(201);
        echo json_encode(['expense' => $repository->createExpense($payload)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $throwable) {
    http_response_code(422);
    echo json_encode([
        'error' => $throwable->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
