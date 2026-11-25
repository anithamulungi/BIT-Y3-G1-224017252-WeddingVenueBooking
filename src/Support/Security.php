<?php
declare(strict_types=1);

namespace App\Support;

final class Security {
    public static function headers(): void {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}
