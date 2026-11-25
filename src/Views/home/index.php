<?php
use App\Support\View;
?>
<?php if (!empty($success)): ?><p class="alert alert-success" role="status"><?= View::e($success) ?></p><?php endif; ?>
<?php if (!empty($error)): ?><p class="alert alert-error" role="alert"><?= View::e($error) ?></p><?php endif; ?>
<section class="hero">
  <p class="eyebrow">Celebrate your wedding in Rwanda</p>
  <h1>Find your place to celebrate in Rwanda.</h1>
  <p class="lede">Explore wedding venues across Rwanda, compare prices in Rwandan francs, and request a date for your family celebration.</p>
  <div class="hero-actions">
    <a class="button button-light" href="<?= View::e(View::url('/venues')) ?>">Explore venues</a>
    <?php if (!$user): ?><a class="button-ghost hero-ghost" href="<?= View::e(View::url('/login')) ?>">Sign in to plan</a><?php endif; ?>
    <?php if ($user && $user['role'] === 'customer'): ?>
      <a class="button button-light" href="<?= View::e(View::url('/my-weddings')) ?>">Open My Weddings</a>
    <?php elseif ($user && $user['role'] === 'manager'): ?>
      <a class="button button-light" href="<?= View::e(View::url('/bookings')) ?>">View bookings</a>
    <?php endif; ?>
  </div>
  <div class="trust-row"><span>Rwandan venues</span><span>·</span><span>Secure date requests</span><span>·</span><span>Venue manager support</span></div>
</section>

<section>
  <div class="section-heading">
    <div><p class="eyebrow">Wedding places across Rwanda</p><h2>Featured venues</h2></div>
    <a class="button-ghost" href="<?= View::e(View::url('/venues')) ?>">View all venues</a>
  </div>
  <?php if (!$venues): ?>
    <p class="empty-state">Our venue collection is being prepared. Please check back soon.</p>
  <?php else: ?>
    <div class="card-grid">
      <?php foreach ($venues as $venue): ?>
        <article class="card venue-card">
          <span class="eyebrow">Wedding venue</span>
          <h2><?= View::e($venue['name']) ?></h2>
          <p class="venue-location"><?= View::e($venue['location']) ?></p>
          <p class="venue-description"><?= View::e($venue['description']) ?></p>
          <p class="meta"><?= (int)$venue['capacity'] ?> guests · From <?= number_format((float)$venue['price_per_day']) ?> RWF / day</p>
          <a class="button" href="<?= View::e(View::url($user && !in_array($user['role'], ['customer'], true) ? '/venues' : '/booking/form?venue_id=' . (int)$venue['venue_id'])) ?>"><?= $user && !in_array($user['role'], ['customer'], true) ? 'Explore venue' : 'Reserve this venue' ?></a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
