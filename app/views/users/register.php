<form action="<?= url('/register') ?>" method="POST">
    <?= CSRF::generateInput() ?>

    <input type="email" name="email" id="email" placeholder="Email" value="<?= h($user['email']) ?>" class="<?= isset($errors['email']) ? 'form-error-input' : '' ?>">
    <?php if (isset($errors['email'])): ?>
        <p class="form-error-text"><?= h($errors['email']) ?></p>
    <?php endif; ?>

    <input type="password" name="password" id="password" placeholder="Password" class="<?= isset($errors['password']) ? 'form-error-input' : '' ?>">
    <?php if (isset($errors['password'])): ?>
        <p class="form-error-text"><?= h($errors['password']) ?></p>
    <?php endif; ?>

    <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm password" class="<?= isset($errors['confirm_password']) ? 'form-error-input' : '' ?>">
    <?php if (isset($errors['confirm_password'])): ?>
        <p class="form-error-text"><?= h($errors['confirm_password']) ?></p>
    <?php endif; ?>

    <input type="submit" value="Sign Up">
</form>
