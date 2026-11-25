<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Venue;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Flash;
use App\Support\View;
use DomainException;

final class BookingController {
    public function index(): void {
        $user = Auth::requireLogin();
        if (!in_array($user['role'], ['customer', 'manager'], true)) {
            http_response_code(403);
            exit('You cannot view bookings.');
        }
        View::render('bookings/index', [
            'title' => 'Your celebrations',
            'bookings' => Booking::all($user),
            'user' => $user,
            'success' => Flash::get('success'),
            'error' => Flash::get('error'),
        ]);
    }

    public function form(): void {
        $user = Auth::requireLogin();
        if ($user['role'] !== 'customer' && $user['role'] !== 'manager') {
            http_response_code(403);
            exit('Only customers and venue managers can manage bookings.');
        }
        if ($user['role'] === 'customer' && empty($user['customer_id'])) {
            http_response_code(403);
            exit('A customer profile is required before booking.');
        }
        $id = (int)($_GET['id'] ?? 0);
        if ($user['role'] === 'manager' && !$id) {
            http_response_code(403);
            exit('Venue managers can update existing bookings, but cannot create couple bookings.');
        }
        $booking = $id ? Booking::find($id) : null;
        if ($id && (!$booking || ($user['role'] === 'customer' && (int)$booking['customer_id'] !== (int)$user['customer_id']))) {
            http_response_code(404);
            exit('Booking not found.');
        }
        $venues = Venue::all('', $user['role'] !== 'manager');
        View::render('bookings/form', [
            'title' => $booking ? 'Update your booking' : 'Reserve your date',
            'booking' => $booking,
            'venues' => $venues,
            'user' => $user,
            'errors' => Flash::get('errors', []),
        ]);
        Flash::clearOld();
    }

    public function save(): void {
        $user = Auth::requireLogin();
        $this->postOnly('/bookings');
        Csrf::require();
        $id = (int)($_POST['booking_id'] ?? 0);
        if ($user['role'] === 'manager' && !$id) {
            http_response_code(403);
            exit('Venue managers can update existing bookings, but cannot create couple bookings.');
        }
        $existing = $id ? Booking::find($id) : null;
        if ($id && (!$existing || ($user['role'] === 'customer' && (int)$existing['customer_id'] !== (int)$user['customer_id']))) {
            http_response_code(404);
            exit('Booking not found.');
        }
        if ($user['role'] !== 'customer' && $user['role'] !== 'manager') {
            http_response_code(403);
            exit('You cannot manage bookings.');
        }
        $venueId = filter_var($_POST['venue_id'] ?? null, FILTER_VALIDATE_INT);
        $date = trim((string)($_POST['event_date'] ?? ''));
        $status = $user['role'] === 'manager'
            ? (string)($_POST['status'] ?? 'pending')
            : (string)($existing['status'] ?? 'pending');
        $errors = [];
        if (!$venueId || !Venue::find($venueId)) $errors[] = 'Choose a valid venue.';
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date || $date < date('Y-m-d')) $errors[] = 'Choose a valid date today or later.';
        if (!in_array($status, ['pending', 'confirmed', 'cancelled'], true)) $errors[] = 'Choose a valid booking status.';
        if ($errors) {
            Flash::set('errors', $errors);
            Flash::preserve($_POST);
            $this->redirect($id ? '/booking/form?id=' . $id : '/booking/form');
        }
        try {
            Booking::save([
                'venue_id' => (int)$venueId,
                'customer_id' => $user['role'] === 'customer' ? (int)$user['customer_id'] : (int)($existing['customer_id'] ?? 0),
                'manager_id' => $user['role'] === 'manager'
                    ? (int)$user['user_id']
                    : Booking::managerId(),
                'event_date' => $date,
                'status' => $status,
            ], $id);
        } catch (DomainException $error) {
            Flash::set('errors', [$error->getMessage()]);
            Flash::preserve($_POST);
            $this->redirect($id ? '/booking/form?id=' . $id : '/booking/form');
        }
        Flash::set('success', $id ? 'Booking updated.' : 'Your date request has been received.');
        $this->redirect('/bookings');
    }

    public function delete(): void {
        $user = Auth::requireLogin();
        $this->postOnly('/bookings');
        Csrf::require();
        $id = (int)($_POST['booking_id'] ?? 0);
        $booking = $id ? Booking::find($id) : null;
        if (!$booking || ($user['role'] === 'customer' && (int)$booking['customer_id'] !== (int)$user['customer_id'])) {
            http_response_code(404);
            exit('Booking not found.');
        }
        if ($user['role'] !== 'customer' && $user['role'] !== 'manager') {
            http_response_code(403);
            exit('You cannot manage bookings.');
        }
        Booking::delete($id);
        Flash::set('success', 'Booking removed.');
        $this->redirect('/bookings');
    }

    public function pay(): void {
        $user = Auth::requireRole('customer');
        $this->postOnly('/bookings');
        Csrf::require();
        $id = (int)($_POST['booking_id'] ?? 0);
        $booking = $id ? Booking::find($id) : null;
        if (!$booking || (int)$booking['customer_id'] !== (int)$user['customer_id']) {
            http_response_code(404);
            exit('Booking not found.');
        }
        if (Booking::payDeposit($id, (int)$user['customer_id'])) {
            Flash::set('success', 'Deposit record saved for coursework. No money was transferred.');
        } else {
            Flash::set('error', 'No deposit was recorded. The booking may be cancelled or already paid.');
        }
        $this->redirect('/bookings');
    }

    private function postOnly(string $path): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') $this->redirect($path);
    }

    private function redirect(string $path): never {
        header('Location: ' . View::url($path), true, 303);
        exit;
    }
}
