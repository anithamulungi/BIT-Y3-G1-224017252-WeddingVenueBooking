<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;

final class Wedding {
    public static function dashboard(int $customerId): array {
        $bookingStmt = Database::pdo()->prepare(
            'SELECT b.*, v.name AS venue_name, v.location, v.capacity, v.price_per_day
             FROM bookings b JOIN venues v ON v.venue_id=b.venue_id
             WHERE b.customer_id=:customer_id AND b.status<>\'cancelled\'
             ORDER BY b.event_date ASC LIMIT 1'
        );
        $bookingStmt->execute(['customer_id' => $customerId]);
        $booking = $bookingStmt->fetch() ?: null;

        $customerStmt = Database::pdo()->prepare(
            'SELECT groom_name, bride_name, phone, budget_total, budget_spent
             FROM customers WHERE customer_id=:customer_id'
        );
        $customerStmt->execute(['customer_id' => $customerId]);
        $customer = $customerStmt->fetch() ?: [];

        return [
            'couple' => $customer,
            'booking' => $booking,
        ];
    }

    public static function updateBudget(int $customerId, float $total, float $spent): void {
        $stmt = Database::pdo()->prepare(
            'UPDATE customers SET budget_total=:total, budget_spent=:spent WHERE customer_id=:customer_id'
        );
        $stmt->execute(['total' => $total, 'spent' => $spent, 'customer_id' => $customerId]);
    }
}
