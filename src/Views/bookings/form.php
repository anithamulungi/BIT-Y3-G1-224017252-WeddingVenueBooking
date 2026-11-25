<?php use App\Support\Flash; use App\Support\View; ?>
<p class="eyebrow">One step closer</p>
<h1 class="page-title"><?= View::e($title) ?></h1>
<p class="lede">Send a date request for your preferred venue to the venue manager.</p>
<?php if ($errors): ?><ul class="error-list" role="alert"><?php foreach ($errors as $error): ?><li><?= View::e($error) ?></li><?php endforeach; ?></ul><?php endif; ?>
<form method="post" action="<?= View::e(View::url('/booking/save')) ?>" class="form-card">
  <input type="hidden" name="_csrf" value="<?= View::e(\App\Support\Csrf::token()) ?>">
  <?php if ($booking): ?><input type="hidden" name="booking_id" value="<?= (int)$booking['booking_id'] ?>"><?php endif; ?>
  <label>Wedding venue
    <?php $selectedVenue = (int)Flash::old('venue_id', (string)($_GET['venue_id'] ?? ($booking['venue_id'] ?? 0))); ?>
    <select name="venue_id" required>
      <option value="">Choose a venue</option>
      <?php foreach ($venues as $venue): ?>
        <option value="<?= (int)$venue['venue_id'] ?>" <?= $selectedVenue === (int)$venue['venue_id'] ? 'selected' : '' ?> <?= !(int)$venue['available'] ? 'disabled' : '' ?>>
          <?= View::e($venue['name']) ?> · <?= View::e($venue['location']) ?> · <?= number_format((float)$venue['price_per_day']) ?> RWF/day
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Event date<input name="event_date" type="date" min="<?= View::e(date('Y-m-d')) ?>" required value="<?= View::e(Flash::old('event_date', $booking['event_date'] ?? '')) ?>"></label>
  <?php if ($user['role'] === 'manager' && $booking): ?>
    <label>Booking status<select name="status"><?php foreach (['pending','confirmed','cancelled'] as $status): ?><option value="<?= $status ?>" <?= $booking['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></label>
  <?php endif; ?>
  <p class="form-hint">The required deposit is 20% of the selected venue’s daily price. This coursework site does not process mobile money or bank payments.</p>
  <div class="form-actions"><a class="button-ghost" href="<?= View::e(View::url('/bookings')) ?>">Cancel</a><button class="button" type="submit"><?= $booking ? 'Save changes' : 'Request date' ?></button></div>
</form>
