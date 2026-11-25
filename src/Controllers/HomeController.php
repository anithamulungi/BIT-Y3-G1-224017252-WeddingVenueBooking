<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Venue;
use App\Support\Auth;
use App\Support\Flash;
use App\Support\View;

final class HomeController {
    public function index(): void {
        $user = Auth::user();
        View::render('home/index', [
            'title' => 'Wedding venues in Rwanda',
            'venues' => array_slice(Venue::all(), 0, 3),
            'user' => $user,
            'bookings' => $user ? Booking::all($user) : [],
            'success' => Flash::get('success'),
            'error' => Flash::get('error'),
        ]);
    }

    public function about(): void {
        View::render('home/about', [
            'title' => 'About Us',
            'user' => Auth::user(),
            'success' => Flash::get('success'),
            'error' => Flash::get('error'),
        ]);
    }
}
