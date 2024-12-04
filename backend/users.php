<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Получение конкретного пользователя
            getUser($conn, $_GET['id']);
        } else {
            // Получение всех пользователей
            getUsers($conn);
        }
        break;
    case 'POST':
        createUser($conn);
        break;
    case 'PUT':
        if (isset($request[0])) {
            updateUser($conn, $request[0]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "User ID is required"]);
        }
        break;
    case 'DELETE':
        if (isset($request[0])) {
            deleteUser($conn, $request[0]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "User ID is required"]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}

function getUsers($conn) {
    try {
        $stmt = $conn->prepare("SELECT users.id, users.full_name, users.login, roles.role_name, users.is_blocked, users.created_at, users.updated_at 
                                FROM users 
                                JOIN roles ON users.role_id = roles.id");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => $e->getMessage()]);
    }
}

function getUser($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "User not found"]);
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => $e->getMessage()]);
    }
}

function createUser($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!validateUserData($data, true)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid input"]);
        return;
    }

    try {
        // Хеширование пароля
        $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, login, password, role_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['full_name'], $data['login'], $hashed_password, $data['role_id']]);
        echo json_encode(["message" => "User created successfully"]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => $e->getMessage()]);
    }
}

function updateUser($conn, $id) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!validateUserData($data, false)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid input"]);
        return;
    }

    try {
        if (isset($data['password']) && !empty($data['password'])) {
            $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, login = ?, password = ?, role_id = ?, is_blocked = ? WHERE id = ?");
            $stmt->execute([$data['full_name'], $data['login'], $hashed_password, $data['role_id'], $data['is_blocked'], $id]);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, login = ?, role_id = ?, is_blocked = ? WHERE id = ?");
            $stmt->execute([$data['full_name'], $data['login'], $data['role_id'], $data['is_blocked'], $id]);
        }
        echo json_encode(["message" => "User updated successfully"]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => $e->getMessage()]);
    }
}

function deleteUser($conn, $id) {
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["message" => "User deleted successfully"]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => $e->getMessage()]);
    }
}

function validateUserData($data, $isNew = true) {
    if (!isset($data['full_name']) || empty($data['full_name'])) return false;
    if (!isset($data['login']) || empty($data['login'])) return false;
    if ($isNew && (!isset($data['password']) || empty($data['password']))) return false;
    if (!isset($data['role_id']) || empty($data['role_id'])) return false;
    if (isset($data['is_blocked']) && !is_numeric($data['is_blocked'])) return false;
    return true;
}
?>
