<?php
declare(strict_types=1);

namespace App\Support;

final class Csrf {
    public static function token(): string {
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['_csrf'];
    }

    public static function require(): void {
        $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $expected = $_SESSION['_csrf'] ?? '';
        if (!is_string($sent) || !is_string($expected) || $expected === '' || !hash_equals($expected, $sent)) {
            http_response_code(419);
            exit('Your session expired. Refresh the page and try again.');
        }
    }
}
