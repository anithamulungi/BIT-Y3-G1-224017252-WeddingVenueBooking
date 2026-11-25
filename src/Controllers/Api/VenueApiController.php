<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Venue;

final class VenueApiController {
    public function index(): void {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => true, 'data' => Venue::all(trim((string)($_GET['q'] ?? '')))]);
    }
}
