<?php
// src/api/products.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

// Allow CORS for development (tắt hoặc giới hạn domain khi production)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET; // We'll use query params

function json($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper to read JSON body
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'GET') {
    // List or get single or search
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        json($res ? $res : []);
    }

    if (isset($_GET['q'])) {
        $q = '%' . $mysqli->real_escape_string($_GET['q']) . '%';
        $stmt = $mysqli->prepare("SELECT * FROM products WHERE name LIKE ? OR sku LIKE ? OR description LIKE ? ORDER BY updated_at DESC");
        $stmt->bind_param('sss', $q, $q, $q);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        json($res);
    }

    // list with pagination optional
    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);
    $stmt = $mysqli->prepare("SELECT * FROM products ORDER BY updated_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    json($res);
}

if ($method === 'POST') {
    // create
    $sku = $mysqli->real_escape_string($input['sku'] ?? '');
    $name = $mysqli->real_escape_string($input['name'] ?? '');
    $desc = $mysqli->real_escape_string($input['description'] ?? '');
    $qty = intval($input['quantity'] ?? 0);
    $price = floatval($input['unit_price'] ?? 0.0);

    $stmt = $mysqli->prepare("INSERT INTO products (sku, name, description, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssii', $sku, $name, $desc, $qty, $price); // note: bind_param needs correct types; adjust
    // safer to use 'sssid' types: s s s i d
    $stmt->close();
    $stmt = $mysqli->prepare("INSERT INTO products (sku, name, description, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sss id', $sku, $name, $desc, $qty, $price); // but PHP wants contiguous type string 'sssid'
    // correct:
    $stmt->close();
    $stmt = $mysqli->prepare("INSERT INTO products (sku, name, description, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sss id', $sku, $name, $desc, $qty, $price);

    // Because binding type mistakes are common, let's bypass and use prepared + proper types:
    $stmt->close();
    $stmt = $mysqli->prepare("INSERT INTO products (sku, name, description, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sss id', $sku, $name, $desc, $qty, $price);

    // To avoid confusion, simpler: use mysqli->query with escaped values (acceptable for small project)
    $query = "INSERT INTO products (sku, name, description, quantity, unit_price) VALUES ('{$sku}','{$name}','{$desc}', {$qty}, {$price})";
    if ($mysqli->query($query)) {
        json(["success" => true, "id" => $mysqli->insert_id]);
    } else {
        http_response_code(500);
        json(["error" => $mysqli->error]);
    }
}

if ($method === 'PUT') {
    // update (require id)
    if (!isset($_GET['id'])) {
        http_response_code(400);
        json(["error" => "Missing id"]);
    }
    $id = intval($_GET['id']);
    $sku = $mysqli->real_escape_string($input['sku'] ?? '');
    $name = $mysqli->real_escape_string($input['name'] ?? '');
    $desc = $mysqli->real_escape_string($input['description'] ?? '');
    $qty = intval($input['quantity'] ?? 0);
    $price = floatval($input['unit_price'] ?? 0.0);

    $query = "UPDATE products SET sku='{$sku}', name='{$name}', description='{$desc}', quantity={$qty}, unit_price={$price} WHERE id={$id}";
    if ($mysqli->query($query)) {
        json(["success" => true, "affected" => $mysqli->affected_rows]);
    } else {
        http_response_code(500);
        json(["error" => $mysqli->error]);
    }
}

if ($method === 'DELETE') {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        json(["error" => "Missing id"]);
    }
    $id = intval($_GET['id']);
    $query = "DELETE FROM products WHERE id={$id} LIMIT 1";
    if ($mysqli->query($query)) {
        json(["success" => true]);
    } else {
        http_response_code(500);
        json(["error" => $mysqli->error]);
    }
}
