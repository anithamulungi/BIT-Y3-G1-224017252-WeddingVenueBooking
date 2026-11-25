<?php use App\Support\View; ?>
<p class="eyebrow">Good to see you again</p>
<h1 class="page-title">Welcome back</h1>
<p class="lede">Sign in to pick up where your plans left off.</p>
<?php if (!empty($error)): ?><p class="alert alert-error" role="alert"><?= View::e($error) ?></p><?php endif; ?>
<form method="post" action="<?= View::e(View::url('/login/process')) ?>" class="form-card">
  <input type="hidden" name="_csrf" value="<?= View::e(\App\Support\Csrf::token()) ?>">
  <label>Email address<input name="email" type="email" autocomplete="email" required maxlength="190" value="<?= View::e($old) ?>"></label>
  <label>Password<input name="password" type="password" autocomplete="current-password" required></label>
  <button class="button" type="submit">Sign in</button>
</form>
<p class="auth-link">New to Urukundo? <a href="<?= View::e(View::url('/register')) ?>">Create an account</a></p>
