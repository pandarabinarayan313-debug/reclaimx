<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, ngrok-skip-browser-warning");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Local XAMPP MySQL Settings
$host   = "localhost";
$user   = "root";      // Default XAMPP username
$pass   = "";          // Default XAMPP password (leave blank)
$dbname = "reclaimx_db";
$conn   = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database Connection Failed: " . $conn->connect_error]);
    exit();
}

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

// POST: Add new item (supports multipart/form-data with image upload)
if ($method === 'POST') {
    $data = $_POST;
    if (empty($data)) {
        $data = json_decode(file_get_contents("php://input"), true) ?? [];
    }
    
    if (empty($data['title']) || empty($data['reporter_email'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields"]);
        exit();
    }

    $type = $data['type'] ?? 'lost';
    $title = $data['title'];
    $item = !empty($data['item']) ? $data['item'] : ($data['category'] ?? 'General');
    $color = $data['color'] ?? 'N/A';
    $location = $data['location'] ?? 'Unknown';
    $date = $data['date'] ?? date('Y-m-d');
    $reporter_name = !empty($data['reporter_name']) ? $data['reporter_name'] : 'Anonymous';
    $reporter_email = $data['reporter_email'];
    $reporter_phone = !empty($data['reporter_phone']) ? $data['reporter_phone'] : 'N/A';
    $description = $data['description'] ?? '';
    $image_url = '';

    // Handle File Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $image_url = $destPath;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO items (type, title, item, color, location, date, reporter_name, reporter_email, reporter_phone, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $type, $title, $item, $color, $location, $date, $reporter_name, $reporter_email, $reporter_phone, $description, $image_url);

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

// DELETE: Delete record and attached file
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id']);

    // Check for existing image file and remove it
    $stmtSelect = $conn->prepare("SELECT image_url FROM items WHERE id = ?");
    $stmtSelect->bind_param("i", $id);
    $stmtSelect->execute();
    $res = $stmtSelect->get_result()->fetch_assoc();
    if ($res && !empty($res['image_url']) && file_exists($res['image_url'])) {
        unlink($res['image_url']);
    }
    $stmtSelect->close();

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