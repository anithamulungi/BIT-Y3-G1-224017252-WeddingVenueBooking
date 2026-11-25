<?php use App\Support\View; ?>
<p class="eyebrow">Make it yours</p>
<h1 class="page-title">Explore venues</h1>
<p class="lede">Explore wedding venues in Kigali, Rubavu, Huye, Musanze and other parts of Rwanda.</p>
<?php if (!empty($success)): ?><p class="alert alert-success" role="status"><?= View::e($success) ?></p><?php endif; ?>
<?php if (!empty($error)): ?><p class="alert alert-error" role="alert"><?= View::e($error) ?></p><?php endif; ?>
<?php if ($manager): ?><p><a class="button" href="<?= View::e(View::url('/venue/form')) ?>">+ Add venue</a></p><?php endif; ?>
<form class="toolbar" method="get" action="<?= View::e(View::url('/venues')) ?>">
  <input id="venue-search" type="search" name="q" value="<?= View::e($search) ?>" placeholder="Search by venue or location" aria-label="Search venues">
  <button class="button" type="submit">Search</button>
</form>
<p class="count">Showing <strong id="venue-count"><?= count($venues) ?></strong> venue(s)</p>
<p id="async-status" class="alert alert-error" aria-live="polite"></p>
<?php if (!$venues): ?><p class="empty-state">No venues match your search.</p><?php endif; ?>
<section class="card-grid" id="venue-grid">
  <?php foreach ($venues as $venue): ?>
    <article class="card venue-card">
      <span class="eyebrow"><?= (int)$venue['available'] ? 'Available' : 'Not available' ?></span>
      <h2><?= View::e($venue['name']) ?></h2>
      <p class="venue-location"><?= View::e($venue['location']) ?></p>
      <p class="venue-description"><?= View::e($venue['description']) ?></p>
      <p class="meta"><span><?= (int)$venue['capacity'] ?> guests</span> · <span>From <?= number_format((float)$venue['price_per_day']) ?> RWF / day</span></p>
      <?php if ((int)$venue['available'] && $canBook): ?><a class="button" href="<?= View::e(View::url('/booking/form?venue_id=' . (int)$venue['venue_id'])) ?>">Reserve this venue</a><?php endif; ?>
      <?php if ($manager): ?>
        <div class="card-actions">
          <a class="button-ghost" href="<?= View::e(View::url('/venue/form?id=' . (int)$venue['venue_id'])) ?>">Edit</a>
          <form method="post" action="<?= View::e(View::url('/venue/delete')) ?>" onsubmit="return confirm('Delete this venue?');">
            <input type="hidden" name="_csrf" value="<?= View::e(\App\Support\Csrf::token()) ?>">
            <input type="hidden" name="venue_id" value="<?= (int)$venue['venue_id'] ?>">
            <button class="button-danger" type="submit">Delete</button>
          </form>
        </div>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</section>
<?php if ($canBook): ?>
  <script>
    window.WEDDING_API = <?= json_encode(View::url('/api/venues'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window.WEDDING_BOOKING = <?= json_encode(View::url('/booking/form'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  </script>
  <script src="<?= View::e(View::url('/assets/js/venues-live.js')) ?>" defer></script>
<?php endif; ?>
