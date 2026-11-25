<?php use App\Support\Flash; use App\Support\View; $v = $venue; ?>
<p class="eyebrow"><?= $v ? 'Refine your listing' : 'Grow the collection' ?></p>
<h1 class="page-title"><?= View::e($title) ?></h1>
<?php if ($errors): ?><ul class="error-list" role="alert"><?php foreach ($errors as $error): ?><li><?= View::e($error) ?></li><?php endforeach; ?></ul><?php endif; ?>
<form method="post" action="<?= View::e(View::url('/venue/save')) ?>" class="form-card">
  <input type="hidden" name="_csrf" value="<?= View::e(\App\Support\Csrf::token()) ?>">
  <?php if ($v): ?><input type="hidden" name="venue_id" value="<?= (int)$v['venue_id'] ?>"><?php endif; ?>
  <label>Venue name<input name="name" required minlength="3" maxlength="120" value="<?= View::e(Flash::old('name', $v['name'] ?? '')) ?>"></label>
  <label>District / location<input name="location" required maxlength="180" placeholder="District, Rwanda" value="<?= View::e(Flash::old('location', $v['location'] ?? '')) ?>"></label>
  <label>Guest capacity<input name="capacity" type="number" min="1" max="65535" required value="<?= View::e(Flash::old('capacity', (string)($v['capacity'] ?? ''))) ?>"></label>
  <label>Price per day (RWF)<input name="price_per_day" type="number" min="1" step="1" required value="<?= View::e(Flash::old('price_per_day', (string)($v['price_per_day'] ?? ''))) ?>"></label>
  <label>Description<textarea name="description" rows="5" minlength="20" maxlength="5000" required><?= View::e(Flash::old('description', $v['description'] ?? '')) ?></textarea></label>
  <label><span><input type="checkbox" name="available" value="1" <?= (int)($v['available'] ?? 1) === 1 ? 'checked' : '' ?>> Available for new bookings</span></label>
  <div class="form-actions"><a class="button-ghost" href="<?= View::e(View::url('/venues')) ?>">Cancel</a><button class="button" type="submit">Save venue</button></div>
</form>
