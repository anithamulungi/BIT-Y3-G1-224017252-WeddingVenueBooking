<?php
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\View;
$currentUser = Auth::user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#16324f">
  <title><?= View::e($title ?? 'Urukundo') ?> · Urukundo</title>
  <link rel="stylesheet" href="<?= View::e(View::url('/assets/css/style.css')) ?>">
</head>
<body>
  <header class="site-header">
    <a class="brand" href="<?= View::e(View::url('/')) ?>" aria-label="Urukundo home">
      <img src="<?= View::e(View::url('/assets/images/urukundo-logo.svg')) ?>" alt="Urukundo wedding venue logo">
      <span>URU<span>KUNDO</span></span>
    </a>
    <nav aria-label="Primary">
      <a href="<?= View::e(View::url('/')) ?>">Home</a>
      <a href="<?= View::e(View::url('/venues')) ?>">Venues</a>
      <a href="<?= View::e(View::url('/about')) ?>">About Us</a>
      <?php if ($currentUser && in_array($currentUser['role'], ['customer', 'manager'], true)): ?>
        <a href="<?= View::e(View::url('/bookings')) ?>">My bookings</a>
      <?php endif; ?>
      <?php if (($currentUser['role'] ?? '') === 'customer'): ?>
        <a href="<?= View::e(View::url('/my-weddings')) ?>">My Weddings</a>
      <?php endif; ?>
      <?php if (($currentUser['role'] ?? '') === 'manager'): ?>
        <a href="<?= View::e(View::url('/venue/form')) ?>">Add venue</a>
      <?php endif; ?>
    </nav>
    <div class="nav-account">
      <?php if ($currentUser): ?>
        <span>Welcome, <?= View::e($currentUser['username']) ?></span>
        <form method="post" action="<?= View::e(View::url('/logout')) ?>">
          <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
          <button type="submit">Sign out</button>
        </form>
      <?php else: ?>
        <a class="nav-login" href="<?= View::e(View::url('/login')) ?>">Login</a>
        <a class="nav-register" href="<?= View::e(View::url('/register')) ?>">Register</a>
      <?php endif; ?>
    </div>
  </header>
  <main class="container">
