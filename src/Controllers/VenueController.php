<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Venue;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Flash;
use App\Support\View;

final class VenueController {
    public function index(): void {
        $search = trim((string)($_GET['q'] ?? ''));
        $user = Auth::user();
        $manager = ($user['role'] ?? '') === 'manager';
        View::render('venues/index', [
            'title' => 'Explore venues',
            'venues' => Venue::all($search, !$manager),
            'search' => $search,
            'manager' => $manager,
            'canBook' => !$user || $user['role'] === 'customer',
            'success' => Flash::get('success'),
            'error' => Flash::get('error'),
        ]);
    }

    public function form(): void {
        Auth::requireRole('manager');
        $id = (int)($_GET['id'] ?? 0);
        $venue = $id ? Venue::find($id) : null;
        if ($id && !$venue) {
            http_response_code(404);
            exit('Venue not found.');
        }
        View::render('venues/form', [
            'title' => $venue ? 'Edit venue' : 'Add a venue',
            'venue' => $venue,
            'errors' => Flash::get('errors', []),
        ]);
        Flash::clearOld();
    }

    public function save(): void {
        Auth::requireRole('manager');
        $this->postOnly('/venues');
        Csrf::require();
        $id = (int)($_POST['venue_id'] ?? 0);
        $data = [
            'name' => trim((string)($_POST['name'] ?? '')),
            'location' => trim((string)($_POST['location'] ?? '')),
            'capacity' => filter_var($_POST['capacity'] ?? null, FILTER_VALIDATE_INT),
            'price' => filter_var($_POST['price_per_day'] ?? null, FILTER_VALIDATE_FLOAT),
            'available' => isset($_POST['available']) ? 1 : 0,
            'description' => trim((string)($_POST['description'] ?? '')),
        ];
        $errors = [];
        if (mb_strlen($data['name']) < 3 || mb_strlen($data['name']) > 120) $errors[] = 'Venue name must be 3–120 characters.';
        if (mb_strlen($data['location']) < 3 || mb_strlen($data['location']) > 180) $errors[] = 'Enter a venue location.';
        if (!$data['capacity'] || $data['capacity'] < 1 || $data['capacity'] > 65535) $errors[] = 'Capacity must be a positive whole number.';
        if (
            $data['price'] === false
            || $data['price'] <= 0
            || $data['price'] > 99999999
            || floor((float)$data['price']) !== (float)$data['price']
        ) {
            $errors[] = 'Enter a valid whole-number daily price in Rwandan francs.';
        }
        if (mb_strlen($data['description']) < 20 || mb_strlen($data['description']) > 5000) $errors[] = 'Description must be 20–5,000 characters.';
        if ($errors) {
            Flash::set('errors', $errors);
            Flash::preserve($_POST);
            $this->redirect($id ? '/venue/form?id=' . $id : '/venue/form');
        }
        Venue::save($data, $id);
        Flash::set('success', $id ? 'Venue updated.' : 'Venue added to the collection.');
        $this->redirect('/venues');
    }

    public function delete(): void {
        Auth::requireRole('manager');
        $this->postOnly('/venues');
        Csrf::require();
        $id = (int)($_POST['venue_id'] ?? 0);
        if ($id > 0 && Venue::delete($id)) {
            Flash::set('success', 'Venue deleted.');
        } else {
            Flash::set('error', 'This venue cannot be deleted while it has bookings.');
        }
        $this->redirect('/venues');
    }

    private function postOnly(string $path): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') $this->redirect($path);
    }

    private function redirect(string $path): never {
        header('Location: ' . View::url($path), true, 303);
        exit;
    }
}
