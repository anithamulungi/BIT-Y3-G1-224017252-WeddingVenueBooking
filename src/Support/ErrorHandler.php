<?php
declare(strict_types=1);

namespace App\Support;

use Throwable;

final class ErrorHandler {
    public static function register(): void {
        ini_set('display_errors', '0');
        set_exception_handler(static function (Throwable $error): void {
            $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs';
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                error_log('Unable to create application log directory.');
            }
            $record = json_encode([
                'time' => date(DATE_ATOM),
                'message' => $error->getMessage(),
                'file' => $error->getFile(),
                'line' => $error->getLine(),
            ], JSON_UNESCAPED_SLASHES);
            if ($record !== false) {
                error_log($record . PHP_EOL, 3, $directory . DIRECTORY_SEPARATOR . 'app.log');
            }
            http_response_code(500);
            echo '<main><h1>Something went wrong</h1><p>Please try again later.</p></main>';
        });
    }
}
