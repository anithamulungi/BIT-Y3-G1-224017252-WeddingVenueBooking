<?php use App\Support\View; ?>
<p class="eyebrow">Every detail, coming together</p>
<h1 class="page-title"><?= $user['role'] === 'manager' ? 'All celebrations' : 'Your celebrations' ?></h1>
<p class="lede">Your date requests, venue details and event support in one place.</p>
<?php if (!empty($success)): ?><p class="alert alert-success" role="status"><?= View::e($success) ?></p><?php endif; ?>
<?php if (!empty($error)): ?><p class="alert alert-error" role="alert"><?= View::e($error) ?></p><?php endif; ?>
<?php if ($user['role'] === 'customer'): ?><p><a class="button" href="<?= View::e(View::url('/booking/form')) ?>">+ Request a date</a></p><?php endif; ?>
<?php if (!$bookings): ?>
  <p class="empty-state">No bookings yet. Your perfect venue could be just around the corner.</p>
  <p><a class="button" href="<?= View::e(View::url('/venues')) ?>">Explore venues</a></p>
<?php else: ?>
  <section class="card-grid">
    <?php foreach ($bookings as $booking): ?>
      <article class="card booking-card">
        <span class="status status-<?= View::e($booking['status']) ?>"><?= View::e($booking['status']) ?></span>
        <h2><?= View::e($booking['venue_name']) ?></h2>
        <p class="venue-location"><?= View::e($booking['location']) ?></p>
        <p><strong>Event date:</strong> <?= View::e(date('F j, Y', strtotime($booking['event_date']))) ?></p>
        <?php if ($user['role'] === 'manager'): ?><p><strong>Customer:</strong> <?= View::e($booking['groom_name']) ?> &amp; <?= View::e($booking['bride_name']) ?></p><?php endif; ?>
        <p><strong>Venue manager:</strong> <?= View::e($booking['manager_name']) ?></p>
        <?php $depositDue = round((float)$booking['price_per_day'] * 0.20, 2); ?>
        <?php if ($booking['status'] === 'confirmed'): ?>
          <p><strong>Deposit due:</strong> <?= number_format($depositDue) ?> RWF <span class="form-hint">(20% of venue day rate)</span></p>
        <?php else: ?>
          <p><strong>Deposit due:</strong> <?= number_format($depositDue) ?> RWF <span class="form-hint">(20% of venue day rate)</span></p>
          <?php if ($user['role'] === 'customer' && $booking['status'] !== 'cancelled'): ?>
            <form method="post" action="<?= View::e(View::url('/booking/pay')) ?>">
              <input type="hidden" name="_csrf" value="<?= View::e(\App\Support\Csrf::token()) ?>">
              <input type="hidden" name="booking_id" value="<?= (int)$booking['booking_id'] ?>">
              <button class="button" type="submit">Record deposit for coursework</button>
            </form>
          <?php endif; ?>
        <?php endif; ?>
        <?php if (in_array($user['role'], ['customer', 'manager'], true)): ?>
          <div class="card-actions">
            <a class="button-ghost" href="<?= View::e(View::url('/booking/form?id=' . (int)$booking['booking_id'])) ?>">Edit</a>
            <form method="post" action="<?= View::e(View::url('/booking/delete')) ?>" onsubmit="return confirm('Remove this booking?');">
              <input type="hidden" name="_csrf" value="<?= View::e(\App\Support\Csrf::token()) ?>">
              <input type="hidden" name="booking_id" value="<?= (int)$booking['booking_id'] ?>">
              <button class="button-danger" type="submit">Delete</button>
            </form>
          </div>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>
