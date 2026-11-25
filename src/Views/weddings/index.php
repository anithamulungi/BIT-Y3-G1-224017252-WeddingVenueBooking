<?php
use App\Support\Csrf;
use App\Support\View;

$couple = $dashboard['couple'];
$booking = $dashboard['booking'];
$total = (float)($couple['budget_total'] ?? 0);
$spent = (float)($couple['budget_spent'] ?? 0);
$remaining = max(0, $total - $spent);
$budgetPercent = $total > 0 ? min(100, round(($spent / $total) * 100)) : 0;
?>
<p class="eyebrow">Wedding planning</p>
<h1 class="page-title">My Weddings</h1>
<p class="lede">Plan the details, venue and budget in one place.</p>

<?php if (!empty($success)): ?><p class="alert alert-success" role="status"><?= View::e($success) ?></p><?php endif; ?>
<?php if (!empty($error)): ?><p class="alert alert-error" role="alert"><?= View::e($error) ?></p><?php endif; ?>

<?php if ($booking): ?>
  <?php $days = max(0, (new DateTimeImmutable($booking['event_date']))->diff(new DateTimeImmutable(date('Y-m-d')))->days); ?>
  <section class="wedding-hero">
    <div>
      <p class="eyebrow">The celebration countdown</p>
      <h2><?= $days > 0 ? (int)$days : 0 ?> days to go</h2>
      <p><?= View::e(date('l, F j, Y', strtotime($booking['event_date']))) ?></p>
    </div>
    <div class="wedding-couple">
      <strong><?= View::e($couple['groom_name']) ?></strong>
      <span>&amp;</span>
      <strong><?= View::e($couple['bride_name']) ?></strong>
    </div>
  </section>
<?php endif; ?>

<section class="dashboard-grid">
  <article class="dashboard-card">
    <p class="eyebrow">Wedding details</p>
    <h2><?= View::e($couple['groom_name'] . ' & ' . $couple['bride_name']) ?></h2>
    <?php if ($booking): ?>
      <dl class="detail-list">
        <div><dt>Event date</dt><dd><?= View::e(date('F j, Y', strtotime($booking['event_date']))) ?></dd></div>
        <div><dt>Venue</dt><dd><?= View::e($booking['venue_name']) ?></dd></div>
        <div><dt>Location</dt><dd><?= View::e($booking['location']) ?></dd></div>
        <div><dt>Status</dt><dd><span class="status status-<?= View::e($booking['status']) ?>"><?= View::e($booking['status']) ?></span></dd></div>
      </dl>
    <?php else: ?>
      <p class="muted-text">No active venue booking yet.</p>
      <a class="button" href="<?= View::e(View::url('/booking/form')) ?>">Request a wedding date</a>
    <?php endif; ?>
  </article>

  <article class="dashboard-card">
    <p class="eyebrow">Budget</p>
    <h2><?= number_format($total) ?> RWF</h2>
    <div class="progress-track" aria-label="<?= $budgetPercent ?> percent of budget spent">
      <div class="progress-bar" style="width:<?= $budgetPercent ?>%"></div>
    </div>
    <div class="budget-values">
      <span><strong><?= number_format($spent) ?> RWF</strong><small>Spent</small></span>
      <span><strong><?= number_format($remaining) ?> RWF</strong><small>Remaining</small></span>
    </div>
  </article>
</section>

<section class="management-grid">
  <div class="dashboard-card">
    <div class="section-heading">
      <div>
        <p class="eyebrow">Planning settings</p>
        <h2>Manage budget</h2>
      </div>
    </div>
    <form method="post" action="<?= View::e(View::url('/my-weddings/budget')) ?>" class="compact-form">
      <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
      <div class="budget-form-grid">
        <label>Budget total (RWF)<input name="budget_total" type="number" min="0" step="1" required value="<?= (int)$total ?>"></label>
        <label>Budget spent (RWF)<input name="budget_spent" type="number" min="0" step="1" required value="<?= (int)$spent ?>"></label>
      </div>
      <button class="button" type="submit">Save budget</button>
    </form>
  </div>
</section>
