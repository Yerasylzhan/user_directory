<?php
header('Content-Type: application/json');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

$data = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        try {
            $stmt = $pdo->prepare("SELECT users.id, users.full_name, users.login, roles.name as role, users.is_blocked
                                   FROM users
                                   LEFT JOIN roles ON users.role_id = roles.id");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($users);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка при получении пользователей: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        try {
            $full_name = $data['full_name'];
            $login = $data['login'];
            $password = password_hash($data['password'], PASSWORD_BCRYPT);
            $role_id = $data['role_id'];

            $stmt = $pdo->prepare("INSERT INTO users (full_name, login, password, role_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $login, $password, $role_id]);

            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка при создании пользователя: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        try {
            $id = $data['id'];
            $full_name = $data['full_name'];
            $login = $data['login'];
            $role_id = $data['role_id'];
            $is_blocked = isset($data['is_blocked']) ? (int)$data['is_blocked'] : 0;

            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, login = ?, role_id = ?, is_blocked = ? WHERE id = ?");
            $stmt->execute([$full_name, $login, $role_id, $is_blocked, $id]);

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка при обновлении пользователя: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        try {
            $id = $_GET['id'];
            $stmt = $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка при блокировке пользователя: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Метод не поддерживается']);
        break;
}
?>
