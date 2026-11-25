<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Wedding;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Flash;
use App\Support\View;

final class WeddingController {
    public function index(): void {
        $user = Auth::requireRole('customer');
        if (empty($user['customer_id'])) {
            http_response_code(403);
            exit('A customer profile is required.');
        }
        View::render('weddings/index', [
            'title' => 'My Weddings',
            'user' => $user,
            'dashboard' => Wedding::dashboard((int)$user['customer_id']),
            'success' => Flash::get('success'),
            'error' => Flash::get('error'),
        ]);
    }

    public function budget(): void {
        $user = Auth::requireRole('customer');
        $this->postOnly();
        Csrf::require();
        $total = filter_var($_POST['budget_total'] ?? null, FILTER_VALIDATE_FLOAT);
        $spent = filter_var($_POST['budget_spent'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($total === false || $spent === false || $total < 0 || $spent < 0 || $spent > $total) {
            Flash::set('error', 'Enter a valid budget and ensure spending does not exceed the total.');
            $this->redirect();
        }
        Wedding::updateBudget((int)$user['customer_id'], (float)$total, (float)$spent);
        Flash::set('success', 'Wedding budget updated.');
        $this->redirect();
    }

    private function postOnly(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect();
        }
    }

    private function redirect(): never {
        header('Location: ' . View::url('/my-weddings'), true, 303);
        exit;
    }
}
