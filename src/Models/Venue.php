<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDOException;

final class Venue {
    public static function all(string $search = '', bool $availableOnly = true): array {
        $sql = 'SELECT venue_id, name, location, capacity, price_per_day, available, description
                FROM venues WHERE 1=1';
        $params = [];
        if ($availableOnly) {
            $sql .= ' AND available = 1';
        }
        if ($search !== '') {
            $sql .= ' AND (name LIKE :name_search OR location LIKE :location_search OR description LIKE :description_search)';
            $term = '%' . $search . '%';
            $params = ['name_search' => $term, 'location_search' => $term, 'description_search' => $term];
        }
        $sql .= ' ORDER BY available DESC, name';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $stmt = Database::pdo()->prepare('SELECT * FROM venues WHERE venue_id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function save(array $data, int $id = 0): int {
        if ($id > 0) {
            $stmt = Database::pdo()->prepare(
                'UPDATE venues SET name=:name, location=:location, capacity=:capacity,
                 price_per_day=:price, available=:available, description=:description WHERE venue_id=:id'
            );
            $stmt->execute($data + ['id' => $id]);
            return $id;
        }
        $stmt = Database::pdo()->prepare(
            'INSERT INTO venues (name, location, capacity, price_per_day, available, description)
             VALUES (:name, :location, :capacity, :price, :available, :description)'
        );
        $stmt->execute($data);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function delete(int $id): bool {
        try {
            $stmt = Database::pdo()->prepare('DELETE FROM venues WHERE venue_id=:id');
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') {
                return false;
            }
            throw $error;
        }
    }

    public static function stats(): array {
        $row = Database::pdo()->query(
            'SELECT COUNT(*) AS total, SUM(available=1) AS available FROM venues'
        )->fetch();
        return ['total' => (int)$row['total'], 'available' => (int)$row['available']];
    }
}
