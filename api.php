<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, ngrok-skip-browser-warning");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "sql108.infinityfree.com"; // MySQL Server from phpMyAdmin header
$user = "if0_43327913";            // Your InfinityFree Username
$pass = "YOUR_VPANEL_PASSWORD";     // Your account password
$dbname = "if0_43327913_reclaimx_db"; // Database Name from header
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database Connection Failed: " . $conn->connect_error]);
    exit();
}

// ... (keep the rest of your api.php code unchanged)
$method = $_SERVER['REQUEST_METHOD'];

// GET: Fetch all items
if ($method === 'GET') {
    $result = $conn->query("SELECT * FROM items ORDER BY created_at DESC");
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode($items);
    exit();
}

// POST: Add new item using Prepared Statements
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['title']) || empty($data['reporter_email'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields"]);
        exit();
    }

    $type = $data['type'] ?? 'lost';
    $title = $data['title'];
    $category = $data['category'] ?? 'General';
    $color = $data['color'] ?? 'N/A';
    $location = $data['location'] ?? 'Unknown';
    $date = $data['date'] ?? date('Y-m-d');
    $reporter_name = !empty($data['reporter_name']) ? $data['reporter_name'] : 'Anonymous';
    $reporter_email = $data['reporter_email'];
    $reporter_phone = !empty($data['reporter_phone']) ? $data['reporter_phone'] : 'N/A';
    $description = $data['description'] ?? '';
    $image_url = $data['image_url'] ?? '';

    $stmt = $conn->prepare("INSERT INTO items (type, title, category, color, location, date, reporter_name, reporter_email, reporter_phone, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $type, $title, $category, $color, $location, $date, $reporter_name, $reporter_email, $reporter_phone, $description, $image_url);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "id" => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => $stmt->error]);
    }
    $stmt->close();
    exit();
}

// PUT: Update status to resolved
if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id']);
    
    $stmt = $conn->prepare("UPDATE items SET status = 'resolved' WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => $stmt->error]);
    }
    $stmt->close();
    exit();
}

// DELETE: Delete record
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id']);

    $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => $stmt->error]);
    }
    $stmt->close();
    exit();
}

$conn->close();
?>