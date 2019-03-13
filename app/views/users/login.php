<form action="<?= url('/login') ?>" method="POST">
    <?= CSRF::generateInput() ?>
    <input type="email" name="email" id="email" placeholder="Email" value="<?= h($user['email']) ?>">
    <input type="password" name="password" id="password" placeholder="Password">
    <input type="submit" value="Sign In">
</form>
