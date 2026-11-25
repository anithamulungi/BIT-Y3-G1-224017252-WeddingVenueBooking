<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
final class Booking {
    public static function managerId(): int {
        $row = Database::pdo()->query(
            "SELECT customer_id FROM customers WHERE role='manager' ORDER BY customer_id LIMIT 1"
        )->fetch();
        if (!$row) {
            throw new \DomainException('No venue manager account is available.');
        }
        return (int)$row['customer_id'];
    }

    public static function all(array $user): array {
        $sql = 'SELECT b.*, v.name AS venue_name, v.location, v.price_per_day,
                       c.groom_name, c.bride_name, m.username AS manager_name
                FROM bookings b
                JOIN venues v ON v.venue_id=b.venue_id
                JOIN customers c ON c.customer_id=b.customer_id
                JOIN customers m ON m.customer_id=b.manager_id';
        $params = [];
        if ($user['role'] === 'customer') {
            $sql .= ' WHERE b.customer_id=:customer_id';
            $params['customer_id'] = (int)$user['customer_id'];
        }
        $sql .= ' ORDER BY b.event_date DESC, b.booking_id DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $stmt = Database::pdo()->prepare('SELECT * FROM bookings WHERE booking_id=:id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function save(array $data, int $id = 0): int {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT available, price_per_day FROM venues WHERE venue_id=:venue_id FOR UPDATE');
            $lock->execute(['venue_id' => $data['venue_id']]);
            $venue = $lock->fetch();
            $currentVenueId = 0;
            $currentEventDate = '';
            if ($id > 0) {
                $current = $pdo->prepare('SELECT venue_id, event_date FROM bookings WHERE booking_id=:id FOR UPDATE');
                $current->execute(['id' => $id]);
                $currentBooking = $current->fetch();
                $currentVenueId = (int)($currentBooking['venue_id'] ?? 0);
                $currentEventDate = (string)($currentBooking['event_date'] ?? '');
            }
            if (!$venue || (!(int)$venue['available'] && (
                $currentVenueId !== (int)$data['venue_id'] || $currentEventDate !== $data['event_date']
            ))) {
                throw new \DomainException('This venue is not currently available.');
            }
            $conflict = $pdo->prepare(
                "SELECT booking_id FROM bookings
                 WHERE venue_id=:venue_id AND event_date=:event_date AND status<>'cancelled'
                   AND booking_id<>:booking_id LIMIT 1"
            );
            $conflict->execute([
                'venue_id' => $data['venue_id'],
                'event_date' => $data['event_date'],
                'booking_id' => $id,
            ]);
            if ($conflict->fetch()) {
                throw new \DomainException('That venue already has an active booking on this date.');
            }
            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE bookings SET venue_id=:venue_id, customer_id=:customer_id, manager_id=:manager_id,
                     event_date=:event_date, status=:status WHERE booking_id=:id'
                );
                $stmt->execute($data + ['id' => $id]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO bookings (venue_id,customer_id,manager_id,event_date,status)
                     VALUES (:venue_id,:customer_id,:manager_id,:event_date,:status)'
                );
                $stmt->execute($data);
                $id = (int)$pdo->lastInsertId();
            }
            $pdo->commit();
            return $id;
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    public static function delete(int $id): void {
        $stmt = Database::pdo()->prepare('DELETE FROM bookings WHERE booking_id=:id');
        $stmt->execute(['id' => $id]);
    }

    public static function payDeposit(int $id, int $customerId): bool {
        $stmt = Database::pdo()->prepare(
            "INSERT INTO payments (booking_id, amount, payment_method, status, paid_at)
             SELECT b.booking_id, ROUND(v.price_per_day * 0.20, 2), 'Mobile Money', 'completed', NOW()
             FROM bookings b JOIN venues v ON v.venue_id=b.venue_id
             WHERE b.booking_id=:id AND b.customer_id=:customer_id
               AND b.status<>'cancelled'
               AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.booking_id=b.booking_id AND p.status='completed')"
        );
        $stmt->execute(['id' => $id, 'customer_id' => $customerId]);
        return $stmt->rowCount() > 0;
    }
}
