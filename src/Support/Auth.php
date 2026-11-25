<?php
declare(strict_types=1);

namespace App\Support;

use App\Config\Database;

final class Auth {
    public static function user(): ?array {
        $id = (int)($_SESSION['user_id'] ?? 0);
        if (!$id) {
            return null;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT customer_id AS user_id, customer_id, username, email, role,
                    groom_name, bride_name, phone, budget_total, budget_spent
             FROM customers WHERE customer_id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function requireLogin(): array {
        $user = self::user();
        if (!$user) {
            Flash::set('error', 'Please sign in to continue.');
            header('Location: ' . View::url('/login'), true, 303);
            exit;
        }
        return $user;
    }

    public static function requireRole(string $role): array {
        $user = self::requireLogin();
        if ($user['role'] !== $role) {
            http_response_code(403);
            exit('You do not have permission to access this page.');
        }
        return $user;
    }

    public static function signIn(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['user_id'];
        unset($_SESSION['_csrf']);
    }

    public static function signOut(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
