<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(string $expectedRole = ''): array {
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        header('Location: /kelma/kelma/web/login.php');
        exit;
    }
    if ($expectedRole !== '' && $_SESSION['role'] !== $expectedRole) {
        http_response_code(403);
        die('Access denied: role mismatch.');
    }
    return [
        'user_id' => (int)$_SESSION['user_id'],
        'role'    => $_SESSION['role'],
        'name'    => $_SESSION['full_name'] ?? 'User',
    ];
}

function assert_child_access(PDO $pdo, int $childId): array {
    $user = require_login();
    if ($user['role'] === 'parent') {
        $stmt = $pdo->prepare('SELECT * FROM children WHERE id = ? AND parent_id = ?');
        $stmt->execute([$childId, $user['user_id']]);
        $child = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$child) {
            http_response_code(403);
            die('Access denied: child does not belong to this parent.');
        }
        $_SESSION['active_child_id'] = $childId;
        return $child;
    }

    if ($user['role'] === 'therapist') {
        $stmt = $pdo->prepare("
            SELECT c.* FROM children c
            JOIN therapist_links tl ON tl.child_id = c.id
            JOIN consents co ON co.child_id = c.id AND co.withdrawn_at IS NULL
            WHERE c.id = ? AND tl.therapist_id = ? AND tl.status = 'approved'
        ");
        $stmt->execute([$childId, $user['user_id']]);
        $child = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$child) {
            http_response_code(403);
            die('Access denied: no active guardian consent for this child.');
        }
        return $child;
    }

    http_response_code(403);
    die('Access denied.');
}