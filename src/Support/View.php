<?php
declare(strict_types=1);

namespace App\Support;

final class View {
    public static function render(string $template, array $data = []): void {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../Views/layouts/header.php';
        require __DIR__ . '/../Views/' . $template . '.php';
        require __DIR__ . '/../Views/layouts/footer.php';
    }

    public static function e(mixed $value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function url(string $path = '/'): string {
        $base = defined('APP_BASE') ? APP_BASE : '';
        $route = parse_url($path, PHP_URL_PATH) ?: '/';
        $route = '/' . ltrim($route, '/');

        if (str_starts_with($route, '/assets/')) {
            return $base . $route;
        }

        $query = [];
        parse_str((string)(parse_url($path, PHP_URL_QUERY) ?? ''), $query);
        unset($query['_route']);
        return $base . '/index.php?' . http_build_query(['_route' => $route] + $query);
    }
}
