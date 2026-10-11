<?php
$navScriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
if (in_array(strtolower(basename($navScriptDir)), ['admin', 'auth', 'pages'], true)) {
    $navScriptDir = dirname($navScriptDir);
}
$navBase = rtrim($navScriptDir, '/.');
$navBase = $navBase === '' ? '' : $navBase;
$navUrl = static fn (string $path): string => $navBase . '/' . ltrim($path, '/');
$navLoggedIn = !empty($_SESSION['user_id']);
$navIsAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$navCartCount = 0;

if ($navLoggedIn && isset($pdo)) {
    try {
        $navCartStmt = $pdo->prepare(
            'SELECT COALESCE(SUM(ci.quantity), 0)
             FROM public.carts c
             LEFT JOIN public.cart_items ci ON ci.cart_id = c.id
             WHERE c.user_id = :uid'
        );
        $navCartStmt->execute([':uid' => $_SESSION['user_id']]);
        $navCartCount = (int) $navCartStmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('ShopFlow navigation cart count error: ' . $e->getMessage());
    }
}

$navPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<nav class="navbar navbar-expand-lg sticky-top shop-navbar">
    <div class="container">
        <a class="navbar-brand" href="<?= h($navUrl('index.php')) ?>">ShopFlow</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#shopNavbar"
                aria-controls="shopNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="shopNavbar">
            <div class="navbar-nav me-auto ms-lg-4">
                <a class="nav-link <?= $navPage === 'index.php' ? 'active' : '' ?>" href="<?= h($navUrl('index.php')) ?>">Shop</a>
                <?php if ($navLoggedIn): ?>
                    <a class="nav-link <?= $navPage === 'orders.php' ? 'active' : '' ?>" href="<?= h($navUrl('orders.php')) ?>">My orders</a>
                    <?php if ($navIsAdmin): ?>
                        <a class="nav-link <?= $navPage === 'products.php' ? 'active' : '' ?>" href="<?= h($navUrl('admin/products.php')) ?>">Manage products</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <form class="navbar-search" action="<?= h($navUrl('index.php')) ?>" method="get" role="search">
                <label class="visually-hidden" for="shop-search">Search products</label>
                <input id="shop-search" type="search" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="Search products" aria-label="Search products">
                <button type="submit" aria-label="Search">Search</button>
            </form>
            <div class="navbar-actions">
                <?php if ($navLoggedIn): ?>
                    <a class="nav-cart" href="<?= h($navUrl('cart.php')) ?>" aria-label="Cart, <?= $navCartCount ?> items">
                        <span>Cart</span><span class="cart-count"><?= $navCartCount ?></span>
                    </a>
                    <span class="nav-greeting">Hi, <?= h($_SESSION['user_name'] ?? 'there') ?></span>
                    <form action="<?= h($navUrl('auth/logout.php')) ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <button type="submit" class="btn btn-outline-primary btn-sm">Log out</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-outline-primary btn-sm" href="<?= h($navUrl('auth/login.php')) ?>">Log in</a>
                    <a class="btn btn-primary btn-sm" href="<?= h($navUrl('auth/register.php')) ?>">Create account</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
