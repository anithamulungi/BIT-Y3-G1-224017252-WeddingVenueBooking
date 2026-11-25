<?php
declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();
require __DIR__ . '/../src/Support/autoload.php';

\App\Support\ErrorHandler::register();
\App\Support\Security::headers();

$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
define('APP_BASE', $scriptDirectory === '/' ? '' : rtrim($scriptDirectory, '/'));
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (
    PHP_SAPI === 'cli-server'
    && $uri !== '/'
    && is_file(__DIR__ . $uri)
    && strtolower(pathinfo($uri, PATHINFO_EXTENSION)) !== 'php'
) {
    return false;
}
$path = '';
if (isset($_GET['_route'])) {
    $path = '/' . trim((string)$_GET['_route'], '/');
    if ($path === '') {
        $path = '/';
    }
} else {
    $path = $uri;
    if (APP_BASE !== '' && str_starts_with($path, APP_BASE)) {
        $path = substr($path, strlen(APP_BASE));
    }
    $path = '/' . trim($path, '/');
    if ($path === '//') $path = '/';
}

match (true) {
    $path === '/' => (new \App\Controllers\HomeController())->index(),
    $path === '/about' => (new \App\Controllers\HomeController())->about(),
    $path === '/venues' => (new \App\Controllers\VenueController())->index(),
    $path === '/venue/form' => (new \App\Controllers\VenueController())->form(),
    $path === '/venue/save' => (new \App\Controllers\VenueController())->save(),
    $path === '/venue/delete' => (new \App\Controllers\VenueController())->delete(),
    $path === '/bookings' => (new \App\Controllers\BookingController())->index(),
    $path === '/booking/form' => (new \App\Controllers\BookingController())->form(),
    $path === '/booking/save' => (new \App\Controllers\BookingController())->save(),
    $path === '/booking/delete' => (new \App\Controllers\BookingController())->delete(),
    $path === '/booking/pay' => (new \App\Controllers\BookingController())->pay(),
    $path === '/login' => (new \App\Controllers\AuthController())->login(),
    $path === '/login/process' => (new \App\Controllers\AuthController())->processLogin(),
    $path === '/register' => (new \App\Controllers\AuthController())->register(),
    $path === '/register/process' => (new \App\Controllers\AuthController())->processRegister(),
    $path === '/logout' => (new \App\Controllers\AuthController())->logout(),
    $path === '/my-weddings' => (new \App\Controllers\WeddingController())->index(),
    $path === '/my-weddings/budget' => (new \App\Controllers\WeddingController())->budget(),
    $path === '/api/venues' => (new \App\Controllers\Api\VenueApiController())->index(),
    default => (static function (): void {
        http_response_code(404);
        echo '<main><h1>Page not found</h1><p>The page you requested does not exist.</p><a href="' . \App\Support\View::e(\App\Support\View::url('/')) . '">Back to home</a></main>';
    })(),
};
