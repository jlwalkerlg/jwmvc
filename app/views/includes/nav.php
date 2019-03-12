<nav class="site-nav">
    <ul class="site-nav-list">

        <li class="site-nav-li"><a href="<?= url('/') ?>" class="site-nav-link">Home</a></li>
        <li class="site-nav-li push-right"><a href="<?= url('/posts') ?>" class="site-nav-link">Posts</a></li>

        <?php if (!Session::isLoggedIn()): ?>
            <li class="site-nav-li"><a href="<?= url('/login') ?>" class="site-nav-link">Login</a></li>
            <li class="site-nav-li"><a href="<?= url('/register') ?>" class="site-nav-link">Register</a></li>
        <?php else: ?>
            <li class="site-nav-li"><a href="<?= url('/logout') ?>" class="site-nav-link">Logout</a></li>
        <?php endif; ?>

    </ul>
</nav>

<?php Session::flash() ?>
