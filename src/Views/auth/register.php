<?php use App\Support\Csrf; use App\Support\View; ?>
<p class="eyebrow">Join Urukundo</p>
<h1 class="page-title">Create an account</h1>
<p class="lede">Register to discover venues and manage your wedding plans.</p>

<?php if (!empty($errors)): ?>
  <div class="alert alert-error" role="alert">
    <?php foreach ($errors as $message): ?><p><?= View::e($message) ?></p><?php endforeach; ?>
  </div>
<?php endif; ?>

<form method="post" action="<?= View::e(View::url('/register/process')) ?>" class="form-card">
  <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
  <label>Username<input name="username" required minlength="2" maxlength="60" autocomplete="username" value="<?= View::e($old['username'] ?? '') ?>"></label>
  <label>Email address<input name="email" type="email" required maxlength="190" autocomplete="email" value="<?= View::e($old['email'] ?? '') ?>"></label>
  <div class="form-grid">
    <label>Password<input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
    <label>Confirm password<input name="confirm_password" type="password" required minlength="8" autocomplete="new-password"></label>
  </div>
  <div class="form-grid">
    <label>Groom name<input name="groom_name" maxlength="100" value="<?= View::e($old['groom_name'] ?? '') ?>"></label>
    <label>Bride name<input name="bride_name" maxlength="100" value="<?= View::e($old['bride_name'] ?? '') ?>"></label>
  </div>
  <label>Phone number<input name="phone" maxlength="30" autocomplete="tel" value="<?= View::e($old['phone'] ?? '') ?>"></label>
  <button class="button" type="submit">Register</button>
</form>
<p class="auth-link">Already have an account? <a href="<?= View::e(View::url('/login')) ?>">Login</a></p>
